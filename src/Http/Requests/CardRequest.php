<?php

namespace EleganceCMS\Iyzico\Http\Requests;

use EleganceCMS\Support\Http\Requests\Request;

class CardRequest extends Request
{
    public function rules(): array
    {
        return [
            'card.card_number' => 'required|validate_card_number',
            'card.name' => 'required|string|max:255',
            'card.expiration_date' => 'required|validate_expiration_date',
            'card.security_code' => 'required|numeric|digits_between:3,4',
        ];
    }

    public function messages(): array
    {
        return [
            'card.card_number.required' => 'Card number is required.',
            'card.card_number.validate_card_number' => 'Invalid card number.',
            'card.name.required' => 'Cardholder name is required.',
            'card.name.string' => 'Cardholder name must be a valid text.',
            'card.name.max' => 'Cardholder name cannot exceed 255 characters.',
            'card.expiration_date.required' => 'Expiration date is required.',
            'card.expiration_date.validate_expiration_date' => 'Expiration date must be in MM/YY format.',
            'card.security_code.required' => 'CVC is required.',
            'card.security_code.numeric' => 'CVC must contain only numbers.',
            'card.security_code.digits_between' => 'CVC must be 3 or 4 digits.',
        ];
    }
}
