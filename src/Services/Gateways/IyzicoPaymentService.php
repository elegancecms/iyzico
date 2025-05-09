<?php

namespace EleganceCMS\Iyzico\Services\Gateways;

use EleganceCMS\Iyzico\Facades\Iyzico;
use EleganceCMS\Iyzico\Models\PaymentType;
use EleganceCMS\Iyzico\Services\Abstracts\IyzicoPaymentAbstract;
use Iyzipay\Options;
use Iyzipay\Model\Locale;
use Iyzipay\Model\PaymentChannel;
use Iyzipay\Model\PaymentGroup;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\Address;
use Iyzipay\Model\BinNumber;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\InstallmentInfo;
use Iyzipay\Model\PaymentCard;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;
use Iyzipay\Request\CreatePaymentRequest;
use Iyzipay\Request\CreatePayWithIyzicoInitializeRequest;
use Iyzipay\Request\RetrieveBinNumberRequest;
use Iyzipay\Request\RetrieveInstallmentInfoRequest;

class IyzicoPaymentService extends IyzicoPaymentAbstract
{
    public function paymentOptions(): Options
    {
        $options = new Options();
        $options->setApiKey(get_payment_setting('api_key', IYZICO_PAYMENT_METHOD_NAME));
        $options->setSecretKey(get_payment_setting('secret_key', IYZICO_PAYMENT_METHOD_NAME));

        $baseUrl = get_payment_setting('environment', IYZICO_PAYMENT_METHOD_NAME) === 'live'
            ? "https://api.iyzipay.com"
            : "https://sandbox-api.iyzipay.com";

        $options->setBaseUrl($baseUrl);

        return $options;
    }

    public function paymentRequest(array $data)
    {
        $paymentType = PaymentType::getCurrentType();

        $paymentRequest = match ($paymentType) {
            PaymentType::PayWithIyzico => new CreatePayWithIyzicoInitializeRequest(),
            PaymentType::IyzicoCheckoutForm => new CreateCheckoutFormInitializeRequest(),
            default => new CreatePaymentRequest(),
        };

        $this->setPaymentBaseInfo($paymentRequest, $data);

        if ($paymentType === PaymentType::Default) {
            $this->setPaymentCard($paymentRequest, $data);
        }

        $this->setBuyer($paymentRequest, $data);
        $this->setAddresses($paymentRequest, $data);
        $this->setBasketItems($paymentRequest, $data);

        return $paymentRequest;
    }

    private function setPaymentBaseInfo($request, array $data): void
    {
        $convertedAmount = Iyzico::convertAmount($data['amount'], $data['currency']);
        $paymentType = PaymentType::getCurrentType();

        if ($paymentType === PaymentType::Default) {
            $installment = $data['card']['installment'] ?? 1;
            $request->setInstallment($installment);
            $request->setPaymentChannel(PaymentChannel::WEB);
        }

        $request->setLocale(app()->getLocale());

        $request->setPrice($convertedAmount);
        $request->setPaidPrice($convertedAmount);
        $request->setCurrency($data['currency']);
        $request->setBasketId($data['checkout_token']);
        $request->setPaymentGroup(PaymentGroup::PRODUCT);
        $request->setCallbackUrl(route('payments.iyzico.gateway', ['token' => $data['checkout_token']]));

    }

