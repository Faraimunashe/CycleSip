<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryStoreRequest extends FormRequest
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
        $store = $this->route('store');

        return [
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
                Rule::unique('store_products', 'product_id')->where(
                    fn ($query) => $query->where('store_id', $store?->id),
                ),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'is_available' => ['required', 'boolean'],
            'promotion_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'promotion_ends_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.unique' => 'This product is already in the store inventory.',
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
