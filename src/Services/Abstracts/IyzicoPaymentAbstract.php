<?php

namespace EleganceCMS\Iyzico\Services\Abstracts;

use Iyzipay\Request\CreatePaymentRequest;
use Iyzipay\Options;

abstract class IyzicoPaymentAbstract
{
    abstract public function paymentRequest(array $data);
    
    abstract public function paymentOptions(): Options;
            
}