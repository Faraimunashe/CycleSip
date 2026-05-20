<template>
  <Head :title="zone.name" />

  <AdminLayout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ zone.name }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ zone.slug }}</p>
          </div>
          <Link :href="`/admin/zones/${zone.id}/edit`" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
            Edit Zone
          </Link>
        </div>
      </header>

      <section class="grid gap-4 rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl md:grid-cols-2 dark:border-slate-700/45 dark:bg-slate-900/60">
        <p><span class="font-semibold">Latitude:</span> {{ zone.center_latitude }}</p>
        <p><span class="font-semibold">Longitude:</span> {{ zone.center_longitude }}</p>
        <p><span class="font-semibold">Radius:</span> {{ zone.radius_km }} km</p>
        <p><span class="font-semibold">Base Fee:</span> ${{ Number(zone.base_delivery_fee).toFixed(2) }}</p>
        <p><span class="font-semibold">Surcharge/km:</span> ${{ Number(zone.distance_surcharge_per_km).toFixed(2) }}</p>
        <p><span class="font-semibold">ETA:</span> {{ zone.estimated_minutes }} min</p>
        <p><span class="font-semibold">Status:</span> {{ zone.is_active ? 'Active' : 'Inactive' }}</p>
        <p><span class="font-semibold">Orders Served:</span> {{ zone.orders_count }}</p>
        <p><span class="font-semibold">Stores Covered:</span> {{ zone.stores_count }}</p>
        <p><span class="font-semibold">Riders Assigned:</span> {{ zone.rider_profiles_count }}</p>
      </section>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Shared/AdminLayout.vue';

defineProps({
  zone: {
    type: Object,
    required: true,
  },
});
</script>
