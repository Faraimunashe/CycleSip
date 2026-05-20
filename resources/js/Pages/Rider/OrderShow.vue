<template>
  <Head :title="`Order #${order.id}`" />

  <Layout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Rider Order View</p>
            <h2 class="text-2xl font-semibold text-slate-900">Order #{{ order.id }}</h2>
            <p class="text-sm text-slate-600">Manage the order flow and review delivery details.</p>
          </div>
          <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold uppercase text-indigo-700">{{ order.status }}</span>
        </div>
      </header>

      <section class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm">
        <div class="flex flex-wrap items-center gap-2">
          <button
            v-if="order.can_accept"
            type="button"
            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500"
            @click="acceptOrder"
          >
            Accept Order
          </button>
          <button
            v-for="nextStatus in order.next_status_options || []"
            :key="nextStatus.value"
            type="button"
            class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:border-indigo-300 hover:bg-indigo-100"
            @click="updateOrderStatus(nextStatus.value)"
          >
            Mark {{ nextStatus.label }}
          </button>
        </div>
        <p v-if="!order.can_accept && !(order.next_status_options || []).length" class="text-sm text-slate-600">
          No status action available for this order right now.
        </p>
      </section>

      <section class="grid gap-4 md:grid-cols-2">
        <article class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm">
          <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">Customer & Delivery</h3>
          <div class="space-y-2 text-sm text-slate-700">
            <p><span class="font-semibold">Customer:</span> {{ order.customer?.name || 'N/A' }}</p>
            <p><span class="font-semibold">Customer Phone:</span> {{ order.customer_phone || 'N/A' }}</p>
            <p><span class="font-semibold">Recipient:</span> {{ order.recipient_name || order.customer?.name || 'N/A' }}</p>
            <p><span class="font-semibold">Recipient Phone:</span> {{ order.recipient_phone || order.customer_phone || 'N/A' }}</p>
            <p><span class="font-semibold">Address:</span> {{ order.delivery_address || 'N/A' }}</p>
            <p><span class="font-semibold">Instructions:</span> {{ order.delivery_instructions || 'None' }}</p>
          </div>
        </article>

        <article class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm">
          <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">Order Summary</h3>
          <div class="space-y-2 text-sm text-slate-700">
            <p><span class="font-semibold">Store:</span> {{ order.store?.name || 'N/A' }}</p>
            <p><span class="font-semibold">Payment:</span> {{ order.payment_method || 'N/A' }}</p>
            <p><span class="font-semibold">Payment Status:</span> {{ order.payment_status || 'N/A' }}</p>
            <p><span class="font-semibold">Subtotal:</span> ${{ Number(order.subtotal_amount || 0).toFixed(2) }}</p>
            <p><span class="font-semibold">Delivery Fee:</span> ${{ Number(order.delivery_fee || 0).toFixed(2) }}</p>
            <p><span class="font-semibold">Total:</span> ${{ Number(order.total_amount || 0).toFixed(2) }}</p>
          </div>
        </article>
      </section>

      <section class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">Products Bought</h3>
        <div v-if="(order.items || []).length === 0" class="text-sm text-slate-600">
          No products found for this order.
        </div>
        <div v-else class="space-y-2">
          <article
            v-for="item in order.items"
            :key="item.id"
            class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-indigo-100/80 px-3 py-2"
          >
            <div>
              <p class="text-sm font-semibold text-slate-900">{{ item.product_name || `Product #${item.product_id}` }}</p>
              <p class="text-xs text-slate-500">Unit: ${{ Number(item.unit_price || 0).toFixed(2) }}</p>
            </div>
            <div class="text-right text-sm text-slate-700">
              <p>Qty: {{ item.quantity }}</p>
              <p class="font-semibold">${{ Number(item.line_total || 0).toFixed(2) }}</p>
            </div>
          </article>
        </div>
      </section>

      <section class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">Timeline</h3>
        <div v-if="(order.timeline || []).length === 0" class="text-sm text-slate-600">
          No timeline activity yet.
        </div>
        <ul v-else class="space-y-2">
          <li
            v-for="entry in order.timeline"
            :key="entry.id"
            class="rounded-xl border border-indigo-100/80 px-3 py-2"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <p class="text-sm font-semibold text-slate-900">{{ prettyStatus(entry.status) }}</p>
              <p class="text-xs text-slate-500">{{ formatDate(entry.created_at) }}</p>
            </div>
            <p class="text-sm text-slate-600">{{ entry.note || 'No note provided' }}</p>
            <p class="text-xs text-slate-500">By: {{ entry.changed_by_name || 'System' }}</p>
          </li>
        </ul>
      </section>

      <section class="grid gap-4 md:grid-cols-2">
        <article class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm">
          <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">Rider Feedback</h3>
          <div v-if="!order.rider_rating" class="text-sm text-slate-600">
            No rider rating submitted yet.
          </div>
          <div v-else class="space-y-2 text-sm text-slate-700">
            <p><span class="font-semibold">Stars:</span> {{ stars(order.rider_rating.rating) }}</p>
            <p><span class="font-semibold">By:</span> {{ order.rider_rating.reviewer_name || 'Customer' }}</p>
            <p><span class="font-semibold">Comment:</span> {{ order.rider_rating.comment || 'No comment provided.' }}</p>
          </div>
        </article>

        <article class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm">
          <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">Order Feedback</h3>
          <div v-if="!order.order_rating" class="text-sm text-slate-600">
            No order rating submitted yet.
          </div>
          <div v-else class="space-y-2 text-sm text-slate-700">
            <p><span class="font-semibold">Stars:</span> {{ stars(order.order_rating.rating) }}</p>
            <p><span class="font-semibold">By:</span> {{ order.order_rating.reviewer_name || 'Customer' }}</p>
            <p><span class="font-semibold">Comment:</span> {{ order.order_rating.comment || 'No comment provided.' }}</p>
          </div>
        </article>
      </section>
    </div>
  </Layout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import Layout from '@/Shared/Layout.vue';

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
});

const prettyStatus = (status) => status ? status.replaceAll('_', ' ') : 'unknown';

const formatDate = (value) => {
  if (!value) return 'N/A';
  return new Date(value).toLocaleString();
};

const stars = (value) => '★'.repeat(Number(value || 0)) + '☆'.repeat(Math.max(0, 5 - Number(value || 0)));

const acceptOrder = () => {
  router.patch(`/rider/orders/${props.order.id}/accept`);
};

const updateOrderStatus = (status) => {
  router.patch(`/rider/orders/${props.order.id}/status`, { status }, { preserveScroll: true });
};
</script>
