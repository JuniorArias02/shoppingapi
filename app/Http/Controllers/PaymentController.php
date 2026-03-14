<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Pedido;
use App\Models\MovimientoStock;
use App\Models\ReservaStock;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\WompiService;

class PaymentController extends Controller
{
    protected $wompiService;
    protected $emailService;

    public function __construct(WompiService $wompiService, \App\Services\EmailService $emailService)
    {
        $this->wompiService = $wompiService;
        $this->emailService = $emailService;
    }

    /**
     * Initialize Wompi Transaction
     * Returns parameters needed for the Frontend Widget
     */
    public function initWompiTransaction(Request $request)
    {
        $request->validate([
            'pedido_id' => 'required|exists:pedidos,id'
        ]);

        $pedido = Pedido::findOrFail($request->pedido_id);

        if ($pedido->usuario_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Format: ORDER-{id}-{timestamp_ms}
        // Using ORDER- prefix consistently for identification in webhooks
        $timestampMs = round(microtime(true) * 1000);
        $reference = "ORDER-{$pedido->id}-{$timestampMs}";

        $amountInCents = (int) round($pedido->total * 100);
        $currency = 'COP';

        $signatureData = $this->wompiService->generateIntegritySignature($reference, $amountInCents, $currency);

        \Illuminate\Support\Facades\Log::info("Wompi Signature Debug:", [
            'reference' => $reference,
            'amount' => $amountInCents,
            'currency' => $currency,
            'signature_integrity' => $signatureData['integrity']
        ]);

        $response = [
            'reference' => $reference,
            'amount_in_cents' => $amountInCents,
            'currency' => $currency,
            'signature' => $signatureData,
            'public_key' => $this->wompiService->getPublicKey(),
            'redirect_url' => env('FRONTEND_URL', env('APP_URL')) . "/client/gracias",
        ];

        \Illuminate\Support\Facades\Log::info("🚀 Wompi Init Response:", $response);

        return response()->json($response);
    }

    /**
     * Handle Wompi Webhook
     */
    public function handleWompiWebhook(Request $request)
    {
        try {
            $data = $request->all();
            $signature = $data['signature']['checksum'] ?? null;

            if (!$signature || !$this->wompiService->verifyWebhookSignature($data, $signature)) {
                \Illuminate\Support\Facades\Log::warning('⚠️ Wompi Webhook: Invalid Signature', ['data' => $data]);
                return response()->json(['message' => 'Invalid signature'], 400);
            }

            \Illuminate\Support\Facades\Log::info('✅ Wompi Webhook: Signature Verified', ['data' => $data]);

            $transaction = $data['data']['transaction'];
            $reference = $transaction['reference'];
            $status = $transaction['status']; // APPROVED, DECLINED, VOIDED, ERROR

            \Illuminate\Support\Facades\Log::info("💳 Wompi Webhook Processing: {$reference}", ['status' => $status]);

            // Extract Order ID
            if (!preg_match('/^ORDER-(\d+)-/', $reference, $matches)) {
                return response()->json(['message' => 'Invalid reference format'], 400);
            }
            $pedidoId = $matches[1];
            $pedido = Pedido::find($pedidoId);

            if (!$pedido) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            $pago = Pago::where('pedido_id', $pedido->id)->first();

            // Avoid processing if already completed
            if ($pago->estado === Pago::ESTADO_COMPLETADO) {
                return response()->json(['status' => 'already_processed']);
            }

            if ($status === 'APPROVED') {
                $this->finalizePayment($pago, $pedido, $transaction, 'Wompi Webhook');
            } elseif (in_array($status, ['DECLINED', 'ERROR'])) {
                $pago->update([
                    'estado' => Pago::ESTADO_FALLIDO,
                    'pasarela_respuesta' => json_encode($transaction)
                ]);
            } elseif ($status === 'VOIDED') {
                $pago->update([
                    'estado' => Pago::ESTADO_CANCELADO,
                    'pasarela_respuesta' => json_encode($transaction)
                ]);
                // Release stock reservation
                ReservaStock::where('pedido_id', $pedido->id)->delete();
                $pedido->update(['estado' => Pedido::ESTADO_CANCELADO]);
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Wompi Webhook Error: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Verify Wompi Transaction from Frontend Success
     */
    public function verifyTransaction(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|string'
        ]);

        $transactionId = $request->transaction_id;

        \Illuminate\Support\Facades\Log::info("🔍 Verifying Wompi Transaction (Frontend Request): {$transactionId}");

        $transaction = $this->wompiService->getTransaction($transactionId);

        if (!$transaction) {
            return response()->json(['message' => 'No se pudo verificar la transacción con Wompi'], 404);
        }

        $reference = $transaction['reference'];
        $status = $transaction['status'];

        \Illuminate\Support\Facades\Log::info("💳 Wompi Verification Status: {$status} for reference {$reference}");

        // Extract Order ID
        if (!preg_match('/^ORDER-(\d+)-/', $reference, $matches)) {
            return response()->json(['message' => 'Formato de referencia inválido'], 400);
        }
        $pedidoId = $matches[1];
        $pedido = Pedido::find($pedidoId);

        if (!$pedido) {
            return response()->json(['message' => 'Pedido no encontrado'], 404);
        }

        $pago = Pago::where('pedido_id', $pedido->id)->first();

        if (!$pago) {
            return response()->json(['message' => 'Registro de pago no encontrado'], 404);
        }

        // Si ya está completado, simplemente retornar éxito
        if ($pago->estado === Pago::ESTADO_COMPLETADO) {
            return response()->json([
                'status' => 'APPROVED',
                'message' => 'Pago ya procesado previamente'
            ]);
        }

        if ($status === 'APPROVED') {
            $this->finalizePayment($pago, $pedido, $transaction, 'Frontend Verify');
            return response()->json([
                'status' => 'APPROVED',
                'message' => 'Pago verificado y completado'
            ]);
        }

        return response()->json([
            'status' => $status,
            'message' => "La transacción se encuentra en estado: {$status}"
        ]);
    }

    /**
     * Confirmar pago manual (o fallback)
     */
    public function confirm(Request $request, $pagoId)
    {
        if ($request->user()->rol_id !== 1) {
            return response()->json(['message' => 'No tienes permisos para realizar esta acción'], 403);
        }
        
        $pago = Pago::findOrFail($pagoId);

        if ($pago->estado !== Pago::ESTADO_PENDIENTE) {
            return response()->json(['message' => 'El pago ya fue procesado'], 400);
        }

        try {
            // Simulamos verificación manual
            $transactionData = [
                'status' => 'APPROVED',
                'method' => 'manual_confirm',
                'confirmed_by' => $request->user()->id
            ];

            $this->finalizePayment($pago, $pago->pedido, $transactionData, 'Manual Confirm');

            return response()->json([
                'message' => 'Pago confirmado exitosamente',
                'pedido' => $pago->pedido->fresh()->load('items'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Logic to finalize payment and update stock
     */
    private function finalizePayment(Pago $pago, Pedido $pedido, array $transactionData, string $motivo)
    {
        DB::transaction(function () use ($pago, $pedido, $transactionData, $motivo) {
            // 1. Update Payment
            $pago->update([
                'estado' => Pago::ESTADO_COMPLETADO,
                'fecha_pago' => now(),
                'pasarela_transaccion_id' => $transactionData['id'] ?? null,
                'pasarela_nombre' => 'Wompi', // Or dynamic
                'pasarela_respuesta' => json_encode($transactionData),
            ]);

            // 2. Update Order
            $pedido->update([
                'estado' => Pedido::ESTADO_PAGADO,
                'pagado_en' => now(),
            ]);

            // 3. Process Stock (Convert Reserve to Sale)
            foreach ($pedido->items as $item) {
                $variante = $item->variante;

                if ($variante) {
                    // Decrement physical stock
                    $variante->decrement('stock', $item->cantidad);

                    // Refresh variant to get updated stock
                    $variante->refresh();

                    // If stock reaches 0, disable variant
                    if ($variante->stock <= 0) {
                        $variante->update(['activo' => 0]);

                        // Check if parent product should also be disabled
                        $producto = $variante->producto;
                        if ($producto) {
                            $hasActiveStock = $producto->variantes()
                                ->where('activo', 1)
                                ->where('stock', '>', 0)
                                ->exists();

                            if (!$hasActiveStock) {
                                $producto->update(['activo' => 0]);
                            }
                        }
                    }

                    // Record movement
                    MovimientoStock::create([
                        'producto_variante_id' => $variante->id,
                        'tipo' => MovimientoStock::TIPO_SALIDA,
                        'cantidad' => -$item->cantidad,
                        'motivo' => "Venta - Pedido #{$pedido->id} ({$motivo})",
                    ]);
                }
            }

            // 4. Delete Reserves
            ReservaStock::where('pedido_id', $pedido->id)->delete();

            // 5. Log
            Log::create([
                'usuario_id' => $pedido->usuario_id,
                'accion' => 'compra_confirmada',
                'tabla' => 'pedidos',
                'registro_id' => $pedido->id,
                'ip' => request()->ip(),
            ]);

            // 6. Send Confirmation Email (Since it wasn't sent at creation for online payments)
            try {
                // Ensure relationships are loaded for the email template
                $pedido->load('items.variante.producto');
                $this->emailService->sendOrderConfirmation($pedido->usuario, $pedido);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Error sending confirmation email in PaymentController: " . $e->getMessage());
            }
        });
    }
}
