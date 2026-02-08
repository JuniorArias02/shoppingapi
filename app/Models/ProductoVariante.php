<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoVariante extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->sku)) {
                $model->sku = 'SKU-' . strtoupper(uniqid());
            }
        });
    }

    protected $table = 'producto_variantes';

    protected $fillable = [
        'producto_id',
        'sku',
        'precio',
        'stock',
        'activo'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function valores()
    {
        return $this->belongsToMany(AtributoValor::class, 'variante_atributos', 'producto_variante_id', 'atributo_valor_id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoStock::class);
    }
}
