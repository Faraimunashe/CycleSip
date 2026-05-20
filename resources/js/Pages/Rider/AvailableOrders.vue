<template>
  <Head title="Available Orders" />

  <Layout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm">
        <h2 class="text-2xl font-semibold text-slate-900">Available Orders</h2>
        <p class="text-sm text-slate-600">Orders broadcast to riders and ready to be picked.</p>
      </header>

      <div v-if="orders.data.length === 0" class="rounded-2xl border border-indigo-100 bg-white/90 p-5 text-sm text-slate-600 shadow-sm">
        No available orders at the moment.
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

        <div class="mt-3 flex flex-wrap gap-2">
          <Link
            :href="`/rider/orders/${order.id}`"
            class="rounded-xl border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50"
          >
            Open Order
          </Link>
          <button
            type="button"
            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
            @click="accept(order.id)"
          >
            Accept Order
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

const accept = (orderId) => {
  router.patch(`/rider/orders/${orderId}/accept`);
};
</script>
