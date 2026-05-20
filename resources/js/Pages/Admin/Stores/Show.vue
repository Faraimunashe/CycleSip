<template>
  <Head :title="store.name" />

  <AdminLayout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="flex items-center gap-3">
            <EntityImage :image-url="store.logo_url" :alt="`${store.name} logo`" placeholder-label="Store logo" />
            <div>
              <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ store.name }}</h2>
              <p class="text-sm text-slate-500 dark:text-slate-400">{{ store.slug }}</p>
            </div>
          </div>
          <Link :href="`/admin/stores/${store.id}/edit`" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
            Edit Store
          </Link>
        </div>
      </header>

      <section class="grid gap-4 rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl md:grid-cols-2 dark:border-slate-700/45 dark:bg-slate-900/60">
        <p><span class="font-semibold">Address:</span> {{ store.address }}</p>
        <p><span class="font-semibold">Phone:</span> {{ store.phone || 'N/A' }}</p>
        <p><span class="font-semibold">Hours:</span> {{ store.opening_time || '--:--' }} - {{ store.closing_time || '--:--' }}</p>
        <p><span class="font-semibold">Commission:</span> {{ store.commission_rate }}%</p>
        <p><span class="font-semibold">Status:</span> {{ store.is_active ? 'Active' : 'Inactive' }}</p>
        <p><span class="font-semibold">Zones:</span> {{ store.zones.map(z => z.name).join(', ') || 'Not assigned' }}</p>
      </section>

      <section class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Inventory</h3>
        <ul class="space-y-2 text-sm text-slate-700 dark:text-slate-300">
          <li v-for="item in store.inventory" :key="item.id" class="flex items-center justify-between rounded-xl border border-indigo-100/70 px-3 py-2 dark:border-slate-700/50">
            <span>{{ item.product_name || 'Unknown product' }}</span>
            <span>Stock: {{ item.stock_quantity }} | ${{ item.price.toFixed(2) }}</span>
          </li>
          <li v-if="store.inventory.length === 0" class="text-slate-500 dark:text-slate-400">No inventory entries.</li>
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
  store: {
    type: Object,
    required: true,
  },
});
</script>
