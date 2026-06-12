<?php

namespace App\Http\Controllers\Clientes; 

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
   
    public function index(Request $request)
    {
        $query = Client::query();

        if ($request->has('q') && !empty($request->input('q'))) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('document', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        $perPage = $request->input('per_page', 20);
        $clients = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $clients->items(),
            'meta' => [
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
            ]
        ], 200);
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255', // 
            'document' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'No autenticado. Por favor inicia sesión en Postman.'
            ], 401);
        }

        $client = Client::create([
            'company_id' => $user->company_id,
            'name' => $request->name,
            'document' => $request->input('document'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Cliente creado correctamente.',
            'data' => $client
        ], 201);
    }

   
    public function show($id)
    {
        $client = Client::find($id);

        if (!$client) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cliente no encontrado o no pertenece a tu empresa.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $client
        ], 200);
    }


    public function update(Request $request, $id)
    {
        $client = Client::find($id);

        if (!$client) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cliente no encontrado.'
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'document' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $client->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Cliente actualizado correctamente.',
            'data' => $client
        ], 200);
    }

 
    public function destroy($id)
    {
        $client = Client::find($id);

        if (!$client) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cliente no encontrado.'
            ], 404);
        }

        if ($client->invoices()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede eliminar el cliente porque tiene facturas asociadas.'
            ], 400);
        }

        $client->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Cliente eliminado correctamente.'
        ], 200);
    }
}