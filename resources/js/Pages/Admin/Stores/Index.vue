<template>
  <Head title="Store Management" />

  <AdminLayout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/90 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Store Management</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">Manage store entities through dedicated create, view, and edit pages.</p>
          </div>
          <Link href="/admin/stores/create" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
            New Store
          </Link>
        </div>
      </header>

      <div class="grid gap-3 rounded-2xl border border-indigo-100/90 bg-white/90 p-4 shadow-sm backdrop-blur-xl md:grid-cols-[1fr_auto] dark:border-slate-700/45 dark:bg-slate-900/55">
        <input
          v-model="search"
          type="text"
          placeholder="Search stores by name..."
          class="rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"
          @keyup.enter="applySearch"
        >
        <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" @click="applySearch">
          Search
        </button>
      </div>

      <div class="grid gap-3 md:grid-cols-2">
        <article
          v-for="store in stores.data"
          :key="store.id"
          class="rounded-2xl border border-indigo-100/90 bg-white/90 p-4 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3">
              <EntityImage :image-url="store.logo_url" :alt="`${store.name} logo`" placeholder-label="Store logo" />
              <div>
                <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ store.name }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ store.slug }}</p>
              </div>
            </div>
            <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="store.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
              {{ store.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>

          <div class="mt-2 grid gap-2 text-sm text-slate-600 md:grid-cols-3 dark:text-slate-300">
            <p>{{ store.address }}</p>
            <p>{{ store.phone || 'No phone' }}</p>
            <p>Commission: {{ store.commission_rate }}%</p>
            <p>Hours: {{ store.opening_time || '--:--' }} - {{ store.closing_time || '--:--' }}</p>
            <p>Zones: {{ store.zones?.length || 0 }}</p>
          </div>

          <div class="mt-4 flex items-center gap-2">
            <Link :href="`/admin/stores/${store.id}`" class="rounded-lg border border-indigo-100 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
              View
            </Link>
            <Link :href="`/admin/stores/${store.id}/edit`" class="rounded-lg border border-indigo-100 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
              Edit
            </Link>
          </div>
        </article>
      </div>

      <p v-if="stores.data.length === 0" class="rounded-2xl border border-indigo-100/90 bg-white/90 p-5 text-sm text-slate-600 dark:border-slate-700/45 dark:bg-slate-900/55 dark:text-slate-400">
        No stores found.
      </p>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import EntityImage from '@/Components/Admin/EntityImage.vue';
import AdminLayout from '@/Shared/AdminLayout.vue';

const props = defineProps({
  stores: {
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
  router.get('/admin/stores', { search: search.value || undefined }, {
    preserveState: true,
    preserveScroll: true,
  });
};
</script>
