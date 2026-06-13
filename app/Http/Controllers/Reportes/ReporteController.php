<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReporteController extends Controller
{
   
    public function getDashboardStats(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'No autenticado.'], 401);
        }

        $companyId = $user->company_id;

        $request->validate([
            'fecha' => 'nullable|date_format:Y-m-d'
        ]);

        $fechaConsulta = $request->has('fecha') 
            ? Carbon::parse($request->query('fecha')) 
            : Carbon::today();

        $inicioMesConsulta = $fechaConsulta->copy()->startOfMonth();
        $finMesConsulta = $fechaConsulta->copy()->endOfMonth();

        $statsDia = Invoice::where('company_id', $companyId)
            ->where('status', '!=', 'cancelled')
            ->whereDate('issued_at', $fechaConsulta)
            ->select(
                DB::raw('COALESCE(SUM(total), 0) as ventas_dia'),
                DB::raw('COALESCE(SUM(total_profit), 0) as ganancia_dia'),
                DB::raw('COUNT(id) as facturas_dia'),
                DB::raw("COALESCE(SUM(CASE WHEN status = 'draft' THEN total ELSE 0 END), 0) as por_cobrar_dia")
            )
            ->first();

        $statsMes = Invoice::where('company_id', $companyId)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('issued_at', [$inicioMesConsulta, $finMesConsulta])
            ->select(
                DB::raw('COALESCE(SUM(total), 0) as ventas_mes'),
                DB::raw('COALESCE(SUM(total_profit), 0) as ganancia_mes'),
                DB::raw('COUNT(id) as facturas_mes'),
                DB::raw("COALESCE(SUM(CASE WHEN status = 'draft' THEN total ELSE 0 END), 0) as por_cobrar_mes")
            )
            ->first();

        $topClientes = Invoice::where('invoices.company_id', $companyId)
            ->where('invoices.status', '!=', 'cancelled')
            ->whereBetween('invoices.issued_at', [$inicioMesConsulta, $finMesConsulta])
            ->join('clients', 'invoices.client_id', '=', 'clients.id')
            ->select(
                'clients.id as client_id',
                'clients.name as client_name',
                DB::raw('SUM(invoices.total) as total_comprado')
            )
            ->groupBy('clients.id', 'clients.name')
            ->orderBy('total_comprado', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'periodo_consultado' => [
                    'dia' => $fechaConsulta->format('Y-m-d'),
                    'mes_correspondiente' => $fechaConsulta->format('F Y') // 
                ],
                'del_dia' => [
                    'ventas_totales' => (float) $statsDia->ventas_dia,
                    'ganancias_totales' => (float) $statsDia->ganancia_dia,
                    'pendiente_por_cobrar' => (float) $statsDia->por_cobrar_dia,
                    'total_facturas' => (int) $statsDia->facturas_dia,
                ],
                'del_mes_completo' => [
                    'ventas_totales' => (float) $statsMes->ventas_mes,
                    'ganancias_totales' => (float) $statsMes->ganancia_mes,
                    'pendiente_por_cobrar' => (float) $statsMes->por_cobrar_mes,
                    'total_facturas' => (int) $statsMes->facturas_mes,
                ],
                'top_clientes_del_mes' => $topClientes
            ]
        ], 200);
    }

}