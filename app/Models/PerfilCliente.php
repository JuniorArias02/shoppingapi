<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerfilCliente extends Model
{
    use HasFactory;

    protected $table = 'perfil_clientes';

    protected $fillable = [
        'usuario_id',
        'codigo_postal',
        'direccion',
        'departamento',
        'ciudad',
        'numero_telefono',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
