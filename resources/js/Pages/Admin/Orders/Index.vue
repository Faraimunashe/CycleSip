<template>
  <Head title="Admin Orders" />

  <AdminLayout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm dark:border-slate-700/45 dark:bg-slate-900/60">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Operations Desk</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900 dark:text-slate-100">Order Command</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Live monitoring, rider assignment visibility, and status workflow control.</p>
          </div>
          <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300">
            <ClipboardList class="h-5 w-5" />
          </div>
        </div>
      </header>

      <form class="grid gap-3 rounded-2xl border border-indigo-100/90 bg-white/95 p-4 shadow-sm md:grid-cols-3 dark:border-slate-700/45 dark:bg-slate-900/60" @submit.prevent="applyFilters">
        <input
          v-model="filterForm.search"
          type="text"
          placeholder="Search by ID, address, or customer"
          class="rounded border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"
        />
        <select
          v-model="filterForm.status"
          class="rounded border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"
        >
          <option value="">All statuses</option>
          <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
        </select>
        <button class="cursor-pointer rounded-xl bg-indigo-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-indigo-500">
          <span class="inline-flex items-center gap-2">
            <Search class="h-4 w-4" />
            Apply Filters
          </span>
        </button>
      </form>

      <div v-if="orders.data.length === 0" class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 text-slate-600 dark:border-slate-700/45 dark:bg-slate-900/60 dark:text-slate-400">
        No orders found for the selected filters.
      </div>

      <article
        v-for="order in orders.data"
        :key="order.id"
        class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm transition hover:shadow-md dark:border-slate-700/45 dark:bg-slate-900/60"
      >
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
          <h3 class="font-semibold text-slate-900 dark:text-slate-100">
            Order #{{ order.id }} - {{ order.store?.name || 'Unknown Store' }}
          </h3>
          <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">
            {{ order.status }}
          </span>
        </div>

        <div class="grid gap-2 text-sm text-slate-600 md:grid-cols-2 dark:text-slate-300">
          <p class="inline-flex items-center gap-2"><User class="h-4 w-4 text-slate-400" /> Customer: {{ order.customer?.name || '-' }}</p>
          <p class="inline-flex items-center gap-2"><Bike class="h-4 w-4 text-slate-400" /> Rider: {{ order.rider?.name || 'Unassigned' }}</p>
          <p class="inline-flex items-center gap-2"><Wallet class="h-4 w-4 text-slate-400" /> Payment: {{ order.payment_method }} ({{ order.payment_status }})</p>
          <p class="inline-flex items-center gap-2"><CircleDollarSign class="h-4 w-4 text-slate-400" /> Total: ${{ Number(order.total_amount).toFixed(2) }}</p>
          <p class="inline-flex items-center gap-2 md:col-span-2"><MapPin class="h-4 w-4 text-slate-400" /> Address: {{ order.delivery_address }}</p>
        </div>

        <ul class="mt-3 list-disc pl-5 text-sm text-slate-700 dark:text-slate-300">
          <li v-for="item in order.items" :key="`${order.id}-${item.id}`">
            {{ item.product_name }} x {{ item.quantity }}
          </li>
        </ul>

        <div class="mt-4 rounded-xl border border-slate-100 p-3 dark:border-slate-800">
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Timeline</p>
          <ul class="space-y-2 text-sm text-slate-700 dark:text-slate-300">
            <li v-for="entry in order.timeline" :key="entry.id">
              {{ entry.status }} - {{ entry.note || 'No note' }}
            </li>
            <li v-if="order.timeline.length === 0" class="text-slate-500 dark:text-slate-400">No timeline entries yet.</li>
          </ul>
        </div>

        <div class="mt-4 flex items-center gap-2">
          <Link :href="`/admin/orders/${order.id}`" class="rounded-lg border border-indigo-100 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
            View
          </Link>
          <Link :href="`/admin/orders/${order.id}/edit`" class="rounded-lg border border-indigo-100 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
            Update Status
          </Link>
        </div>
      </article>

      <div class="flex flex-wrap items-center gap-2 text-sm">
        <Link
          v-for="link in orders.links"
          :key="`${link.url}-${link.label}`"
          :href="link.url || '#'"
          class="rounded border px-3 py-1.5"
          :class="[
            link.active
              ? 'border-indigo-600 bg-indigo-600 text-white'
              : 'border-slate-300 text-slate-700 dark:border-slate-700 dark:text-slate-300',
            !link.url ? 'pointer-events-none opacity-40' : '',
          ]"
          v-html="link.label"
        />
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import {
  Bike,
  CircleDollarSign,
  ClipboardList,
  MapPin,
  Search,
  User,
  Wallet,
} from '@lucide/vue';
import AdminLayout from '@/Shared/AdminLayout.vue';

const props = defineProps({
  orders: {
    type: Object,
    required: true,
  },
  statuses: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const filterForm = reactive({
  search: props.filters.search || '',
  status: props.filters.status || '',
});

const applyFilters = () => {
  router.get('/admin/orders', filterForm, {
    preserveState: true,
    preserveScroll: true,
  });
};
</script>
