<?php

namespace EleganceCMS\Iyzico\Facades;

use EleganceCMS\Iyzico\Supports\IyzicoHelper as BaseIyzicoHelper;
use Illuminate\Support\Facades\Facade;

class Iyzico extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BaseIyzicoHelper::class;
    }
}