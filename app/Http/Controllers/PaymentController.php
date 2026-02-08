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

        // Generate unique reference
        // Format: ORDER-{id}-{timestamp} to allow retries with unique references
        $reference = "ORDER-{$pedido->id}-" . time();
        
        $amountInCents = (int) ($pedido->total * 100);
        $currency = 'COP';

        $signature = $this->wompiService->generateIntegritySignature($reference, $amountInCents, $currency);
        
        \Illuminate\Support\Facades\Log::info("Wompi Signature Debug:", [
            'reference' => $reference,
            'amount' => $amountInCents,
            'currency' => $currency,
            'signature' => $signature
        ]);

        $response = [
            'reference' => $reference,
            'amount_in_cents' => $amountInCents,
            'currency' => $currency,
            'signature' => $signature,
            'public_key' => $this->wompiService->getPublicKey(),
            'redirect_url' => env('FRONTEND_URL', env('APP_URL')) . "/client/orders", 
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
                return response()->json(['message' => 'Invalid signature'], 400);
            }

            $transaction = $data['data']['transaction'];
            $reference = $transaction['reference'];
            $status = $transaction['status']; // APPROVED, DECLINED, VOIDED, ERROR

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
     * Confirmar pago manual (o fallback)
     */
    public function confirm(Request $request, $pagoId)
    {
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

                // Decrement physical stock
                $variante->decrement('stock', $item->cantidad);

                // Record movement
                MovimientoStock::create([
                    'producto_variante_id' => $variante->id,
                    'tipo' => MovimientoStock::TIPO_SALIDA,
                    'cantidad' => -$item->cantidad,
                    'motivo' => "Venta - Pedido #{$pedido->id} ({$motivo})",
                ]);
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
