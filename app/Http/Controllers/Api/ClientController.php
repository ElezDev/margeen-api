<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Http\Requests\ImportSpreadsheetRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Services\ClientImportService;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClientController extends Controller
{
    public function __construct(
        private readonly ClientImportService $clientImportService
    ) {}
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Client::class);

        $query = Client::query()
            ->forCompany(Tenant::companyId($request))
            ->orderBy('name');

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($builder) use ($term) {
                $builder->where('name', 'like', $term)
                    ->orWhere('document', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        return ClientResource::collection(
            $query->paginate($request->integer('per_page', 20))
        );
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = Client::query()->create([
            ...$request->validated(),
            'company_id' => Tenant::companyId($request),
        ]);

        return response()->json([
            'message' => 'Cliente creado.',
            'data' => ClientResource::make($client),
        ], 201);
    }

    public function importTemplate(Request $request): BinaryFileResponse
    {
        $this->authorize('create', Client::class);

        return response()
            ->download($this->clientImportService->templatePath(), 'plantilla-clientes.xlsx')
            ->deleteFileAfterSend();
    }

    public function import(ImportSpreadsheetRequest $request): JsonResponse
    {
        $this->authorize('create', Client::class);

        $result = $this->clientImportService->import(
            Tenant::companyId($request),
            $request->file('file')
        );

        return response()->json([
            'message' => 'Importación de clientes procesada.',
            'data' => $result,
        ]);
    }

    public function show(Request $request, Client $client): JsonResponse
    {
        $this->ensureSameCompany($request, $client);
        $this->authorize('view', $client);

        return response()->json([
            'data' => ClientResource::make($client),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        $this->ensureSameCompany($request, $client);

        $client->update($request->validated());

        return response()->json([
            'message' => 'Cliente actualizado.',
            'data' => ClientResource::make($client),
        ]);
    }

    public function destroy(Request $request, Client $client): JsonResponse
    {
        $this->ensureSameCompany($request, $client);
        $this->authorize('delete', $client);

        if ($client->invoices()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un cliente con facturas asociadas.',
            ], 422);
        }

        $client->delete();

        return response()->json([
            'message' => 'Cliente eliminado.',
        ]);
    }

    private function ensureSameCompany(Request $request, Client $client): void
    {
        if (! Tenant::belongsToTenant($client, $request)) {
            abort(404);
        }
    }
}
