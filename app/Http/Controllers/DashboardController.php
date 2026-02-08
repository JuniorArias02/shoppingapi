<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        // ... (existing admin stats logic)
        // 1. Total Productos
        $totalProducts = Producto::count();

        // 2. Precio Promedio (Usando variantes si existen, o fallback a lógica custom si fuera necesario)
        // Se asume que el precio real de venta está en ProductoVariante
        $avgPrice = ProductoVariante::avg('precio') ?? 0;

        // 3. Ventas del Mes
        $monthlySales = Pedido::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        
        // Calcular tendencia de ventas (comparado con mes anterior)
        $lastMonthSales = Pedido::whereMonth('created_at', Carbon::now()->subMonth()->month)
             ->whereYear('created_at', Carbon::now()->subMonth()->year)
             ->count();

        $salesTrend = 0;
        if ($lastMonthSales > 0) {
            $salesTrend = (($monthlySales - $lastMonthSales) / $lastMonthSales) * 100;
        } elseif ($monthlySales > 0) {
            $salesTrend = 100; // Crecimiento total si antes era 0
        }

        // 4. Actividad (Usuarios activos en los últimos 30 días)
        $totalUsers = User::count();
        $activeUsers = User::where('last_login_at', '>=', Carbon::now()->subDays(30))->count();
        
        $activityPercentage = $totalUsers > 0 ? ($activeUsers / $totalUsers) * 100 : 0;

        return response()->json([
            'total_products' => $totalProducts,
            'avg_price' => round($avgPrice, 2),
            'monthly_sales' => $monthlySales,
            'sales_trend' => round($salesTrend, 1),
            'activity_percentage' => round($activityPercentage, 1),
            'active_users' => $activeUsers,
            'total_users' => $totalUsers
        ]);
    }

    /**
     * Get client specific dashboard statistics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clientStats(Request $request)
    {
        $user = $request->user();

        // Pending Orders: Visto, Empacado, Pagado, Pendiente, Procesando
        // Excludes: Enviado (now considered completed), Entregado, Cancelado, Reembolsado
        $pendingOrders = Pedido::where('usuario_id', $user->id)
            ->whereIn('estado', [
                Pedido::ESTADO_PENDIENTE, 
                Pedido::ESTADO_PAGADO, 
                Pedido::ESTADO_VISTO, 
                Pedido::ESTADO_EMPACADO, 
                Pedido::ESTADO_PROCESANDO
            ])
            ->count();

        // Completed Orders: Enviado + Entregado
        $completedOrders = Pedido::where('usuario_id', $user->id)
            ->whereIn('estado', [
                Pedido::ESTADO_ENVIADO, 
                Pedido::ESTADO_ENTREGADO
            ])
            ->count();

        // Favorites Count
        $favorites = \App\Models\ProductLike::where('usuario_id', $user->id)->count();

        return response()->json([
            'pending_orders' => $pendingOrders,
            'completed_orders' => $completedOrders,
            'favorites' => $favorites
        ]);
    }
}
