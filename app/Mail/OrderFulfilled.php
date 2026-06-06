<?php

namespace App\Mail;

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
        $productName = config("downloads.products.{$this->order->product_slug}.name", 'Your Product');

        return new Envelope(
            subject: "Your {$productName} License Key & Download",
        );
    }

    public function content(): Content
    {
        $productName = config("downloads.products.{$this->order->product_slug}.name", 'Your Product');
        $downloadUrl = config("downloads.products.{$this->order->product_slug}.url", '#');

        return new Content(
            view: 'mail.order-fulfilled',
            with: [
                'order' => $this->order,
                'productName' => $productName,
                'downloadUrl' => $downloadUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
