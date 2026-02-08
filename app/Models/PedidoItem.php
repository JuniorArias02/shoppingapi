<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoItem extends Model
{
    use HasFactory;

    protected $table = 'pedido_items';

    protected $fillable = ['pedido_id', 'producto_variante_id', 'cantidad', 'precio_unitario'];

    public $timestamps = false;

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'producto_variante_id');
    }
}
