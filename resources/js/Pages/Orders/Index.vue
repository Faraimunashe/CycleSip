<template>
  <Head title="My Orders" />

  <Layout>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 class="text-2xl font-semibold">My Orders</h2>
        <p class="text-sm text-slate-600">Track your recent purchases.</p>
      </div>
      <Link href="/checkout" class="rounded-xl border border-indigo-100 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">
        Open checkout
      </Link>
    </div>

    <div v-if="orders.length === 0" class="glass rounded-2xl p-5 text-slate-600 shadow-sm">
      No orders yet. Visit products to place your first order.
    </div>

    <div class="space-y-4">
      <article
        v-for="order in orders"
        :key="order.id"
        class="glass rounded-2xl p-5 shadow-sm"
      >
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
          <h3 class="font-semibold">Order #{{ order.id }} - {{ order.store.name }}</h3>
          <span class="rounded bg-indigo-50 px-2 py-1 text-xs font-medium uppercase text-indigo-700">
            {{ order.status }}
          </span>
        </div>
        <p class="text-sm text-slate-600">Address: {{ order.delivery_address }}</p>
        <p class="text-sm text-slate-600">Payment: {{ order.payment_method }}</p>
        <p class="text-sm text-slate-600">Total: ${{ order.total_amount.toFixed(2) }}</p>
        <div class="mt-3 flex flex-wrap gap-2">
          <Link :href="`/orders/${order.id}`" class="rounded-lg border border-indigo-200 bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-indigo-700 hover:bg-indigo-50">
            Open order
          </Link>
          <span
            v-if="order.can_rate && !order.has_order_rating"
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-emerald-700"
          >
            Rate order pending
          </span>
          <span
            v-if="order.can_rate && !order.has_rider_rating"
            class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-amber-700"
          >
            Rate rider pending
          </span>
        </div>
        <ul class="mt-3 list-disc pl-5 text-sm text-slate-700">
          <li v-for="item in order.items" :key="`${order.id}-${item.name}`">
            {{ item.name }} x {{ item.quantity }} (${{ item.line_total.toFixed(2) }})
          </li>
        </ul>
      </article>
    </div>
  </Layout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Layout from '@/Shared/Layout.vue';

defineProps({
  orders: {
    type: Array,
    default: () => [],
  },
});
</script>
