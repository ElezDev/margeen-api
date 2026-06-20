<?php

namespace App\Services;

use App\Models\Invoice;
use App\Support\CompanyLogoStorage;
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
            'logoDataUri' => $this->logoDataUri($invoice->company),
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
        $path = $this->generate($invoice);

        if ($invoice->pdf_path !== $path) {
            $invoice->update(['pdf_path' => $path]);
        }

        return Storage::disk('local')->get($path);
    }

    private function logoDataUri($company): ?string
    {
        app(PlatformCompanyService::class)->migrateLegacyLogo($company);

        $contents = CompanyLogoStorage::contents($company);

        if (! $contents) {
            return null;
        }

        $mime = CompanyLogoStorage::mimeType($company);

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
