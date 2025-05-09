<?php

namespace EleganceCMS\Iyzico;

use EleganceCMS\PluginManagement\Abstracts\PluginOperationAbstract;
use EleganceCMS\Setting\Facades\Setting;

class Plugin extends PluginOperationAbstract
{
    public static function remove(): void
    {
        Setting::delete([
            'payment_iyzico_payment_type',
            'payment_iyzico_name',
            'payment_iyzico_description',
            'payment_iyzico_shop_code',
            'payment_iyzico_merchant_pass',
            'payment_iyzico_status',
        ]);
    }
}