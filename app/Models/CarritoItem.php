<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarritoItem extends Model
{
    use HasFactory;

    protected $table = 'carrito_items';

    protected $fillable = ['carrito_id', 'producto_variante_id', 'cantidad'];

    public $timestamps = false;

    public function carrito()
    {
        return $this->belongsTo(Carrito::class);
    }

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'producto_variante_id');
    }
}
