<template>
  <Head :title="`Order #${order.id}`" />

  <AdminLayout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Order #{{ order.id }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Current status: {{ order.status }}</p>
          </div>
          <Link :href="`/admin/orders/${order.id}/edit`" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
            Update Status
          </Link>
        </div>
      </header>

      <section class="grid gap-4 rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl md:grid-cols-2 dark:border-slate-700/45 dark:bg-slate-900/60">
        <p><span class="font-semibold">Store:</span> {{ order.store?.name || 'N/A' }}</p>
        <p><span class="font-semibold">Customer:</span> {{ order.customer?.name || 'N/A' }}</p>
        <p><span class="font-semibold">Rider:</span> {{ order.rider?.name || 'Unassigned' }}</p>
        <p><span class="font-semibold">Payment:</span> {{ order.payment_method }} ({{ order.payment_status }})</p>
        <p><span class="font-semibold">Delivery Fee:</span> ${{ Number(order.delivery_fee).toFixed(2) }}</p>
        <p><span class="font-semibold">Total:</span> ${{ Number(order.total_amount).toFixed(2) }}</p>
        <p class="md:col-span-2"><span class="font-semibold">Address:</span> {{ order.delivery_address }}</p>
      </section>

      <section class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Items</h3>
        <ul class="space-y-2 text-sm text-slate-700 dark:text-slate-300">
          <li v-for="item in order.items" :key="item.id" class="flex items-center justify-between rounded-xl border border-indigo-100/70 px-3 py-2 dark:border-slate-700/50">
            <span>{{ item.product_name }}</span>
            <span>Qty: {{ item.quantity }}</span>
          </li>
          <li v-if="order.items.length === 0" class="text-slate-500 dark:text-slate-400">No items found.</li>
        </ul>
      </section>

      <section class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Timeline</h3>
        <ul class="space-y-2 text-sm text-slate-700 dark:text-slate-300">
          <li v-for="entry in order.timeline" :key="entry.id" class="rounded-xl border border-indigo-100/70 px-3 py-2 dark:border-slate-700/50">
            {{ entry.status }} - {{ entry.note || 'No note' }}
          </li>
          <li v-if="order.timeline.length === 0" class="text-slate-500 dark:text-slate-400">No timeline entries yet.</li>
        </ul>
      </section>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Shared/AdminLayout.vue';

defineProps({
  order: {
    type: Object,
    required: true,
  },
});
</script>
