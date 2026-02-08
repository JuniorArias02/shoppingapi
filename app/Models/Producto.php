<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'categoria_id',
        'nombre',
        'descripcion',
        'marca',
        'activo',
        'descuento'
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function imagenes()
    {
        return $this->hasMany(ProductoImagen::class);
    }

    public function calificaciones()
    {
        return $this->hasMany(ProductRating::class);
    }

    public function likes()
    {
        return $this->hasMany(ProductLike::class);
    }

    public function variantes()
    {
        return $this->hasMany(ProductoVariante::class);
    }
}
