<template>
  <Head title="My Orders" />

  <Layout>
    <div class="mb-6">
      <h2 class="text-2xl font-semibold">My Orders</h2>
      <p class="text-sm text-slate-600">Track your recent purchases.</p>
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
import { Head } from '@inertiajs/vue3';
import Layout from '@/Shared/Layout.vue';

defineProps({
  orders: {
    type: Array,
    default: () => [],
  },
});
</script>
