<?php

namespace EleganceCMS\Iyzico\Http\Controllers;

use EleganceCMS\Base\Http\Controllers\BaseController;
use EleganceCMS\Base\Http\Responses\BaseHttpResponse;
use EleganceCMS\Ecommerce\Models\Order;
use EleganceCMS\Iyzico\Models\PaymentType;
use EleganceCMS\Iyzico\Services\Gateways\IyzicoPaymentService;
use EleganceCMS\Payment\Enums\PaymentStatusEnum;
use EleganceCMS\Payment\Supports\PaymentHelper;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Iyzipay\Model\CheckoutForm;
use Iyzipay\Model\ThreedsPayment;
use Iyzipay\Request\CreateThreedsPaymentRequest;
use Iyzipay\Request\RetrieveCheckoutFormRequest;

class IyzicoController extends BaseController
{
    private IyzicoPaymentService $paymentService;
    private $error = null;

    public function __construct(IyzicoPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
    private function handlePayWithIyzico(Request $request): CheckoutForm
    {
        $paymentRequest = new RetrieveCheckoutFormRequest();
        $paymentRequest->setLocale(app()->getLocale());
        $paymentRequest->setToken(token: $request->input('token'));
        $paymentResponse = CheckoutForm::retrieve($paymentRequest, $this->paymentService->paymentOptions());

        $this->setToken($paymentResponse->getBasketId());

        if (!$this->isSuccessfully($paymentResponse->getPaymentStatus())) {
            $this->setError($paymentResponse->getErrorMessage());
        }

        return $paymentResponse;
    }

    private function handlePayWithDefault(Request $request): ThreedsPayment
    {
        if ($this->isSuccessfully($request->input('status'))) {
            $this->setError(__('3D verification failed.'));
        }

        $paymentRequest = new CreateThreedsPaymentRequest();
        $paymentRequest->setLocale(app()->getLocale());
        $paymentRequest->setConversationId($request->input('conversationId'));
        $paymentRequest->setPaymentId($request->input('paymentId'));

        if ($conversationData = $request->input('conversationData')) {
            $paymentRequest->setConversationData($conversationData);
        }

        $paymentResponse = ThreedsPayment::create($paymentRequest, $this->paymentService->paymentOptions());

        $this->setToken($request->input('token'));

        if (!$this->isSuccessfully($paymentResponse->getStatus())) {
            $this->setError($paymentResponse->getErrorMessage());
        }

        return $paymentResponse;
    }

    public function gateway(Request $request): ?RedirectResponse
    {
        if (in_array(PaymentType::getCurrentType(), [PaymentType::PayWithIyzico, PaymentType::IyzicoCheckoutForm])) {
            $paymentResponse = $this->handlePayWithIyzico($request);
        } else {
            $paymentResponse = $this->handlePayWithDefault($request);
        }

        if ($this->hasError()) {
            return $this->redirectWithMessage('payments.iyzico.error', $this->getError());
        }

        return $this->processOrderData($paymentResponse, $request);

    }

    public function processOrderData($paymentResponse, Request $request): RedirectResponse
    {
        $order = Order::where('token', $paymentResponse->getBasketId())->first();

        if ($order) {
            $order->status = PaymentStatusEnum::COMPLETED;
            $order->save();
        }

        do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
            'amount' => $paymentResponse->getPaidPrice(),
            'currency' => $paymentResponse->getCurrency(),
            'charge_id' => $paymentResponse->getPaymentId(),
            'payment_channel' => IYZICO_PAYMENT_METHOD_NAME,
            'status' => PaymentStatusEnum::COMPLETED,
            'customer_id' => 0,
            'customer_type' => null,
            'payment_type' => 'direct',
            'order_id' => $order->id ?? 0,
        ], $request);

        return $this->redirectWithMessage('payments.iyzico.success', __('Checkout successfully!'));
    }

    public function getInstallments(Request $request, IyzicoPaymentService $paymentService)
    {
        $request->validate([
            'binNumber' => 'required|digits:6',
            'price' => 'required|numeric|min:0.01',
        ]);

        try {
            $installmentInfo = $paymentService->getInstallmentOptions($request->binNumber, $request->price);

            if ($installmentInfo->getStatus() === "success") {
                $installmentDetails = $installmentInfo->getInstallmentDetails();

                $installments = [];
                foreach ($installmentDetails as $detail) {
                    $installmentPrices = [];
                    foreach ($detail->getInstallmentPrices() as $installment) {
                        $installmentPrices[] = [
                            'installmentNumber' => $installment->getInstallmentNumber(),
                            'totalPrice' => $installment->getTotalPrice(),
                        ];
                    }

                    $installments[] = [
                        'cardType' => $detail->getCardType(),
                        'bankName' => $detail->getBankName(),
                        'installmentPrices' => $installmentPrices,
                    ];
                }

                return response()->json(['success' => true, 'data' => $installments]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $installmentInfo->getErrorMessage(),
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function checkThreeds($data)
    {
        exit(base64_decode($data));
    }

    public function checkBin(Request $request, IyzicoPaymentService $paymentService)
    {
        $request->validate([
            'binNumber' => 'required|digits:6',
        ]);

        try {
            $binDetails = $paymentService->checkBin($request->binNumber);
            return response()->json(['success' => true, 'data' => $binDetails]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
    public function success(BaseHttpResponse $response): BaseHttpResponse
    {
        return $response
            ->setNextUrl(PaymentHelper::getRedirectURL())
            ->setMessage(__('Checkout successfully!'));
    }

    public function error(Request $request, BaseHttpResponse $response): BaseHttpResponse
    {
        return $response      
            ->setNextUrl(PaymentHelper::getRedirectURL())                 
            ->setError()
            ->setMessage($request->input('message', __('An error occurred during the payment process.')));
    }

    private function redirectWithMessage(string $routeName, string $message): RedirectResponse
    {
        return redirect()
            ->to(route($routeName, ['message' => $message]))
            ->send();
    }

    private function setToken($token)
    {
        session(['tracked_start_checkout' => $token]);
    }

    private function setError($message)
    {
        $this->error = __($message ?? "Payment Failed!");
    }

    private function getError(): array|string|null
    {
        return $this->error;
    }

    private function hasError(): bool
    {
        return $this->error ?? false;
    }

    private function isSuccessfully($status): bool
    {
        if (strtoupper($status) != 'SUCCESS')
            return false;

        return true;
    }
}