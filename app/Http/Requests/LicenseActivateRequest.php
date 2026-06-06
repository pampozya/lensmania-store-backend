<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LicenseActivateRequest extends FormRequest
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
            'platform' => ['required', 'string', 'in:mac-arm64,mac-x64,win-x64,macos,davinci-resolve'],
            'app_version' => ['required', 'string', 'max:64'],
        ];
    }
}
