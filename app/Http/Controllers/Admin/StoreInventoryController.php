<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInventoryStoreRequest;
use App\Http\Requests\Admin\StoreInventoryUpdateRequest;
use App\Models\Store;
use App\Models\StoreProduct;
use Illuminate\Http\RedirectResponse;

class StoreInventoryController extends Controller
{
    public function store(StoreInventoryStoreRequest $request, Store $store): RedirectResponse
    {
        $store->inventory()->create($request->validated());

        return to_route('admin.stores.show', $store)->with('success', 'Product added to store inventory.');
    }

    public function update(
        StoreInventoryUpdateRequest $request,
        Store $store,
        StoreProduct $storeProduct,
    ): RedirectResponse {
        $this->ensureInventoryBelongsToStore($store, $storeProduct);

        $storeProduct->update($request->validated());

        return to_route('admin.stores.show', $store)->with('success', 'Inventory item updated.');
    }

    public function destroy(Store $store, StoreProduct $storeProduct): RedirectResponse
    {
        $this->ensureInventoryBelongsToStore($store, $storeProduct);

        $storeProduct->delete();

        return to_route('admin.stores.show', $store)->with('success', 'Product removed from store inventory.');
    }

    private function ensureInventoryBelongsToStore(Store $store, StoreProduct $storeProduct): void
    {
        abort_unless((int) $storeProduct->store_id === (int) $store->id, 404);
    }
}
