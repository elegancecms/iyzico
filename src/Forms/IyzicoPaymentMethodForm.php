<?php

namespace EleganceCMS\Iyzico\Forms;

use EleganceCMS\Base\Facades\BaseHelper;
use EleganceCMS\Base\Forms\FieldOptions\TextFieldOption;
use EleganceCMS\Base\Forms\FieldOptions\SelectFieldOption;
use EleganceCMS\Base\Forms\Fields\TextField;
use EleganceCMS\Base\Forms\Fields\SelectField;
use EleganceCMS\Iyzico\Models\PaymentType;

use EleganceCMS\Payment\Forms\PaymentMethodForm;

class IyzicoPaymentMethodForm extends PaymentMethodForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->paymentId(IYZICO_PAYMENT_METHOD_NAME)
            ->paymentName('Iyzico')
            ->paymentDescription(__('Customer can buy product and pay directly using Visa, Credit card via :name', ['name' => 'Iyzico']))
            ->paymentLogo(url('vendor/core/plugins/iyzico/images/iyzico.svg'))
            ->paymentUrl('https://iyzico.com')
            ->paymentInstructions(view('plugins/iyzico::instructions')->render())
            ->add(
                sprintf('payment_%s_api_key', IYZICO_PAYMENT_METHOD_NAME),
                TextField::class,
                TextFieldOption::make()
                    ->label(__('plugins/iyzico::iyzico.api_key'))
                    ->value(BaseHelper::hasDemoModeEnabled() ? '*******************************' : get_payment_setting('api_key', IYZICO_PAYMENT_METHOD_NAME))
                    ->placeholder(__('plugins/iyzico::iyzico.api_key_placeholder'))
                    ->attributes(['data-counter' => 400])
                    ->toArray()
            )
            ->add(
                sprintf('payment_%s_secret_key', IYZICO_PAYMENT_METHOD_NAME),
                'password',
                TextFieldOption::make()
                    ->label(__('plugins/iyzico::iyzico.secret_key'))
                    ->value(BaseHelper::hasDemoModeEnabled() ? '*******************************' : get_payment_setting('secret_key', IYZICO_PAYMENT_METHOD_NAME))
                    ->placeholder(__('plugins/iyzico::iyzico.secret_key_placeholder'))
                    ->toArray()
            )
            ->add(
                sprintf('payment_%s_payment_type', IYZICO_PAYMENT_METHOD_NAME),
                SelectField::class,
                SelectFieldOption::make()
                    ->label(__('plugins/iyzico::iyzico.type'))
                    ->choices(PaymentType::getChoices())
                    ->selected(PaymentType::getCurrentType())
                    ->toArray()
            )
            ->add(
                sprintf('payment_%s_environment', IYZICO_PAYMENT_METHOD_NAME),
                SelectField::class,
                SelectFieldOption::make()
                    ->label(__('plugins/iyzico::iyzico.environment'))
                    ->choices([
                        'sandbox' => __('Sandbox (Test)'),
                        'live' => __('Live (Production)'),
                    ])
                    ->selected(BaseHelper::hasDemoModeEnabled() ? 'sandbox' : get_payment_setting('environment', IYZICO_PAYMENT_METHOD_NAME))
                    ->toArray()
            );
    }
}