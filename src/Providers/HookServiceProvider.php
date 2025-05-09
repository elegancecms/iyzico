<?php

namespace EleganceCMS\Iyzico\Providers;

use EleganceCMS\Iyzico\Facades\Iyzico;
use EleganceCMS\Iyzico\Http\Validators\CustomCardValidators;
use EleganceCMS\Iyzico\Models\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;

use EleganceCMS\Base\Facades\Html;
use EleganceCMS\Payment\Enums\PaymentMethodEnum;
use EleganceCMS\Payment\Facades\PaymentMethods;
use EleganceCMS\Iyzico\Forms\IyzicoPaymentMethodForm;
use EleganceCMS\Iyzico\Services\Gateways\IyzicoPaymentService;
use EleganceCMS\Iyzico\Http\Requests\TermsRequest;
use EleganceCMS\Iyzico\Http\Requests\CardRequest;

use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Model\PayWithIyzicoInitialize;
use Iyzipay\Model\ThreedsInitialize;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        CustomCardValidators::register();

        add_filter(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, [$this, 'registerIyzicoMethod'], 1, 2);

        $this->app->booted(function () {
            add_filter(PAYMENT_FILTER_AFTER_POST_CHECKOUT, [$this, 'checkoutWithIyzico'], 1, 2);
        });

        add_filter(PAYMENT_METHODS_SETTINGS_PAGE, [$this, 'addPaymentSettings'], 1);

        add_filter(BASE_FILTER_ENUM_ARRAY, function ($values, $class) {
            if ($class == PaymentMethodEnum::class) {
                $values['IYZICO'] = IYZICO_PAYMENT_METHOD_NAME;
            }

            return $values;
        }, 1, 2);

        add_filter(BASE_FILTER_ENUM_LABEL, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == IYZICO_PAYMENT_METHOD_NAME) {
                $value = 'Iyzico';
            }

            return $value;
        }, 1, 2);

        add_filter(BASE_FILTER_ENUM_HTML, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == IYZICO_PAYMENT_METHOD_NAME) {
                $value = Html::tag(
                    'span',
                    PaymentMethodEnum::getLabel($value),
                    ['class' => 'label-success status-label']
                )->toHtml();
            }

            return $value;
        }, 1, 2);

        add_filter(PAYMENT_FILTER_GET_SERVICE_CLASS, function ($data, $value) {
            if ($value == IYZICO_PAYMENT_METHOD_NAME) {
                $data = IyzicoPaymentService::class;
            }

            return $data;
        }, 1, 2);

        if (defined('PAYMENT_FILTER_FOOTER_ASSETS')) {
            add_filter(PAYMENT_FILTER_FOOTER_ASSETS, function ($data) {
                return $data . view('plugins/iyzico::assets')->render();
            }, 1);
        }
    }
    public function addPaymentSettings(?string $settings): string
    {
        return $settings . IyzicoPaymentMethodForm::create()->renderForm();
    }
    public function registerIyzicoMethod(?string $html, array $data): string
    {
        PaymentMethods::method(IYZICO_PAYMENT_METHOD_NAME, [
            'html' => view('plugins/iyzico::methods', $data)->render(),
        ]);

        return $html;
    }
    public function checkoutWithIyzico(array $data, Request $request)
    {
        if ($data['type'] !== IYZICO_PAYMENT_METHOD_NAME) {
            return $data;
        }

        if (theme_option('ecommerce_term_and_privacy_policy_url') || theme_option('term_and_privacy_policy_url')) {
            (new TermsRequest($request->all()))->validate(
                (new TermsRequest($request->all()))->rules(),
                (new TermsRequest($request->all()))->messages()
            );
        }

        if (PaymentType::getCurrentType() == PaymentType::Default ) {
            (new CardRequest($request->all()))->validate(
                (new CardRequest($request->all()))->rules(),
                (new CardRequest($request->all()))->messages()
            );
        }

        try {

            $iyzicoPaymentService = $this->app->make(IyzicoPaymentService::class);

            $paymentData = apply_filters(PAYMENT_FILTER_PAYMENT_DATA, [], $request);

            $currentCurrency = get_application_currency();
            $supportedCurrencies = Iyzico::supportedCurrencyCodes();

            if (!in_array($paymentData['currency'], $supportedCurrencies) && strtoupper($currentCurrency->title) !== 'USD') {
                $currencyModel = $currentCurrency->replicate();
                $supportedCurrency = $currencyModel->query()->where('title', 'USD')->first();

                if ($supportedCurrency) {
                    $paymentData['currency'] = strtoupper($supportedCurrency->title);
                    $paymentData['amount'] = $currentCurrency->is_default
                        ? $paymentData['amount'] * $supportedCurrency->exchange_rate
                        : format_price($paymentData['amount'] / $currentCurrency->exchange_rate, $currentCurrency, true);
                }
            }

            if (!in_array($paymentData['currency'], $supportedCurrencies)) {
                $data['error'] = true;
                $data['message'] = __(
                    ":name doesn't support :currency. List of currencies supported by :name: :currencies.",
                    [
                        'name' => 'Iyzico',
                        'currency' => $paymentData['currency'],
                        'currencies' => implode(', ', $supportedCurrencies),
                    ]
                );

                return $data;
            }

            $paymentData['billing_address_same_as_shipping_address'] = $request->input('billing_address_same_as_shipping_address', '0');
            $paymentData['billing_address'] = $request->input('billing_address', []);
            $paymentData['charge_id'] = Str::upper(Str::random(10));
            $paymentData['card'] = $request->input('card', []);

            $paymentOptions = $iyzicoPaymentService->paymentOptions();
            $paymentRequest = $iyzicoPaymentService->paymentRequest($paymentData);

            $currentPaymentType = PaymentType::getCurrentType();
            $IyzicoInitialize = match ($currentPaymentType) {
                PaymentType::PayWithIyzico => PayWithIyzicoInitialize::create($paymentRequest, $paymentOptions),
                PaymentType::IyzicoCheckoutForm => CheckoutFormInitialize::create($paymentRequest, $paymentOptions),
                default => ThreedsInitialize::create($paymentRequest, $paymentOptions),
            };

            if ($IyzicoInitialize->getStatus() === 'success') {
                if ($currentPaymentType === PaymentType::PayWithIyzico || $currentPaymentType === PaymentType::IyzicoCheckoutForm) {
                    header('X-Inertia-Location: ' . ($currentPaymentType === PaymentType::PayWithIyzico
                        ? $IyzicoInitialize->getPayWithIyzicoPageUrl()
                        : $IyzicoInitialize->getPaymentPageUrl()));
                    http_response_code(409);
                    exit;
                }

                header('X-Inertia-Location: ' . route(
                    'payments.iyzico.check-threeds',
                    ['data' => base64_encode($IyzicoInitialize->getHtmlContent())]
                ));
                http_response_code(409);
                exit;
            }

            return array_merge($data, [
                'error' => true,
                'message' => $IyzicoInitialize->getErrorMessage(),
            ]);
        } catch (\Exception $e) {
            return array_merge($data, [
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }
    }

}