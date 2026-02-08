<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservaStock extends Model
{
    protected $table = 'reservas_stock';

    protected $fillable = [
        'pedido_id',
        'producto_variante_id',
        'cantidad',
        'expira_en'
    ];

    protected $casts = [
        'expira_en' => 'datetime',
    ];

    /**
     * Relación con el pedido
     */
    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * Relación con la variante del producto
     */
    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'producto_variante_id');
    }

    /**
     * Verificar si la reserva ha expirado
     */
    public function haExpirado()
    {
        return $this->expira_en < now();
    }

    /**
     * Scope para obtener solo reservas activas (no expiradas)
     */
    public function scopeActivas($query)
    {
        return $query->where('expira_en', '>', now());
    }
}
