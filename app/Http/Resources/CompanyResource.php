<?php

namespace App\Http\Resources;

use App\Support\CompanyLogoStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'document' => $this->document,
            'phone' => $this->phone,
            'address' => $this->address,
            'logo_path' => $this->logo_path,
            'logo_url' => CompanyLogoStorage::url($this->resource),
            'default_margin_percent' => $this->default_margin_percent,
            'invoice_prefix' => $this->invoice_prefix,
            'next_invoice_number' => $this->next_invoice_number,
        ];
    }
}
