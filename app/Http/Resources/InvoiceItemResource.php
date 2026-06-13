<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'unit_price' => $this->unit_price,
            'unit_cost' => $this->unit_cost,
            'line_total' => $this->line_total,
            'line_profit' => $this->line_profit,
            'product' => ProductResource::make($this->whenLoaded('product')),
        ];
    }
}
