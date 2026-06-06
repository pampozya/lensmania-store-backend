<?php

namespace App\Services;

use App\Contracts\PayPalWebhookVerifier as PayPalWebhookVerifierContract;
use Illuminate\Http\Request;

class PayPalWebhookVerifier implements PayPalWebhookVerifierContract
{
    public function verify(Request $request): bool
    {
        $webhookId = (string) config('paypal.webhook_id', env('PAYPAL_WEBHOOK_ID', ''));

        if ($webhookId === '') {
            return false;
        }

        $transmissionId = (string) $request->header('PAYPAL-TRANSMISSION-ID', '');
        $transmissionSig = (string) $request->header('PAYPAL-TRANSMISSION-SIG', '');
        $transmissionTime = (string) $request->header('PAYPAL-TRANSMISSION-TIME', '');

        if ($transmissionId === '' || $transmissionSig === '' || $transmissionTime === '') {
            return false;
        }

        return true;
    }
}

