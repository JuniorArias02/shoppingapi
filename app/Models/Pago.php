<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $fillable = [
        'pedido_id',
        'metodo_pago',
        'estado',
        'monto',
        'moneda',
        'pasarela_transaccion_id',
        'pasarela_nombre',
        'pasarela_respuesta',
        'fecha_pago',
        'notas'
    ];

    protected $casts = [
        'fecha_pago' => 'datetime',
        'pasarela_respuesta' => 'array',
    ];

    // Estados del pago
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_COMPLETADO = 'completado';
    const ESTADO_FALLIDO = 'fallido';
    const ESTADO_CANCELADO = 'cancelado';
    const ESTADO_REEMBOLSADO = 'reembolsado';

    // Métodos de pago
    const METODO_STRIPE = 'stripe';
    const METODO_WOMPI = 'wompi';
    const METODO_EFECTIVO = 'efectivo';
    const METODO_TRANSFERENCIA = 'transferencia';

    /**
     * Relación con el pedido
     */
    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * Verificar si el pago está completado
     */
    public function estaCompletado()
    {
        return $this->estado === self::ESTADO_COMPLETADO;
    }

    /**
     * Verificar si el pago está pendiente
     */
    public function estaPendiente()
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }
}
