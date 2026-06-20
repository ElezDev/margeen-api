<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportSpreadsheetRequest;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Services\MeasurementUnitService;
use App\Services\ProductImportService;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductImportService $productImportService,
        private readonly MeasurementUnitService $measurementUnitService
    ) {}
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::query()
            ->forCompany(Tenant::companyId($request))
            ->with('measurementUnit')
            ->orderBy('name');

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where('name', 'like', $term);
        }

        return ProductResource::collection(
            $query->paginate($request->integer('per_page', 20))
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $companyId = Tenant::companyId($request);
        $unit = $this->resolveUnit($companyId, $validated);

        $product = Product::query()->create([
            'name' => $validated['name'],
            'unit_id' => $unit->id,
            'unit' => $unit->name,
            'cost_price' => $validated['cost_price'],
            'sale_price' => $validated['sale_price'],
            'company_id' => $companyId,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Producto creado.',
            'data' => ProductResource::make($product->load('measurementUnit')),
        ], 201);
    }

    public function importTemplate(Request $request): BinaryFileResponse
    {
        $this->authorize('create', Product::class);

        return response()
            ->download($this->productImportService->templatePath(), 'plantilla-productos.xlsx')
            ->deleteFileAfterSend();
    }

    public function import(ImportSpreadsheetRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $result = $this->productImportService->import(
            Tenant::companyId($request),
            $request->file('file')
        );

        return response()->json([
            'message' => 'Importación de productos procesada.',
            'data' => $result,
        ]);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $this->ensureSameCompany($request, $product);
        $this->authorize('view', $product);

        return response()->json([
            'data' => ProductResource::make($product),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->ensureSameCompany($request, $product);

        $validated = $request->validated();

        if (isset($validated['unit_id']) || isset($validated['unit'])) {
            $unit = $this->resolveUnit(Tenant::companyId($request), $validated);
            $validated['unit_id'] = $unit->id;
            $validated['unit'] = $unit->name;
        }

        $product->update($validated);

        return response()->json([
            'message' => 'Producto actualizado.',
            'data' => ProductResource::make($product->fresh('measurementUnit')),
        ]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->ensureSameCompany($request, $product);
        $this->authorize('delete', $product);

        if ($product->invoiceItems()->exists()) {
            $product->update(['is_active' => false]);

            return response()->json([
                'message' => 'Producto desactivado (tiene facturas asociadas).',
                'data' => ProductResource::make($product->fresh()),
            ]);
        }

        $product->delete();

        return response()->json([
            'message' => 'Producto eliminado.',
        ]);
    }

    private function ensureSameCompany(Request $request, Product $product): void
    {
        if (! Tenant::belongsToTenant($product, $request)) {
            abort(404);
        }
    }

    private function findUnit(int $companyId, int $unitId): MeasurementUnit
    {
        return MeasurementUnit::query()
            ->forCompany($companyId)
            ->whereKey($unitId)
            ->firstOrFail();
    }

    private function resolveUnit(int $companyId, array $validated): MeasurementUnit
    {
        if (! empty($validated['unit_id'])) {
            return $this->findUnit($companyId, (int) $validated['unit_id']);
        }

        return $this->measurementUnitService->resolveOrCreate(
            $companyId,
            $validated['unit'] ?? 'Unidad'
        );
    }
}
