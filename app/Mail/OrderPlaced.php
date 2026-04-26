<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Order confirmation — sent right after a successful POST /api/orders.
 *
 * Eager-loads the relations we render in the Blade template (items.product
 * + address) so the queue worker never lazy-loads inside the mail render.
 * Lazy-loads inside a queued mailable are a classic source of N+1 queries
 * AND of MissingModelException after the cache is restarted.
 */
class OrderPlaced extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        // Reload with relations so the template has everything it needs
        // without touching the DB during render.
        $this->order = $order->load(['items.product', 'address', 'user']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rendelésed visszaigazolása - #' . $this->order->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.order-placed',
            with: [
                'order' => $this->order,
            ],
        );
    }
}
