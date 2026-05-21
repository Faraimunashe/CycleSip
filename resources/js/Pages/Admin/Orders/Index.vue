<template>
  <Head title="Admin Orders" />

  <AdminLayout>
    <div class="space-y-5">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Operations Desk</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Order Command</h2>
            <p class="mt-1 text-sm text-slate-600">Live monitoring, rider assignment visibility, and workflow controls.</p>
          </div>
          <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
            <ClipboardList class="h-5 w-5" />
          </div>
        </div>
      </header>

      <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        <article class="rounded-2xl border border-indigo-100 bg-white/95 p-4 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Showing</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">{{ orders.data.length }}</p>
          <p class="mt-1 text-xs text-slate-500">Current page orders</p>
        </article>
        <article class="rounded-2xl border border-amber-100 bg-white/95 p-4 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pending</p>
          <p class="mt-1 text-2xl font-semibold text-amber-700">{{ pendingCount }}</p>
          <p class="mt-1 text-xs text-slate-500">Need action</p>
        </article>
        <article class="rounded-2xl border border-cyan-100 bg-white/95 p-4 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">In Transit</p>
          <p class="mt-1 text-2xl font-semibold text-cyan-700">{{ inTransitCount }}</p>
          <p class="mt-1 text-xs text-slate-500">Active rider work</p>
        </article>
        <article class="rounded-2xl border border-emerald-100 bg-white/95 p-4 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Completed</p>
          <p class="mt-1 text-2xl font-semibold text-emerald-700">{{ completedCount }}</p>
          <p class="mt-1 text-xs text-slate-500">Finished orders</p>
        </article>
        <article class="rounded-2xl border border-indigo-100 bg-white/95 p-4 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Page Revenue</p>
          <p class="mt-1 text-2xl font-semibold text-indigo-700">${{ pageRevenue.toFixed(2) }}</p>
          <p class="mt-1 text-xs text-slate-500">Total amount sum</p>
        </article>
      </section>

      <form class="grid gap-3 rounded-2xl border border-indigo-100/90 bg-white/95 p-4 shadow-sm md:grid-cols-[1.3fr_1fr_auto_auto]" @submit.prevent="applyFilters">
        <input
          v-model="filterForm.search"
          type="text"
          placeholder="Search by ID, address, or customer"
          class="rounded-xl border border-slate-300 px-3 py-2 text-sm"
        />
        <select
          v-model="filterForm.status"
          class="rounded-xl border border-slate-300 px-3 py-2 text-sm"
        >
          <option value="">All statuses</option>
          <option v-for="status in statuses" :key="status" :value="status">{{ formatStatus(status) }}</option>
        </select>
        <button class="cursor-pointer rounded-xl bg-indigo-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-indigo-500">
          <span class="inline-flex items-center gap-2">
            <Search class="h-4 w-4" />
            Apply Filters
          </span>
        </button>
        <button
          type="button"
          class="cursor-pointer rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
          @click="resetFilters"
        >
          Reset
        </button>
      </form>

      <div v-if="orders.data.length === 0" class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 text-slate-600">
        No orders found for the selected filters.
      </div>

      <section v-else class="grid grid-cols-1 gap-4">
        <article
          v-for="order in orders.data"
          :key="order.id"
          class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm transition hover:shadow-md"
        >
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Order #{{ order.id }}</p>
              <h3 class="mt-1 text-lg font-semibold text-slate-900">{{ order.store?.name || 'Unknown Store' }}</h3>
            </div>
            <span :class="statusClass(order.status)" class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide">
              {{ formatStatus(order.status) }}
            </span>
          </div>

          <div class="mt-4 grid gap-3 rounded-xl border border-indigo-100 bg-indigo-50/40 p-3 text-sm text-slate-700 sm:grid-cols-2">
            <p class="inline-flex items-center gap-1.5"><User class="h-4 w-4 text-slate-400" /> {{ order.customer?.name || 'N/A' }}</p>
            <p class="inline-flex items-center gap-1.5"><Bike class="h-4 w-4 text-slate-400" /> {{ order.rider?.name || 'Unassigned' }}</p>
            <p class="inline-flex items-center gap-1.5"><Wallet class="h-4 w-4 text-slate-400" /> {{ order.payment_method }} ({{ order.payment_status }})</p>
            <p class="inline-flex items-center gap-1.5 font-semibold text-slate-900"><CircleDollarSign class="h-4 w-4 text-indigo-500" /> ${{ Number(order.total_amount).toFixed(2) }}</p>
            <p class="inline-flex items-start gap-1.5 text-slate-600 sm:col-span-2"><MapPin class="mt-0.5 h-4 w-4 text-slate-400" /> {{ order.delivery_address || 'No address provided' }}</p>
          </div>

          <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <section class="rounded-xl border border-slate-100 bg-white p-3">
              <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Items</p>
              <ul v-if="order.items.length" class="space-y-1 text-xs text-slate-700">
                <li v-for="item in order.items" :key="`${order.id}-${item.id}`">
                  {{ item.product_name }} x {{ item.quantity }}
                </li>
              </ul>
              <p v-else class="text-xs text-slate-500">No items found.</p>
            </section>

            <section class="rounded-xl border border-slate-100 bg-white p-3">
              <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Timeline</p>
              <ul v-if="order.timeline.length" class="space-y-2">
                <li
                  v-for="entry in order.timeline.slice(0, 4)"
                  :key="entry.id"
                  class="relative pl-5 text-xs text-slate-700"
                >
                  <span class="absolute left-0 top-1.5 h-2 w-2 rounded-full bg-indigo-500" />
                  <span class="absolute left-[3px] top-3 h-[calc(100%+5px)] w-px bg-indigo-100" />
                  <p class="font-medium text-slate-800">{{ formatStatus(entry.status) }}</p>
                  <p class="text-slate-500">{{ entry.note || 'No note' }}</p>
                </li>
              </ul>
              <p v-else class="text-xs text-slate-500">No timeline entries yet.</p>
            </section>
          </div>

          <div class="mt-4 flex flex-wrap items-center gap-2">
            <Link :href="`/admin/orders/${order.id}`" class="rounded-lg border border-indigo-100 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
              View Order
            </Link>
            <Link :href="`/admin/orders/${order.id}/edit`" class="rounded-lg border border-indigo-100 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
              Update Status
            </Link>
          </div>
        </article>
      </section>

      <div class="flex flex-wrap items-center gap-2 text-sm">
        <Link
          v-for="link in orders.links"
          :key="`${link.url}-${link.label}`"
          :href="link.url || '#'"
          class="rounded border px-3 py-1.5"
          :class="[
            link.active
              ? 'border-indigo-600 bg-indigo-600 text-white'
              : 'border-slate-300 text-slate-700',
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
import { computed, reactive } from 'vue';
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

