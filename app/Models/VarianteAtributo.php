<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VarianteAtributo extends Model
{
    use HasFactory;

    protected $table = 'variante_atributos';

    public $timestamps = false; // Usually pivot tables don't have timestamps unless specified.

    protected $fillable = [
        'producto_variante_id',
        'atributo_valor_id'
    ];
}
