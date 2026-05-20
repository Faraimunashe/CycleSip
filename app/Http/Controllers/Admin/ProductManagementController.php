<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductUpsertRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $products = Product::query()
            ->with('category')
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product): array => $this->toProductArray($product));

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Create', [
            'product' => $this->defaultProductPayload(),
            'categories' => ProductCategory::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(ProductUpsertRequest $request): RedirectResponse
    {
        $product = Product::create($this->buildProductPayload($request));

        return to_route('admin.products.show', $product)->with('success', 'Product created successfully.');
    }

    public function show(Product $product): Response
    {
        $product->load(['category', 'storeProducts.store']);

        return Inertia::render('Admin/Products/Show', [
            'product' => $this->toProductArray($product, true),
        ]);
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Admin/Products/Edit', [
            'product' => $this->toProductArray($product),
            'categories' => ProductCategory::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(ProductUpsertRequest $request, Product $product): RedirectResponse
    {
        $product->update($this->buildProductPayload($request, $product));

        return to_route('admin.products.show', $product)->with('success', 'Product updated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function toProductArray(Product $product, bool $withStores = false): array
    {
        $payload = [
            'id' => $product->id,
            'product_category_id' => $product->product_category_id,
            'name' => $product->name,
            'slug' => $product->slug,
            'brand' => $product->brand,
            'description' => $product->description,
            'image_url' => $product->image_url,
            'is_featured' => $product->is_featured,
            'is_promoted' => $product->is_promoted,
            'is_active' => $product->is_active,
            'category' => $product->relationLoaded('category') && $product->category
                ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                ]
                : null,
            'created_at' => optional($product->created_at)?->toIso8601String(),
            'updated_at' => optional($product->updated_at)?->toIso8601String(),
        ];

        if ($withStores && $product->relationLoaded('storeProducts')) {
            $payload['stores'] = $product->storeProducts->map(fn ($storeProduct): array => [
                'store_name' => $storeProduct->store?->name,
                'price' => (float) $storeProduct->price,
                'stock_quantity' => $storeProduct->stock_quantity,
                'is_available' => $storeProduct->is_available,
            ])->values();
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultProductPayload(): array
    {
        return [
            'product_category_id' => null,
            'name' => '',
            'slug' => '',
            'brand' => '',
            'description' => '',
            'image_url' => '',
            'remove_image' => false,
            'is_featured' => false,
            'is_promoted' => false,
            'is_active' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProductPayload(ProductUpsertRequest $request, ?Product $product = null): array
    {
        $payload = $request->safe()->except(['image', 'remove_image']);

        if ($product && $request->boolean('remove_image')) {
            $this->deleteStoredImage($product->image_url);
            $payload['image_url'] = null;
        }

        if ($request->hasFile('image')) {
            if ($product) {
                $this->deleteStoredImage($product->image_url);
            }

            $payload['image_url'] = Storage::url($request->file('image')->store('products', 'public'));
        }

        return $payload;
    }

    private function deleteStoredImage(?string $imageUrl): void
    {
        if (! $imageUrl || ! str_starts_with($imageUrl, '/storage/')) {
            return;
        }

        $path = ltrim(str_replace('/storage/', '', $imageUrl), '/');

        if ($path !== '') {
            Storage::disk('public')->delete($path);
        }
    }
}
