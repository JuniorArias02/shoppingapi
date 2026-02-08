<?php

namespace App\Mail;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $pedido;
    public $status;
    public $mensaje;
    public $titulo;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Pedido $pedido, $status)
    {
        $this->user = $user;
        $this->pedido = $pedido;
        $this->status = $status;
        
        // Configurar mensaje según estado
        switch($status) {
            case 'visto':
                $this->titulo = "¡Hemos visto tu pedido!";
                $this->mensaje = "Tu pedido #{$pedido->id} ha sido revisado por nuestro equipo y pronto comenzaremos a prepararlo.";
                break;
            case 'empacado':
                $this->titulo = "¡Tu pedido está listo!";
                $this->mensaje = "Tu pedido #{$pedido->id} ya ha sido empacado y está esperando ser enviado.";
                break;
            case 'enviado':
                $this->titulo = "¡Tu pedido va en camino!";
                $this->mensaje = "Tu pedido #{$pedido->id} ha salido de nuestras instalaciones y va rumbo a tu dirección.";
                break;
            case 'entregado':
                $this->titulo = "¡Pedido Entregado!";
                $this->mensaje = "Esperamos que disfrutes tu compra. Gracias por elegir Shopping Cúcuta.";
                break;
            default:
                $this->titulo = "Actualización de Pedido";
                $this->mensaje = "El estado de tu pedido #{$pedido->id} ha cambiado a: " . ucfirst($status);
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->titulo . ' - Shopping Cúcuta',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.order_status',
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
