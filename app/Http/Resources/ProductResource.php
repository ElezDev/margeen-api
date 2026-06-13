<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'unit' => $this->unit,
            'cost_price' => $this->cost_price,
            'sale_price' => $this->sale_price,
            'is_active' => $this->is_active,
        ];
    }
}
