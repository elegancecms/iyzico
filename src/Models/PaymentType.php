<?php

namespace EleganceCMS\Iyzico\Models;

class PaymentType
{
    const Default = "default";
    const IyzicoCheckoutForm = "iyzicocheckoutform";
    const PayWithIyzico = "paywithiyzico";

    public static function getChoices(): array
    {
        return [
            'default' => 'Default',
            'iyzicocheckoutform' => 'IyzicoCheckoutForm',
            'paywithiyzico' => 'PayWithIyzico',
        ];
    }

    public static function getCurrentType()
    {
        return get_payment_setting('payment_type', IYZICO_PAYMENT_METHOD_NAME, self::Default );
    }
}