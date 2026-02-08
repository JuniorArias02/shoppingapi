<?php

namespace App\Mail;

use App\Models\Carrito;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonedCartEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $carrito;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Carrito $carrito)
    {
        $this->user = $user;
        $this->carrito = $carrito;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Tus productos te esperan! 🍕 - Venezia Pizzas',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.abandoned_cart',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
