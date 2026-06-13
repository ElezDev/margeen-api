<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::query()
            ->forCompany($request->user()->company_id)
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
        $product = Product::query()->create([
            ...$request->validated(),
            'company_id' => $request->user()->company_id,
            'unit' => $request->validated('unit') ?? 'unidad',
            'is_active' => $request->validated('is_active') ?? true,
        ]);

        return response()->json([
            'message' => 'Producto creado.',
            'data' => ProductResource::make($product),
        ], 201);
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

        $product->update($request->validated());

        return response()->json([
            'message' => 'Producto actualizado.',
            'data' => ProductResource::make($product),
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
        if ($product->company_id !== $request->user()->company_id) {
            abort(404);
        }
    }
}
