<?php

namespace EleganceCMS\Iyzico\Supports;

use Iyzipay\Model\Currency;

class IyzicoHelper
{
    private function getCurrencyMultiplier(string $currency): int
    {
        $currency = strtoupper($currency);

        return in_array($currency, [
            'BIF',
            'CLP',
            'DJF',
            'GNF',
            'JPY',
            'KMF',
            'KRW',
            'MGA',
            'PYG',
            'RWF',
            'VND',
            'VUV',
            'XAF',
            'XOF',
            'XPF',
        ], true) ? 1 : 100;
    }

    public function convertAmount(float $amount, string $currency): float
    {
        $decimals = cms_currency()->getApplicationCurrency()->decimals ?? 2;
        return round($amount, $decimals);
    }

    public function supportedCurrencyCodes(): array
    {
        return [
            Currency::TL,
            Currency::EUR,
            Currency::USD,
            Currency::GBP,
            Currency::IRR,
            Currency::NOK,
            Currency::RUB,
            Currency::CHF,
        ];
    }

}
