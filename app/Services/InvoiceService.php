<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoiceService
{
    public function __construct(
        private readonly InvoicePdfService $pdfService
    ) {}

    public function create(User $seller, array $data): Invoice
    {
        return DB::transaction(function () use ($seller, $data) {
            $company = Company::query()->lockForUpdate()->findOrFail($seller->company_id);

            $client = Client::query()
                ->forCompany($company->id)
                ->findOrFail($data['client_id']);

            $number = $this->generateNumber($company);
            $lines = $this->buildLines($company->id, $data['items']);
            $totals = $this->calculateTotals($lines, (float) ($data['discount'] ?? 0));

            $invoice = Invoice::query()->create([
                'company_id' => $company->id,
                'client_id' => $client->id,
                'user_id' => $seller->id,
                'number' => $number,
                'status' => InvoiceStatus::Issued,
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'total' => $totals['total'],
                'total_cost' => $totals['total_cost'],
                'total_profit' => $totals['total_profit'],
                'notes' => $data['notes'] ?? null,
                'issued_at' => now(),
            ]);

            foreach ($lines as $line) {
                $invoice->items()->create($line);
            }

            $company->increment('next_invoice_number');

            $invoice->load(['client', 'seller', 'items.product', 'company']);
            $invoice->update([
                'pdf_path' => $this->pdfService->generate($invoice),
            ]);

            return $invoice->fresh(['client', 'seller', 'items.product', 'company']);
        });
    }

    public function cancel(Invoice $invoice): Invoice
    {
        if ($invoice->status === InvoiceStatus::Cancelled) {
            throw new InvalidArgumentException('La factura ya está cancelada.');
        }

        $invoice->update(['status' => InvoiceStatus::Cancelled]);

        return $invoice->fresh(['client', 'seller', 'items.product', 'company']);
    }

    private function generateNumber(Company $company): string
    {
        return sprintf('%s-%04d', $company->invoice_prefix, $company->next_invoice_number);
    }

    private function buildLines(int $companyId, array $items): array
    {
        $lines = [];

        foreach ($items as $item) {
            $product = null;

            if (! empty($item['product_id'])) {
                $product = Product::query()
                    ->forCompany($companyId)
                    ->where('is_active', true)
                    ->findOrFail($item['product_id']);
            }

            $quantity = (float) $item['quantity'];
            $unitPrice = (float) ($item['unit_price'] ?? $product?->sale_price ?? 0);
            $unitCost = (float) ($item['unit_cost'] ?? $product?->cost_price ?? 0);
            $lineTotal = round($quantity * $unitPrice, 2);
            $lineCost = round($quantity * $unitCost, 2);

            $lines[] = [
                'product_id' => $product?->id,
                'description' => $item['description'] ?? $product?->name ?? 'Producto',
                'quantity' => $quantity,
                'unit' => $item['unit'] ?? $product?->unit ?? 'unidad',
                'unit_price' => $unitPrice,
                'unit_cost' => $unitCost,
                'line_total' => $lineTotal,
                'line_profit' => round($lineTotal - $lineCost, 2),
            ];
        }

        return $lines;
    }

    private function calculateTotals(array $lines, float $discount): array
    {
        $subtotal = round(array_sum(array_column($lines, 'line_total')), 2);
        $totalCost = round(array_sum(array_map(
            fn (array $line) => $line['quantity'] * $line['unit_cost'],
            $lines
        )), 2);
        $discount = round(max(0, $discount), 2);
        $total = round(max(0, $subtotal - $discount), 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'total_cost' => $totalCost,
            'total_profit' => round($total - $totalCost, 2),
        ];
    }
}
