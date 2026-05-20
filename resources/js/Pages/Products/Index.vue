<template>
  <Head title="Products" />

  <Layout>
    <div class="mb-6">
      <h2 class="text-2xl font-semibold">Products</h2>
      <p class="text-sm text-slate-600">Create a cash order from one store at a time.</p>
    </div>

    <div v-if="stores.length === 0" class="glass rounded-2xl p-5 text-slate-600 shadow-sm">
      No active stores with stock found.
    </div>

    <div class="space-y-6">
      <div
        v-for="store in stores"
        :key="store.id"
        class="glass rounded-2xl p-5 shadow-sm"
      >
        <div class="mb-4 flex items-center gap-3">
          <EntityImage
            :image-url="store.logo_url"
            :alt="`${store.name} logo`"
            placeholder-label="Store logo"
            img-class="h-12 w-12 rounded-xl border border-indigo-100 object-cover"
            fallback-class="flex h-12 w-12 flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-indigo-200 bg-indigo-50/70"
          />
          <div>
            <h3 class="text-lg font-semibold">{{ store.name }}</h3>
            <p class="text-sm text-slate-600">{{ store.address || 'Address unavailable' }}</p>
          </div>
        </div>

        <div class="space-y-3">
          <div
            v-for="item in store.inventory"
            :key="item.id"
            class="grid grid-cols-[1fr_auto] items-center gap-3 rounded border border-slate-100 p-3"
          >
            <div class="flex items-start gap-3">
              <EntityImage
                :image-url="item.image_url"
                :alt="`${item.product_name} image`"
                placeholder-label="Product"
                img-class="h-12 w-12 rounded-xl border border-indigo-100 object-cover"
                fallback-class="flex h-12 w-12 flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-indigo-200 bg-indigo-50/70"
              />
              <div>
                <p class="font-medium">{{ item.product_name }}</p>
                <p class="text-sm text-slate-600">{{ item.description }}</p>
                <p class="text-sm text-slate-700">${{ item.price.toFixed(2) }} | Stock: {{ item.stock_quantity }}</p>
              </div>
            </div>
            <input
              v-model.number="quantities[item.id]"
              min="0"
              :max="item.stock_quantity"
              type="number"
              class="w-20 rounded-xl border border-slate-300/80 bg-white/80 px-2 py-1 dark:border-slate-700 dark:bg-slate-950/70"
            />
          </div>
        </div>

        <form class="mt-4 space-y-3 rounded-2xl border border-white/50 bg-white/45 p-4 backdrop-blur-lg dark:border-slate-700/45 dark:bg-slate-900/35" @submit.prevent="submitOrder(store)">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Delivery address</label>
            <input
              v-model="deliveryAddress"
              type="text"
              class="w-full rounded-xl border border-slate-300/80 bg-white/80 px-3 py-2 dark:border-slate-700 dark:bg-slate-950/70"
              placeholder="e.g. 10 Campus Lane, Room 2"
            />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Notes (optional)</label>
            <textarea
              v-model="notes"
              rows="2"
              class="w-full rounded-xl border border-slate-300/80 bg-white/80 px-3 py-2 dark:border-slate-700 dark:bg-slate-950/70"
            />
          </div>
          <p v-if="form.errors.items" class="text-sm text-red-600">{{ form.errors.items }}</p>
          <button
            type="submit"
            class="cursor-pointer rounded bg-slate-900 px-4 py-2 text-white hover:bg-slate-700"
            :disabled="form.processing"
          >
            {{ form.processing ? 'Placing order...' : 'Place cash order' }}
          </button>
        </form>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import EntityImage from '@/Components/Admin/EntityImage.vue';
import Layout from '@/Shared/Layout.vue';

const props = defineProps({
  stores: {
    type: Array,
    default: () => [],
  },
});

const quantities = ref({});
const deliveryAddress = ref('');
const notes = ref('');

const form = useForm({
  store_id: null,
  delivery_address: '',
  notes: '',
  payment_method: 'cash',
  items: [],
});

const submitOrder = (store) => {
  const selectedItems = store.inventory
    .map((inventoryItem) => ({
      store_product_id: inventoryItem.id,
      quantity: Number(quantities.value[inventoryItem.id] || 0),
    }))
    .filter((item) => item.quantity > 0);

  form.store_id = store.id;
  form.delivery_address = deliveryAddress.value;
  form.notes = notes.value;
  form.payment_method = 'cash';
  form.items = selectedItems;

  form.post('/orders', {
    onSuccess: () => {
      selectedItems.forEach((item) => {
        quantities.value[item.store_product_id] = 0;
      });
      notes.value = '';
    },
  });
};
</script>
