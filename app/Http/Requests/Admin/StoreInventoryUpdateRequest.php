<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryUpdateRequest extends FormRequest
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
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'is_available' => ['required', 'boolean'],
            'promotion_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'promotion_ends_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'promotion_price.lt' => 'Promotion price must be lower than the regular price.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'promotion_price' => $this->normalizeNullableNumber('promotion_price'),
            'promotion_ends_at' => $this->input('promotion_ends_at') ?: null,
        ]);
    }

    private function normalizeNullableNumber(string $key): mixed
    {
        $value = $this->input($key);

        return $value === '' || $value === null ? null : $value;
    }
}
