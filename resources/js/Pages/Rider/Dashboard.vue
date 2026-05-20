<template>
  <Head title="Rider Dashboard" />

  <Layout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm">
        <h2 class="text-2xl font-semibold text-slate-900">Rider Dashboard</h2>
        <p class="text-sm text-slate-600">Track your active deliveries and assigned orders.</p>
      </header>

      <section class="grid gap-4 md:grid-cols-2">
        <article class="rounded-2xl border border-indigo-100/90 bg-white/95 p-4 shadow-sm">
          <p class="text-sm text-slate-500">Active Deliveries</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">{{ metrics.active_deliveries }}</p>
        </article>
        <article class="rounded-2xl border border-indigo-100/90 bg-white/95 p-4 shadow-sm">
          <p class="text-sm text-slate-500">Completed Today</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">{{ metrics.completed_today }}</p>
        </article>
      </section>

      <section class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">Assigned Orders</h3>

        <div v-if="assignedOrders.length === 0" class="rounded-xl border border-indigo-100 bg-white/80 p-4 text-sm text-slate-600">
          No assigned orders yet.
        </div>

        <article
          v-for="order in assignedOrders"
          :key="order.id"
          class="mb-2 rounded-xl border border-indigo-100/80 px-3 py-3 last:mb-0"
        >
          <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="font-semibold text-slate-900">Order #{{ order.id }} - {{ order.store_name || 'Store' }}</p>
            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ order.status }}</span>
          </div>
          <p class="mt-1 text-sm text-slate-600">Customer: {{ order.customer_name || 'N/A' }}</p>
          <p class="text-sm text-slate-600">Address: {{ order.delivery_address }}</p>
          <p class="text-sm text-slate-600">Phone: {{ order.customer_phone || 'N/A' }}</p>
          <p class="text-sm text-slate-700">Total: ${{ Number(order.total_amount).toFixed(2) }}</p>
          <div v-if="order.rider_rating || order.order_rating" class="mt-2 flex flex-wrap items-center gap-2 text-xs">
            <span
              v-if="order.rider_rating"
              class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 font-semibold text-amber-700"
            >
              Rider: {{ stars(order.rider_rating) }}
            </span>
            <span
              v-if="order.order_rating"
              class="rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 font-semibold text-indigo-700"
            >
              Order: {{ stars(order.order_rating) }}
            </span>
            <span v-if="order.rating_has_comment" class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-700">
              Comment available
            </span>
          </div>
          <div class="mt-3">
            <Link
              :href="`/rider/orders/${order.id}`"
              class="inline-flex rounded-lg border border-indigo-200 bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-indigo-700 transition hover:bg-indigo-50"
            >
              Open Order
            </Link>
          </div>
          <div v-if="order.next_status_options?.length" class="mt-3 flex flex-wrap gap-2">
            <button
              v-for="nextStatus in order.next_status_options"
              :key="`${order.id}-${nextStatus.value}`"
              type="button"
              class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-indigo-700 transition hover:border-indigo-300 hover:bg-indigo-100"
              @click="updateOrderStatus(order.id, nextStatus.value)"
            >
              Mark {{ nextStatus.label }}
            </button>
          </div>
        </article>
      </section>
    </div>
  </Layout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import Layout from '@/Shared/Layout.vue';

defineProps({
  metrics: {
    type: Object,
    required: true,
  },
  assignedOrders: {
    type: Array,
    default: () => [],
  },
});

const updateOrderStatus = (orderId, status) => {
  router.patch(`/rider/orders/${orderId}/status`, { status }, { preserveScroll: true });
};

const stars = (value) => '★'.repeat(Number(value || 0)) + '☆'.repeat(Math.max(0, 5 - Number(value || 0)));
</script>
