<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryZoneUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-zones');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:140',
                Rule::unique('delivery_zones', 'slug')->ignore($this->route('zone')),
            ],
            'center_latitude' => ['required', 'numeric', 'between:-90,90'],
            'center_longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['required', 'numeric', 'min:0.1', 'max:50'],
            'base_delivery_fee' => ['required', 'numeric', 'min:0'],
            'distance_surcharge_per_km' => ['required', 'numeric', 'min:0'],
            'estimated_minutes' => ['required', 'integer', 'min:5', 'max:180'],
            'is_active' => ['required', 'boolean'],
            'store_ids' => ['nullable', 'array'],
            'store_ids.*' => ['integer', 'exists:stores,id'],
        ];
    }
}
