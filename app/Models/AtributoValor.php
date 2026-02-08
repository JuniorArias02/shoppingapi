<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtributoValor extends Model
{
    use HasFactory;

    protected $table = 'atributo_valores';

    protected $fillable = ['atributo_id', 'valor'];

    public $timestamps = false; // Assuming no timestamps as per migration note, but migration might have them if I let default. My migration creation didn't add timestamps for this table explicitly (commented out).

    public function atributo()
    {
        return $this->belongsTo(Atributo::class);
    }

    public function variantes()
    {
        return $this->belongsToMany(ProductoVariante::class, 'variante_atributos', 'atributo_valor_id', 'producto_variante_id');
    }
}
