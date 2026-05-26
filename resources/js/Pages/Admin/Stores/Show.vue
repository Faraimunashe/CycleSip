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
        <p>
          <span class="font-semibold">Zones:</span>
          <template v-if="store.zones.length">
            <Link
              v-for="(zone, index) in store.zones"
              :key="zone.id"
              :href="`/admin/zones/${zone.id}`"
              class="font-medium text-indigo-700 hover:underline dark:text-indigo-300"
            >
              {{ zone.name }}<span v-if="index < store.zones.length - 1">, </span>
            </Link>
          </template>
          <span v-else>Not assigned</span>
        </p>
      </section>

      <StoreInventoryPanel
        :store-id="store.id"
        :inventory="store.inventory || []"
        :available-products="availableProducts"
      />
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import EntityImage from '@/Components/Admin/EntityImage.vue';
import StoreInventoryPanel from '@/Components/Admin/StoreInventoryPanel.vue';
import AdminLayout from '@/Shared/AdminLayout.vue';

defineProps({
  store: {
    type: Object,
    required: true,
  },
  availableProducts: {
    type: Array,
    default: () => [],
  },
});
</script>
