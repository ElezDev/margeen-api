<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing(['company', 'client', 'seller', 'items']);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'company' => $invoice->company,
            'client' => $invoice->client,
            'seller' => $invoice->seller,
            'items' => $invoice->items,
        ])->setPaper('letter');

        $path = sprintf(
            'invoices/%d/%s.pdf',
            $invoice->company_id,
            $invoice->number
        );

        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    public function contents(Invoice $invoice): string
    {
        if (! $invoice->pdf_path || ! Storage::disk('local')->exists($invoice->pdf_path)) {
            $path = $this->generate($invoice);
            $invoice->update(['pdf_path' => $path]);
        }

        return Storage::disk('local')->get($invoice->pdf_path);
    }
}