    private function setPaymentCard($request, array $data): void
    {
        if (empty($data['card'])) {
            throw new \InvalidArgumentException('Card information is missing.');
        }

        $cardData = $data['card'];
        $cardNumber = preg_replace('/[^0-9]/', '', $cardData['card_number'] ?? '');

        if (!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2,4})$/', $cardData['expiration_date'] ?? '', $matches)) {
            throw new \InvalidArgumentException('Invalid expiration date format. Expected MM/YY.');
        }

        $expireMonth = $matches[1];
        $expireYear = strlen($matches[2]) === 2 ? '20' . $matches[2] : $matches[2];

        $paymentCard = new PaymentCard();
        $paymentCard->setCardHolderName($cardData['name'] ?? '');
        $paymentCard->setCardNumber($cardNumber);
        $paymentCard->setExpireMonth($expireMonth);
        $paymentCard->setExpireYear($expireYear);
        $paymentCard->setCvc($cardData['security_code'] ?? '');
        $paymentCard->setRegisterCard(0);

        $request->setPaymentCard($paymentCard);
    }

    private function setBuyer($request, array $data): void
    {
        if (empty($data['address'])) {
            throw new \InvalidArgumentException('Address information is missing.');
        }

        $address = $data['address'];
        $fullName = $address['name'] ?? '';
        [$name, $surname] = explode(' ', $fullName, 2) + [null, $fullName];
        
        $buyer = new Buyer();
        $buyer->setId("ELEGANCE" . rand(1000, 9999));
        $buyer->setName($name);
        $buyer->setSurname($surname ?? $name);
        $buyer->setGsmNumber($address['phone'] ?? '');
        $buyer->setEmail($address['email'] ?? '');
        $buyer->setIdentityNumber("00000000000");
        $buyer->setRegistrationAddress($address['address'] ?? '');
        $buyer->setIp(request()->ip());
        $buyer->setCity($address['city'] ?? '');
        $buyer->setCountry($address['country'] ?? '');
        $buyer->setZipCode($address['zip_code'] ?? '');

        $request->setBuyer($buyer);
    }

    private function setAddresses($request, array $data): void
    {
        $shippingAddress = $this->createAddress($data['address']);
        $billingAddress = $this->createAddress(
            $data['billing_address_same_as_shipping_address'] === "0"
                ? $data['billing_address']
                : $data['address']
        );

        $request->setShippingAddress($shippingAddress);
        $request->setBillingAddress($billingAddress);
    }

    private function createAddress(array $data): Address
    {
        $address = new Address();
        $address->setContactName($data['name']);
        $address->setCity($data['city']);
        $address->setCountry($data['country']);
        $address->setAddress($data['address']);
        $address->setZipCode($data['zip_code']);

        return $address;
    }

    private function setBasketItems($request, array $data): void
    {
        if (empty($data['products']) || !is_array($data['products'])) {
            throw new \InvalidArgumentException('Basket information is missing or invalid.');
        }

        $basketItems = array_map(function ($item) use ($data) {
            $price = Iyzico::convertAmount($item['price_per_order'], $data['currency']);
            $basketItem = new BasketItem();
            $basketItem->setId($item['id'] ?? uniqid());
            $basketItem->setName($item['name'] ?? 'Unknown Item');
            $basketItem->setCategory1('General');
            $basketItem->setItemType(BasketItemType::PHYSICAL);
            $basketItem->setPrice($price);

            return $basketItem;
        }, $data['products']);

        $request->setBasketItems($basketItems);
    }

    public function getInstallmentOptions(string $binNumber, float $price)
    {
        $options = $this->paymentOptions();

        $request = new RetrieveInstallmentInfoRequest();
        $request->setPrice((string) $price);
        $request->setBinNumber($binNumber);
        $request->setLocale(Locale::TR);
        $request->setConversationId(uniqid());

        return InstallmentInfo::retrieve($request, $options);
    }

    public function checkBin(string $binNumber): array
    {
        $options = $this->paymentOptions();

        $request = new RetrieveBinNumberRequest();
        $request->setBinNumber($binNumber);
        $request->setLocale("tr");
        $request->setConversationId(uniqid());

        $binInfo = BinNumber::retrieve($request, $options);

        if ($binInfo->getStatus() !== 'success') {
            throw new \Exception($binInfo->getErrorMessage());
        }

        return [
            'binNumber' => $binInfo->getBinNumber(),
            'cardType' => $binInfo->getCardType(),
            'cardAssociation' => $binInfo->getCardAssociation(),
            'cardFamily' => $binInfo->getCardFamily(),
            'bankName' => $binInfo->getBankName(),
            'bankCode' => $binInfo->getBankCode(),
            'commercial' => $binInfo->getCommercial(),
        ];
    }
}