const pageRevenue = computed(() => props.orders.data.reduce((total, order) => total + Number(order.total_amount || 0), 0));

const pendingStatuses = ['pending', 'broadcast_to_riders', 'accepted_by_rider', 'verifying_stock'];
const inTransitStatuses = ['en_route_to_store', 'collecting_items', 'en_route_to_customer', 'adjusted'];

const pendingCount = computed(() => props.orders.data.filter(order => pendingStatuses.includes(order.status)).length);
const inTransitCount = computed(() => props.orders.data.filter(order => inTransitStatuses.includes(order.status)).length);
const completedCount = computed(() => props.orders.data.filter(order => order.status === 'completed').length);

const applyFilters = () => {
  router.get('/admin/orders', filterForm, {
    preserveState: true,
    preserveScroll: true,
  });
};

const resetFilters = () => {
  filterForm.search = '';
  filterForm.status = '';
  applyFilters();
};

const formatStatus = (value) => value ? value.replaceAll('_', ' ') : '-';

const statusClass = (status) => {
  if (status === 'completed') return 'bg-emerald-50 text-emerald-700';
  if (status === 'cancelled') return 'bg-rose-50 text-rose-700';
  if (status === 'delivered') return 'bg-cyan-50 text-cyan-700';
  if (status === 'en_route_to_customer' || status === 'en_route_to_store') return 'bg-sky-50 text-sky-700';
  if (status === 'verifying_stock' || status === 'collecting_items') return 'bg-amber-50 text-amber-700';
  return 'bg-indigo-50 text-indigo-700';
};
</script>
