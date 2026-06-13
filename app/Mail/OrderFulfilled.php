<?php

namespace App\Mail;

use App\Models\License;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderFulfilled extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your {$this->order->product_name} License — Lensmania Labs",
        );
    }

    public function content(): Content
    {
        // Gather all active licenses for this user.
        $licenses = License::where('user_id', $this->order->user_id)
            ->where('status', 'active')
            ->with('product')
            ->get()
            ->map(fn ($lic) => [
                'product_name' => $lic->product?->name ?? 'License',
                'license_key'  => $lic->license_key,
            ])
            ->filter(fn ($l) => ! empty($l['license_key']))
            ->values()
            ->all();

        return new Content(
            view: 'mail.order-fulfilled',
            with: [
                'order'        => $this->order,
                'licenses'     => $licenses,
                'dashboardUrl' => 'https://labs.lensmania.ae/dashboard',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
