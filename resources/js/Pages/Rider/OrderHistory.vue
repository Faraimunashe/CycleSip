<template>
  <Head title="Rider Order History" />

  <Layout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm">
        <h2 class="text-2xl font-semibold text-slate-900">Worked-On Orders</h2>
        <p class="text-sm text-slate-600">All orders you have accepted or completed.</p>
      </header>

      <div v-if="orders.data.length === 0" class="rounded-2xl border border-indigo-100 bg-white/90 p-5 text-sm text-slate-600 shadow-sm">
        No worked-on orders yet.
      </div>

      <article
        v-for="order in orders.data"
        :key="order.id"
        class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm"
      >
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h3 class="font-semibold text-slate-900">Order #{{ order.id }} - {{ order.store_name || 'Store' }}</h3>
          <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold uppercase text-indigo-700">{{ order.status }}</span>
        </div>
        <div class="mt-2 space-y-1 text-sm text-slate-600">
          <p>Customer: {{ order.customer_name || 'N/A' }}</p>
          <p>Address: {{ order.delivery_address }}</p>
          <p>Phone: {{ order.customer_phone || 'N/A' }}</p>
          <p class="font-medium text-slate-700">Total: ${{ Number(order.total_amount).toFixed(2) }}</p>
        </div>
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

      <div class="flex flex-wrap items-center gap-2 text-sm">
        <Link
          v-for="link in orders.links"
          :key="`${link.url}-${link.label}`"
          :href="link.url || '#'"
          class="rounded border px-3 py-1.5"
          :class="[
            link.active ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 text-slate-700',
            !link.url ? 'pointer-events-none opacity-40' : '',
          ]"
          v-html="link.label"
        />
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import Layout from '@/Shared/Layout.vue';

defineProps({
  orders: {
    type: Object,
    required: true,
  },
});

const updateOrderStatus = (orderId, status) => {
  router.patch(`/rider/orders/${orderId}/status`, { status }, { preserveScroll: true });
};

const stars = (value) => '★'.repeat(Number(value || 0)) + '☆'.repeat(Math.max(0, 5 - Number(value || 0)));
</script>
