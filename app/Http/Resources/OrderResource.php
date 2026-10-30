<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'customer' => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
            ],
            'items' => $this->items->map(fn ($item): array => [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'product_code' => $item->product->code,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax_percentage' => $item->tax_percentage,
                'line_subtotal' => $item->line_subtotal,
                'line_tax' => $item->line_tax,
                'line_total' => $item->line_total,
            ]),
            'subtotal' => $this->subtotal,
            'tax_total' => $this->tax_total,
            'grand_total' => $this->grand_total,
        ];
    }
}
