<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-stores');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'logo_url' => ['nullable', 'url', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:140',
                Rule::unique('stores', 'slug')->ignore($this->route('store')),
            ],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'opening_time' => ['nullable', 'date_format:H:i'],
            'closing_time' => ['nullable', 'date_format:H:i'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
