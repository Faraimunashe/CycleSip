<template>
  <section class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60">
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
      <div>
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Inventory</h3>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
          Add products to this store, set pricing, stock levels, and availability.
        </p>
      </div>
      <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">
        {{ inventory.length }} item{{ inventory.length === 1 ? '' : 's' }}
      </span>
    </div>

    <form
      v-if="availableProducts.length"
      class="mb-5 grid gap-3 rounded-xl border border-dashed border-indigo-200 bg-indigo-50/40 p-4 dark:border-slate-700 dark:bg-slate-950/30"
      @submit.prevent="submitAdd"
    >
      <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">Add product to inventory</p>

      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div class="md:col-span-2">
          <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Product</label>
          <select
            v-model="addForm.product_id"
            class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"
          >
            <option value="">Select a product</option>
            <option v-for="product in availableProducts" :key="product.id" :value="product.id">
              {{ product.name }}<template v-if="product.brand"> · {{ product.brand }}</template>
            </option>
          </select>
          <p v-if="addForm.errors.product_id" class="mt-1 text-xs text-rose-600">{{ addForm.errors.product_id }}</p>
        </div>

        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Price ($)</label>
          <input
            v-model.number="addForm.price"
            type="number"
            min="0"
            step="0.01"
            class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"
          >
          <p v-if="addForm.errors.price" class="mt-1 text-xs text-rose-600">{{ addForm.errors.price }}</p>
        </div>

        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Stock</label>
          <input
            v-model.number="addForm.stock_quantity"
            type="number"
            min="0"
            class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"
          >
          <p v-if="addForm.errors.stock_quantity" class="mt-1 text-xs text-rose-600">{{ addForm.errors.stock_quantity }}</p>
        </div>

        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Promo price ($)</label>
          <input
            v-model.number="addForm.promotion_price"
            type="number"
            min="0"
            step="0.01"
            class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"
          >
          <p v-if="addForm.errors.promotion_price" class="mt-1 text-xs text-rose-600">{{ addForm.errors.promotion_price }}</p>
        </div>

        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Promo ends</label>
          <input
            v-model="addForm.promotion_ends_at"
            type="datetime-local"
            class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"
          >
          <p v-if="addForm.errors.promotion_ends_at" class="mt-1 text-xs text-rose-600">{{ addForm.errors.promotion_ends_at }}</p>
        </div>
      </div>

      <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
        <input v-model="addForm.is_available" type="checkbox" class="rounded border-slate-300">
        Available for sale
      </label>

      <div class="flex justify-end">
        <button
          type="submit"
          class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
          :disabled="addForm.processing || !addForm.product_id"
        >
          {{ addForm.processing ? 'Adding...' : 'Add to inventory' }}
        </button>
      </div>
    </form>

    <p v-else class="mb-5 text-sm text-slate-500 dark:text-slate-400">
      All active catalog products are already assigned to this store.
      <Link href="/admin/products/create" class="font-semibold text-indigo-700 hover:underline dark:text-indigo-300">Create a product</Link>
      to add more.
    </p>

    <div v-if="inventory.length" class="space-y-3">
      <article
        v-for="item in inventory"
        :key="item.id"
        class="rounded-xl border border-indigo-100/70 p-4 dark:border-slate-700/50"
      >
        <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
          <div>
            <Link :href="`/admin/products/${item.product_id}`" class="font-semibold text-indigo-700 hover:underline dark:text-indigo-300">
              {{ item.product_name || 'Unknown product' }}
            </Link>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              <span v-if="item.product_brand">{{ item.product_brand }}</span>
              <span v-if="item.product_brand && item.category_name"> · </span>
              <span v-if="item.category_name">{{ item.category_name }}</span>
            </p>
          </div>
          <span
            class="rounded-full px-2.5 py-1 text-xs font-semibold"
            :class="item.is_available ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'"
          >
            {{ item.is_available ? 'Available' : 'Unavailable' }}
          </span>
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Price ($)</label>
            <input
              v-model.number="itemForms[item.id].price"
              type="number"
              min="0"
              step="0.01"
              class="w-full rounded-lg border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"
            >
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Stock</label>
            <input
              v-model.number="itemForms[item.id].stock_quantity"
              type="number"
              min="0"
              class="w-full rounded-lg border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"
            >
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Promo price ($)</label>
            <input
              v-model.number="itemForms[item.id].promotion_price"
              type="number"
              min="0"
              step="0.01"
              class="w-full rounded-lg border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"
            >
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Promo ends</label>
            <input
              v-model="itemForms[item.id].promotion_ends_at"
              type="datetime-local"
              class="w-full rounded-lg border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"
            >
          </div>
          <div class="flex items-end">
            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
              <input v-model="itemForms[item.id].is_available" type="checkbox" class="rounded border-slate-300">
              Available
            </label>
          </div>
        </div>

        <p v-if="itemForms[item.id]?.errors?.price" class="mt-2 text-xs text-rose-600">{{ itemForms[item.id].errors.price }}</p>
        <p v-if="itemForms[item.id]?.errors?.stock_quantity" class="mt-1 text-xs text-rose-600">{{ itemForms[item.id].errors.stock_quantity }}</p>

        <div class="mt-3 flex flex-wrap justify-end gap-2">
          <button
            type="button"
            class="rounded-xl border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50 dark:border-rose-500/30 dark:hover:bg-rose-500/10"
            :disabled="itemForms[item.id].processing"
            @click="removeItem(item.id)"
          >
            Remove
          </button>
          <button
            type="button"
            class="rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
            :disabled="itemForms[item.id].processing"
            @click="saveItem(item.id)"
          >
            {{ itemForms[item.id].processing ? 'Saving...' : 'Save changes' }}
          </button>
        </div>
      </article>
    </div>

    <p v-else class="text-sm text-slate-500 dark:text-slate-400">No products in this store yet. Add one above.</p>
  </section>
