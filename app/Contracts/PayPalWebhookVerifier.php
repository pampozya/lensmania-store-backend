<?php

namespace App\Contracts;

use Illuminate\Http\Request;

interface PayPalWebhookVerifier
{
    public function verify(Request $request): bool;
}

