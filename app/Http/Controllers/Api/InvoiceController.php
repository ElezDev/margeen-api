<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoices\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Support\Tenant;
use App\Services\InvoicePdfService;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly InvoicePdfService $pdfService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Invoice::class);

        $query = Invoice::query()
            ->forCompany(Tenant::companyId($request))
            ->with(['client', 'seller', 'items.product'])
            ->orderByDesc('issued_at');

        if (! $request->user()->isAdmin() && ! Tenant::isOverride($request)) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->integer('client_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('from')) {
            $query->whereDate('issued_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('issued_at', '<=', $request->date('to'));
        }

        return InvoiceResource::collection(
            $query->paginate($request->integer('per_page', 20))
        );
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->invoiceService->create(
            $request->user(),
            $request->validated(),
            Tenant::companyId($request),
        );

        return response()->json([
            'message' => 'Factura creada.',
            'data' => InvoiceResource::make($invoice),
        ], 201);
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $this->ensureSameCompany($request, $invoice);
        $this->authorize('view', $invoice);

        $invoice->load(['client', 'seller', 'items.product']);

        return response()->json([
            'data' => InvoiceResource::make($invoice),
        ]);
    }

    public function pdf(Request $request, Invoice $invoice): Response
    {
        $this->ensureSameCompany($request, $invoice);
        $this->authorize('downloadPdf', $invoice);

        $contents = $this->pdfService->contents($invoice);

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$invoice->number.'.pdf"',
        ]);
    }

    public function cancel(Request $request, Invoice $invoice): JsonResponse
    {
        $this->ensureSameCompany($request, $invoice);
        $this->authorize('cancel', $invoice);

        $invoice = $this->invoiceService->cancel($invoice);
        $invoice->load(['client', 'seller', 'items.product']);

        return response()->json([
            'message' => 'Factura cancelada.',
            'data' => InvoiceResource::make($invoice),
        ]);
    }

    private function ensureSameCompany(Request $request, Invoice $invoice): void
    {
        if (! Tenant::belongsToTenant($invoice, $request)) {
            abort(404);
        }
    }
}
