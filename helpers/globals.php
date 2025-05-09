<?php

use EleganceCMS\Iyzico\Models\PaymentType;
use Inertia\Inertia;

Inertia::share('plugins.iyzico', ['selected_payment_type' => PaymentType::getCurrentType()]);