</template>

<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

const props = defineProps({
  storeId: {
    type: Number,
    required: true,
  },
  inventory: {
    type: Array,
    default: () => [],
  },
  availableProducts: {
    type: Array,
    default: () => [],
  },
});

const addForm = useForm({
  product_id: '',
  price: 0,
  stock_quantity: 0,
  is_available: true,
  promotion_price: null,
  promotion_ends_at: '',
});

const itemForms = reactive({});

function buildItemForm(item) {
  return useForm({
    price: item.price,
    stock_quantity: item.stock_quantity,
    is_available: item.is_available,
    promotion_price: item.promotion_price,
    promotion_ends_at: item.promotion_ends_at || '',
  });
}

function syncItemForms() {
  props.inventory.forEach(item => {
    if (!itemForms[item.id]) {
      itemForms[item.id] = buildItemForm(item);
      return;
    }

    itemForms[item.id].price = item.price;
    itemForms[item.id].stock_quantity = item.stock_quantity;
    itemForms[item.id].is_available = item.is_available;
    itemForms[item.id].promotion_price = item.promotion_price;
    itemForms[item.id].promotion_ends_at = item.promotion_ends_at || '';
  });

  Object.keys(itemForms).forEach(id => {
    if (!props.inventory.some(item => String(item.id) === String(id))) {
      delete itemForms[id];
    }
  });
}

watch(
  () => props.inventory,
  () => syncItemForms(),
  { immediate: true, deep: true },
);

function submitAdd() {
  addForm.post(`/admin/stores/${props.storeId}/inventory`, {
    preserveScroll: true,
    onSuccess: () => {
      addForm.reset();
      addForm.is_available = true;
    },
  });
}

function saveItem(itemId) {
  itemForms[itemId].patch(`/admin/stores/${props.storeId}/inventory/${itemId}`, {
    preserveScroll: true,
  });
}

function removeItem(itemId) {
  if (!window.confirm('Remove this product from the store inventory?')) {
    return;
  }

  router.delete(`/admin/stores/${props.storeId}/inventory/${itemId}`, {
    preserveScroll: true,
  });
}
</script>
