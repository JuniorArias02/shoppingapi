<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'usuario_id',
        'total',
        'estado',
        'direccion_envio',
        'ciudad',
        'codigo_postal',
        'telefono',
        'metodo_pago',
        'notas_cliente',
        'pagado_en',
        'cancelado_en'
    ];

    protected $casts = [
        'pagado_en' => 'datetime',
        'cancelado_en' => 'datetime',
    ];

    // Estados del pedido
    const ESTADO_PENDIENTE = 'pendiente';           // Creado, esperando pago
    const ESTADO_PAGADO = 'pagado';                 // Pago confirmado
    const ESTADO_VISTO = 'visto';                   // Admin ha visto el pedido
    const ESTADO_EMPACADO = 'empacado';             // Pedido empacado
    const ESTADO_PROCESANDO = 'procesando';         // En preparación (legacy/generic)
    const ESTADO_ENVIADO = 'enviado';               // En camino
    const ESTADO_ENTREGADO = 'entregado';           // Completado
    const ESTADO_CANCELADO = 'cancelado';           // Cancelado
    const ESTADO_REEMBOLSADO = 'reembolsado';       // Pago devuelto

    // Tiempo de expiración para pedidos pendientes (minutos)
    const TIEMPO_EXPIRACION_PENDIENTE = 30;

    /**
     * Transiciones válidas de estados
     */
    public static function transicionesValidas()
    {
        return [
            self::ESTADO_PENDIENTE => [self::ESTADO_PAGADO, self::ESTADO_CANCELADO],
            self::ESTADO_PAGADO => [self::ESTADO_VISTO, self::ESTADO_PROCESANDO, self::ESTADO_REEMBOLSADO],
            self::ESTADO_VISTO => [self::ESTADO_EMPACADO, self::ESTADO_REEMBOLSADO],
            self::ESTADO_EMPACADO => [self::ESTADO_ENVIADO, self::ESTADO_REEMBOLSADO],
            self::ESTADO_PROCESANDO => [self::ESTADO_ENVIADO, self::ESTADO_REEMBOLSADO],
            self::ESTADO_ENVIADO => [self::ESTADO_ENTREGADO],
            // Estados finales no tienen transiciones
            self::ESTADO_ENTREGADO => [],
            self::ESTADO_CANCELADO => [],
            self::ESTADO_REEMBOLSADO => [],
        ];
    }

    /**
     * Verificar si puede transicionar a un nuevo estado
     */
    public function puedeTransicionarA($nuevoEstado)
    {
        $transiciones = self::transicionesValidas();
        return in_array($nuevoEstado, $transiciones[$this->estado] ?? []);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function items()
    {
        return $this->hasMany(PedidoItem::class);
    }

    /**
     * Relación con los pagos
     */
    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    /**
     * Relación con las reservas de stock
     */
    public function reservas()
    {
        return $this->hasMany(ReservaStock::class);
    }
}
