<?php

namespace EleganceCMS\Iyzico\Http\Requests;
use EleganceCMS\Support\Http\Requests\Request;

class TermsRequest extends Request
{
    public function rules(): array
    {
        return [
            'agree_terms_and_policy' => 'required|accepted',
        ];
    }

    public function messages(): array
    {
        return [
            'agree_terms_and_policy.required' => 'You must agree to the terms and conditions and privacy policy.',
            'agree_terms_and_policy.accepted' => 'You must agree to the terms and conditions and privacy policy.',
        ];
    }
}