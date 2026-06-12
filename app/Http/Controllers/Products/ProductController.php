<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->has('q') && !empty($request->q)) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        if ($request->input('active_only') == '1') {
            $query->where('is_active', true);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get()
        ], 200);
    }

 
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:255',
            'cost_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean'
        ]);

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'No autenticado. Por favor inicia sesión en Postman.'
            ], 401);
        }

        $product = Product::create([
            'company_id' => $user->company_id, 
            'name' => $request->name,
            'unit' => $request->input('unit', 'unidad'),
            'cost_price' => $request->input('cost_price', 0.00),
            'sale_price' => $request->input('sale_price', 0.00),
            'is_active' => $request->input('is_active', true)
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Producto creado correctamente.',
            'data' => $product
        ], 201);
    }
    
    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Producto no encontrado o no pertenece a tu empresa.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $product
        ], 200);
    }

   
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Producto no encontrado.'
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'unit' => 'nullable|string|max:255',
            'cost_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean'
        ]);

        $product->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Producto actualizado correctamente.',
            'data' => $product
        ], 200);
    }

    
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Producto no encontrado.'
            ], 404);
        }

        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Producto eliminado correctamente.'
        ], 200);
    }
}