<?php

namespace App\Console\Commands;

use App\Models\Pedido;
use App\Models\Pago;
use App\Models\ReservaStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LimpiarPedidosExpirados extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedidos:limpiar-expirados';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancela pedidos pendientes expirados y libera reservas de stock';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tiempoExpiracion = now()->subMinutes(Pedido::TIEMPO_EXPIRACION_PENDIENTE);

        DB::beginTransaction();
        try {
            // Encontrar pedidos pendientes expirados
            $pedidosExpirados = Pedido::where('estado', Pedido::ESTADO_PENDIENTE)
                ->where('created_at', '<', $tiempoExpiracion)
                ->get();

            foreach ($pedidosExpirados as $pedido) {
                // Cancelar pedido
                $pedido->update([
                    'estado' => Pedido::ESTADO_CANCELADO,
                    'cancelado_en' => now(),
                ]);

                // Cancelar pago pendiente
                Pago::where('pedido_id', $pedido->id)
                    ->where('estado', Pago::ESTADO_PENDIENTE)
                    ->update(['estado' => Pago::ESTADO_CANCELADO]);

                // Liberar reservas
                ReservaStock::where('pedido_id', $pedido->id)->delete();

                $this->info("Pedido #{$pedido->id} cancelado por expiración");
            }

            // Limpiar reservas expiradas huérfanas
            $reservasLiberadas = ReservaStock::where('expira_en', '<', now())->delete();

            DB::commit();

            $this->info("Procesados {$pedidosExpirados->count()} pedidos expirados");
            $this->info("Liberadas {$reservasLiberadas} reservas de stock");

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }
}
