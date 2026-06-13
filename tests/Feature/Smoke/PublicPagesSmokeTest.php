<?php

namespace Tests\Feature\Smoke;

use Tests\TestCase;

class PublicPagesSmokeTest extends TestCase
{
    public function test_public_pages_and_favicon_load(): void
    {
        $this->get('/')->assertOk();
        $this->get('/checkout/thank-you')->assertOk();

        $this->get('/favicon.ico')
            ->assertOk()
            ->assertHeader('content-type', 'image/svg+xml');
    }
}
