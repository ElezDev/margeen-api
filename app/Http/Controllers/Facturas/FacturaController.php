<?php

namespace App\Http\Controllers\Facturas;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class FacturaController extends Controller
{
   
    public function index(Request $request)
    {
        $query = Invoice::with(['client', 'seller']);

        if ($request->has('client_id') && !empty($request->input('client_id'))) {
            $query->where('client_id', $request->input('client_id'));
        }

        if ($request->has('status') && !empty($request->input('status'))) {
            $query->where('status', $request->input('status'));
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->latest()->get()
        ], 200);
    }

  
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $user = auth()->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'No autenticado.'], 401);
        }

        return DB::transaction(function () use ($request, $user) {
            
            $company = DB::table('companies')->where('id', $user->company_id)->first();
            
            $invoiceNumber = $company->invoice_prefix . '-' . $company->next_invoice_number;

            $subtotal = 0;
            $totalCost = 0;
            $itemsData = [];

            foreach ($request->input('items') as $item) {
                $product = Product::find($item['product_id']);
                
                $quantity = $item['quantity'];
                $unitPrice = $product->sale_price;
                $unitCost = $product->cost_price;

                $lineTotal = $quantity * $unitPrice;
                $lineCost = $quantity * $unitCost;
                $lineProfit = $lineTotal - $lineCost; // 

                $subtotal += $lineTotal;
                $totalCost += $lineCost;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'quantity' => $quantity,
                    'unit' => $product->unit,
                    'unit_price' => $unitPrice,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                    'line_profit' => $lineProfit,
                ];
            }

            $discount = $request->input('discount', 0.00);
            $total = $subtotal - $discount;
            $totalProfit = $total - $totalCost; // 

            $invoice = Invoice::create([
                'company_id' => $user->company_id,
                'client_id' => $request->client_id,
                'user_id' => $user->id,
                'number' => $invoiceNumber,
                'status' => 'draft', // 
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'total_cost' => $totalCost,
                'total_profit' => $totalProfit,
                'notes' => $request->input('notes'),
                'issued_at' => now(),
            ]);

            foreach ($itemsData as $item) {
                $invoice->items()->create($item);
            }

            DB::table('companies')
                ->where('id', $user->company_id)
                ->increment('next_invoice_number');

            return response()->json([
                'status' => 'success',
                'message' => 'Factura generada y calculada correctamente.',
                'data' => $invoice->load('items')
            ], 201);
        });
    }

  
    public function show($id)
    {
        $invoice = Invoice::with(['client', 'seller', 'items'])->find($id);

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Factura no encontrada o no pertenece a tu empresa.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $invoice
        ], 200);
    }

   
    public function destroy($id)
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Factura no encontrada.'
            ], 404);
        }

        $invoice->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Factura eliminada correctamente.'
        ], 200);
    }

   
    public function generatePdf($id)
    {
        $invoice = Invoice::with(['client', 'seller', 'items'])->find($id);

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Factura no encontrada o no pertenece a tu empresa.'
            ], 404);
        }

        $pdf = Pdf::loadView('facturas.pdf', compact('invoice'));

        return $pdf->stream('factura_' . $invoice->number . '.pdf');
    }

  
    public function updateStatus(Request $request, $id)
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Factura no encontrada.'
            ], 404);
        }

        
        $request->validate([
            'status' => 'required|string|in:draft,paid,cancelled', 
        ]);

        $invoice->update([
            'status' => $request->input('status')
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'El estado de la factura ha sido actualizado a: ' . $request->input('status'),
            'data' => $invoice
        ], 200);
    }
}