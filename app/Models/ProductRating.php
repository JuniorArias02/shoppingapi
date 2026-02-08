<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Producto;
use App\Models\User;

class ProductRating extends Model
{
    protected $table = 'product_ratings';

    protected $fillable = [
        'producto_id',
        'usuario_id',
        'rating',
        'comment'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
}
