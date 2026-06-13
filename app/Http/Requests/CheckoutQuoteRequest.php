<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_slug' => ['required', 'string', 'in:cinecut'],
            'promo_code' => ['nullable', 'string', 'max:64'],
            'affiliate_code' => ['nullable', 'string', 'max:64'],
        ];
    }
}
