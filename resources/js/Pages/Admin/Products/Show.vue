<template>
  <Head :title="product.name" />

  <AdminLayout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="flex items-center gap-3">
            <EntityImage :image-url="product.image_url" :alt="`${product.name} image`" placeholder-label="Product image" />
            <div>
              <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ product.name }}</h2>
              <p class="text-sm text-slate-500 dark:text-slate-400">{{ product.slug }}</p>
            </div>
          </div>
          <Link :href="`/admin/products/${product.id}/edit`" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
            Edit Product
          </Link>
        </div>
      </header>

      <section class="grid gap-4 rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl md:grid-cols-2 dark:border-slate-700/45 dark:bg-slate-900/60">
        <p><span class="font-semibold">Brand:</span> {{ product.brand || 'N/A' }}</p>
        <p><span class="font-semibold">Category:</span> {{ product.category?.name || 'Uncategorized' }}</p>
        <p><span class="font-semibold">Featured:</span> {{ product.is_featured ? 'Yes' : 'No' }}</p>
        <p><span class="font-semibold">Promoted:</span> {{ product.is_promoted ? 'Yes' : 'No' }}</p>
        <p><span class="font-semibold">Status:</span> {{ product.is_active ? 'Active' : 'Inactive' }}</p>
        <p><span class="font-semibold">Description:</span> {{ product.description || 'No description' }}</p>
      </section>

      <section class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Store Availability</h3>
        <ul class="space-y-2 text-sm text-slate-700 dark:text-slate-300">
          <li v-for="store in product.stores" :key="`${store.store_name}-${store.price}`" class="flex items-center justify-between rounded-xl border border-indigo-100/70 px-3 py-2 dark:border-slate-700/50">
            <span>{{ store.store_name || 'Unknown store' }}</span>
            <span>${{ Number(store.price).toFixed(2) }} | Stock: {{ store.stock_quantity }}</span>
          </li>
          <li v-if="product.stores.length === 0" class="text-slate-500 dark:text-slate-400">No store availability records yet.</li>
        </ul>
      </section>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import EntityImage from '@/Components/Admin/EntityImage.vue';
import AdminLayout from '@/Shared/AdminLayout.vue';

defineProps({
  product: {
    type: Object,
    required: true,
  },
});
</script>
