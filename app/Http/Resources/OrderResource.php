<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'subtotal_amount' => (float) $this->subtotal_amount,
            'delivery_fee' => (float) $this->delivery_fee,
            'total_amount' => (float) $this->total_amount,
            'platform_commission' => (float) $this->platform_commission,
            'delivery_address' => $this->delivery_address,
            'customer_phone' => $this->customer_phone,
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'delivery_instructions' => $this->delivery_instructions,
            'notes' => $this->notes,
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $this->store?->id,
                'name' => $this->store?->name,
                'address' => $this->store?->address,
            ]),
            'customer' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ]),
            'rider' => $this->whenLoaded('rider', fn () => [
                'id' => $this->rider?->id,
                'name' => $this->rider?->name,
            ]),
            'items' => $this->relationLoaded('items')
                ? OrderItemResource::collection($this->items)->resolve()
                : [],
            'timeline' => $this->relationLoaded('timeline')
                ? OrderTimelineResource::collection($this->timeline)->resolve()
                : [],
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
