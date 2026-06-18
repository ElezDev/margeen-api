<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function dashboard(User $user, ?string $from = null, ?string $to = null): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->startOfMonth();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

        $invoiceQuery = Invoice::query()
            ->forCompany($user->company_id)
            ->where('status', InvoiceStatus::Issued)
            ->whereBetween('issued_at', [$fromDate, $toDate]);

        if (! $user->can('reports.view-all')) {
            $invoiceQuery->where('user_id', $user->id);
        }

        $totals = (clone $invoiceQuery)->selectRaw('
            COUNT(*) as invoice_count,
            COALESCE(SUM(total), 0) as total_sales,
            COALESCE(SUM(total_profit), 0) as total_profit
        ')->first();

        $topClients = (clone $invoiceQuery)
            ->select('client_id', DB::raw('SUM(total) as total_sales'), DB::raw('COUNT(*) as invoice_count'))
            ->groupBy('client_id')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $client = Client::query()->find($row->client_id);

                return [
                    'client_id' => $row->client_id,
                    'client_name' => $client?->name,
                    'total_sales' => number_format((float) $row->total_sales, 2, '.', ''),
                    'invoice_count' => (int) $row->invoice_count,
                ];
            });

        $invoiceIds = (clone $invoiceQuery)->pluck('id');

        $topProducts = InvoiceItem::query()
            ->whereIn('invoice_id', $invoiceIds)
            ->select(
                'product_id',
                'description',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(line_total) as total_sales'),
                DB::raw('SUM(line_profit) as total_profit')
            )
            ->groupBy('product_id', 'description')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'product_id' => $row->product_id,
                'description' => $row->description,
                'total_quantity' => number_format((float) $row->total_quantity, 2, '.', ''),
                'total_sales' => number_format((float) $row->total_sales, 2, '.', ''),
                'total_profit' => number_format((float) $row->total_profit, 2, '.', ''),
            ]);

        $recentInvoices = (clone $invoiceQuery)
            ->with('client:id,name')
            ->latest('issued_at')
            ->limit(5)
            ->get()
            ->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'client_name' => $invoice->client?->name,
                'total' => $invoice->total,
                'total_profit' => $invoice->total_profit,
                'issued_at' => $invoice->issued_at?->toIso8601String(),
            ]);

        return [
            'period' => [
                'from' => $fromDate->toDateString(),
                'to' => $toDate->toDateString(),
            ],
            'summary' => [
                'invoice_count' => (int) $totals->invoice_count,
                'total_sales' => number_format((float) $totals->total_sales, 2, '.', ''),
                'total_profit' => number_format((float) $totals->total_profit, 2, '.', ''),
                'profit_margin_percent' => (float) $totals->total_sales > 0
                    ? (float) number_format(
                        ((float) $totals->total_profit / (float) $totals->total_sales) * 100,
                        2,
                        '.',
                        ''
                    )
                    : 0.0,
            ],
            'top_clients' => $topClients,
            'top_products' => $topProducts,
            'recent_invoices' => $recentInvoices,
        ];
    }
}
