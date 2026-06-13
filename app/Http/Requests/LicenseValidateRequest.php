<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LicenseValidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string'],
            'device_id' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'string', 'in:mac-arm64'],
            'app_version' => ['required', 'string', 'max:64'],
            'grace_used' => ['sometimes', 'boolean'],
            'grace_started_at' => ['required_if:grace_used,true', 'nullable', 'integer', 'min:0'],
        ];
    }
}
