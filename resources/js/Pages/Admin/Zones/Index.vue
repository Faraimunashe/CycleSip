<template>
  <Head title="Zone Management" />

  <AdminLayout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/90 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Delivery Zone Management</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">Manage zones with dedicated create, view, and edit pages.</p>
          </div>
          <Link href="/admin/zones/create" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
            New Zone
          </Link>
        </div>
      </header>

      <div class="grid gap-3 rounded-2xl border border-indigo-100/90 bg-white/90 p-4 shadow-sm backdrop-blur-xl md:grid-cols-[1fr_auto] dark:border-slate-700/45 dark:bg-slate-900/55">
        <input
          v-model="search"
          type="text"
          placeholder="Search zones by name..."
          class="rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"
          @keyup.enter="applySearch"
        >
        <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" @click="applySearch">
          Search
        </button>
      </div>

      <div class="grid gap-3 md:grid-cols-2">
        <article
          v-for="zone in zones.data"
          :key="zone.id"
          class="rounded-2xl border border-indigo-100/90 bg-white/90 p-4 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55"
        >
          <div class="flex items-center justify-between gap-3">
            <div>
              <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ zone.name }}</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">{{ zone.slug }}</p>
            </div>
            <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="zone.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
              {{ zone.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>

          <div class="mt-2 grid gap-2 text-sm text-slate-600 md:grid-cols-3 dark:text-slate-300">
            <p>Radius: {{ zone.radius_km }} km</p>
            <p>Base Fee: ${{ Number(zone.base_delivery_fee).toFixed(2) }}</p>
            <p>Surcharge / km: ${{ Number(zone.distance_surcharge_per_km).toFixed(2) }}</p>
            <p>ETA: {{ zone.estimated_minutes }} min</p>
            <p>Stores: {{ zone.stores_count }}</p>
            <p>Riders: {{ zone.rider_profiles_count }}</p>
          </div>

          <div class="mt-4 flex items-center gap-2">
            <Link :href="`/admin/zones/${zone.id}`" class="rounded-lg border border-indigo-100 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
              View
            </Link>
            <Link :href="`/admin/zones/${zone.id}/edit`" class="rounded-lg border border-indigo-100 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
              Edit
            </Link>
          </div>
        </article>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Shared/AdminLayout.vue';

const props = defineProps({
  zones: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const search = ref(props.filters.search || '');

const applySearch = () => {
  router.get('/admin/zones', { search: search.value || undefined }, {
    preserveState: true,
    preserveScroll: true,
  });
};
</script>
