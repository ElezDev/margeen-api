<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profitMargin = (float) $this->total > 0
            ? (float) number_format(
                ((float) $this->total_profit / (float) $this->total) * 100,
                2,
                '.',
                ''
            )
            : 0.0;

        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status->value,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'total' => $this->total,
            'total_cost' => $this->total_cost,
            'total_profit' => $this->total_profit,
            'profit_margin_percent' => $profitMargin,
            'notes' => $this->notes,
            'pdf_path' => $this->pdf_path,
            'pdf_url' => $this->pdf_path
                ? url("/api/invoices/{$this->id}/pdf")
                : null,
            'issued_at' => $this->issued_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'client' => ClientResource::make($this->whenLoaded('client')),
            'seller' => SellerResource::make($this->whenLoaded('seller')),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
