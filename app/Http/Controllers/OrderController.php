<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\ProductoVariante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    protected $emailService;

    public function __construct(\App\Services\EmailService $emailService)
    {
        $this->emailService = $emailService;
    }
    /**
     * Get user's orders.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Admin (1) and Vendedor (2) see all orders
        // Cliente (3) sees only their own orders
        if (in_array($user->rol_id, [1, 2])) {
            $query = Pedido::with(['items.variante.producto.imagenes', 'usuario.perfil'])
                ->orderBy('created_at', 'desc');
        } else {
            $query = Pedido::where('usuario_id', $user->id)
                ->with(['items.variante.producto.imagenes'])
                ->orderBy('created_at', 'desc');
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        $orders = $query->paginate(10);

        // Format for frontend
        $orders->getCollection()->transform(function ($order) use ($user) {
            $orderData = [
                'id' => $order->id,
                'fecha' => $order->created_at->format('Y-m-d'),
                'total' => $order->total,
                'estado' => $order->estado,
                'items_count' => $order->items->sum('cantidad'),
                'preview_images' => $order->items->take(3)->map(function ($item) {
                    $img = $item->variante->producto->imagenes->first();
                    return $img ? ($img->url_imagen ?? $img) : null;
                }),
                'items' => $order->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'producto' => $item->variante->producto->nombre,
                        'cantidad' => $item->cantidad,
                        'precio' => $item->precio_unitario,
                        'sku' => $item->variante->sku,
                        'imagen' => $item->variante->producto->imagenes->first()->url_imagen ?? null
                    ];
                })
            ];

            // Add client info for Admin/Vendedor
            if (in_array($user->rol_id, [1, 2])) {
                $orderData['cliente'] = [
                    'nombre' => $order->usuario->nombre,
                    'email' => $order->usuario->email,
                    'telefono' => $order->usuario->perfil->numero_telefono ?? 'N/A',
                    'direccion' => $order->usuario->perfil->direccion ?? 'N/A',
                    'ciudad' => $order->usuario->perfil->ciudad ?? 'N/A',
                    'departamento' => $order->usuario->perfil->departamento ?? 'N/A',
                ];
            }

            return $orderData;
        });

        return response()->json($orders);
    }

    /**
     * Create order from cart (Checkout).
     */
    public function store(Request $request)
    {
        $request->validate([
            'direccion_envio' => 'required|string|max:255',
            'ciudad' => 'required|string|max:100',
            'telefono' => 'required|string|max:20',
            'metodo_pago' => 'required|in:stripe,wompi,efectivo,transferencia',
            'codigo_postal' => 'nullable|string|max:20',
            'notas_cliente' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        // 0. Validate User Profile Completeness
        // Ensure the user has a profile record and required fields are set
        $perfil = \App\Models\PerfilCliente::where('usuario_id', $user->id)->first();
        
        if (!$perfil || empty($perfil->numero_telefono) || empty($perfil->direccion) || empty($perfil->ciudad)) {
             return response()->json([
                'message' => 'Perfil incompleto. Por favor actualiza tu información de envío (dirección y teléfono) en tu perfil antes de comprar.',
                'code' => 'PROFILE_INCOMPLETE'
            ], 403);
        }


        DB::beginTransaction();
        try {
            // 1. Obtener carrito
            $cart = Carrito::where('usuario_id', $user->id)->first();
            if (!$cart) {
                return response()->json(['message' => 'Carrito vacío'], 400);
            }

            $cartItems = CarritoItem::where('carrito_id', $cart->id)
                ->with('variante.producto')
                ->get();

            if ($cartItems->isEmpty()) {
                return response()->json(['message' => 'Carrito vacío'], 400);
            }

            // 2. Validar stock disponible (considerando reservas activas)
            // Ordenar por variante_id para evitar deadlocks
            $cartItems = $cartItems->sortBy('producto_variante_id');

            foreach ($cartItems as $item) {
                // Bloquear fila para evitar race conditions
                $variante = ProductoVariante::where('id', $item->producto_variante_id)
                    ->lockForUpdate()
                    ->first();

                $stockDisponible = $this->calcularStockDisponible($variante->id);

                if ($item->cantidad > $stockDisponible) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Stock insuficiente para: {$item->variante->producto->nombre}",
                        'stock_disponible' => $stockDisponible
                    ], 400);
                }
            }

            // 3. Calcular total
            $total = 0;
            foreach ($cartItems as $item) {
                $discount = $item->variante->producto->descuento ?? 0;
                $price = $item->variante->precio * (1 - ($discount / 100));
                $total += $price * $item->cantidad;
            }

            // 4. Determinar estado inicial según método de pago
            $isOnlinePayment = in_array($request->metodo_pago, ['wompi', 'stripe']);
            
            // Para pagos online, el estado inicial es PENDIENTE
            // Para pagos manuales (efectivo/transferencia), mantenemos la lógica actual de "testing" (PAGADO)
            // En un flujo real, efectivo/transferencia también debería ser PENDIENTE hasta confirmar
            $estadoPedido = $isOnlinePayment ? Pedido::ESTADO_PENDIENTE : Pedido::ESTADO_PAGADO;
            $estadoPago = $isOnlinePayment ? \App\Models\Pago::ESTADO_PENDIENTE : \App\Models\Pago::ESTADO_COMPLETADO;
            $pagadoEn = $isOnlinePayment ? null : now();

            $pedido = Pedido::create([
                'usuario_id' => $user->id,
                'total' => $total,
                'estado' => $estadoPedido,
                'direccion_envio' => $request->direccion_envio,
                'ciudad' => $request->ciudad,
                'codigo_postal' => $request->codigo_postal,
                'telefono' => $request->telefono,
                'metodo_pago' => $request->metodo_pago,
                'notas_cliente' => $request->notas_cliente,
                'pagado_en' => $pagadoEn,
            ]);

            // 5. Crear items del pedido (snapshot de precios)
            foreach ($cartItems as $item) {
                $discount = $item->variante->producto->descuento ?? 0;
                $price = $item->variante->precio * (1 - ($discount / 100));

                PedidoItem::create([
                    'pedido_id' => $pedido->id,
                    'producto_variante_id' => $item->producto_variante_id,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => $price,
                ]);
            }

            // 6. Gestionar Stock (Descuento directo o Reserva)
            if ($isOnlinePayment) {
                // RESERVAR STOCK (No descontar todavía)
                foreach ($cartItems as $item) {
                     \App\Models\ReservaStock::create([
                        'producto_variante_id' => $item->producto_variante_id,
                        'pedido_id' => $pedido->id,
                        'cantidad' => $item->cantidad,
                        'expira_en' => now()->addMinutes(Pedido::TIEMPO_EXPIRACION_PENDIENTE)
                     ]);
                }
            } else {
                // DESCONTAR STOCK (Lógica inmediata para testing en efectivo/transf)
                foreach ($cartItems as $item) {
                    $item->variante->decrement('stock', $item->cantidad);

                    // Registrar movimiento de stock
                    \App\Models\MovimientoStock::create([
                        'producto_variante_id' => $item->producto_variante_id,
                        'tipo' => \App\Models\MovimientoStock::TIPO_SALIDA,
                        'cantidad' => -$item->cantidad,
                        'motivo' => "Venta - Pedido #{$pedido->id}",
                    ]);
                }
            }

            // 7. Crear registro de pago
            $pago = \App\Models\Pago::create([
                'pedido_id' => $pedido->id,
                'metodo_pago' => $request->metodo_pago,
                'estado' => $estadoPago,
                'monto' => $total,
                'moneda' => 'COP',
                'fecha_pago' => $pagadoEn,
            ]);

            // 8. Limpiar carrito
            CarritoItem::where('carrito_id', $cart->id)->delete();

            DB::commit();

            // Enviar correo de confirmación (Solo si NO es pago online, o sea Efectivo/Transferencia)
            // Para Wompi/Stripe, el correo se envía en el Webhook cuando se aprueba el pago.
            if (!$isOnlinePayment) {
                try {
                    $this->emailService->sendOrderConfirmation($user, $pedido->load('items.variante.producto'));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Error enviando correo de confirmación: " . $e->getMessage());
                }
            }

            // 9. Preparar respuesta
            return response()->json([
                'message' => 'Pedido creado exitosamente',
                'pedido' => $pedido->load('items.variante.producto'),
                'pago' => $pago,
                // Aquí iría la URL de pago de la pasarela cuando se integre
                'payment_url' => null,
                'expira_en' => now()->addMinutes(Pedido::TIEMPO_EXPIRACION_PENDIENTE)->toIso8601String(),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al procesar el pedido',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular stock disponible considerando reservas activas
     */
    private function calcularStockDisponible($varianteId)
    {
        $variante = ProductoVariante::find($varianteId);
        $stockFisico = $variante->stock;

        // Restar reservas activas (no expiradas)
        $stockReservado = \App\Models\ReservaStock::where('producto_variante_id', $varianteId)
            ->where('expira_en', '>', now())
            ->sum('cantidad');

        return max(0, $stockFisico - $stockReservado);
    }

    /**
     * Cancelar pedido (solo si está pendiente)
     */
    public function cancel(Request $request, $pedidoId)
    {
        $pedido = Pedido::findOrFail($pedidoId);

        // Validar que el usuario sea dueño del pedido
        if ($pedido->usuario_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Solo se pueden cancelar pedidos pendientes
        if ($pedido->estado !== Pedido::ESTADO_PENDIENTE) {
            return response()->json([
                'message' => 'Solo se pueden cancelar pedidos pendientes'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // 1. Actualizar estado del pedido
            $pedido->update([
                'estado' => Pedido::ESTADO_CANCELADO,
                'cancelado_en' => now(),
            ]);

            // 2. Liberar reservas de stock
            \App\Models\ReservaStock::where('pedido_id', $pedido->id)->delete();

            // 3. Cancelar pago pendiente
            \App\Models\Pago::where('pedido_id', $pedido->id)
                ->where('estado', \App\Models\Pago::ESTADO_PENDIENTE)
                ->update(['estado' => \App\Models\Pago::ESTADO_CANCELADO]);

            DB::commit();

            return response()->json(['message' => 'Pedido cancelado exitosamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al cancelar pedido'], 500);
        }
    }

    /**
     * Actualizar estado del pedido (solo admin/vendedor)
     */
    public function updateStatus(Request $request, $pedidoId)
    {
        $user = $request->user();

        // Solo admin (1) y vendedor (2) pueden actualizar estados
        if (!in_array($user->rol_id, [1, 2])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $pedido = Pedido::findOrFail($pedidoId);

        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:visto,empacado,procesando,enviado,entregado'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $nuevoEstado = $request->estado;

        // Validar transiciones permitidas
        $transicionesValidas = [
            Pedido::ESTADO_PAGADO => [Pedido::ESTADO_VISTO, 'procesando'],
            Pedido::ESTADO_VISTO => [Pedido::ESTADO_EMPACADO],
            Pedido::ESTADO_EMPACADO => [Pedido::ESTADO_ENVIADO],
            Pedido::ESTADO_PROCESANDO => [Pedido::ESTADO_ENVIADO],
            Pedido::ESTADO_ENVIADO => [Pedido::ESTADO_ENTREGADO],
        ];

        if (
            !isset($transicionesValidas[$pedido->estado]) ||
            !in_array($nuevoEstado, $transicionesValidas[$pedido->estado])
        ) {
            return response()->json([
                'message' => "No se puede cambiar de '{$pedido->estado}' a '{$nuevoEstado}'"
            ], 400);
        }

        try {
            $pedido->update(['estado' => $nuevoEstado]);

            // Enviar correo de actualización de estado
            try {
                // No enviar correo si es estado 'entregado'
                if ($nuevoEstado !== 'entregado') {
                    $this->emailService->sendOrderStatusUpdate($pedido->usuario, $pedido, $nuevoEstado);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Error enviando email de estado: " . $e->getMessage());
            }

            return response()->json([
                'message' => 'Estado actualizado exitosamente',
                'pedido' => $pedido
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar estado'], 500);
        }
    }
}
