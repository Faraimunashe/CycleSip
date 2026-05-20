<template>
  <Head title="Finance & Payments" />

  <AdminLayout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/90 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55">
        <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Finance & Payments</h2>
        <p class="text-sm text-slate-600 dark:text-slate-400">Transactions, rider payouts, commissions, and settlement oversight.</p>
      </header>

      <section class="grid gap-4 md:grid-cols-3">
        <SummaryCard label="Gross Revenue" :value="currency(summary.gross_revenue)" :icon="CircleDollarSign" tone="emerald" />
        <SummaryCard label="Pending Payouts" :value="currency(summary.pending_payouts)" :icon="Clock3" tone="amber" />
        <SummaryCard label="Settled Rider Earnings" :value="currency(summary.settled_rider_earnings)" :icon="Wallet" tone="indigo" />
      </section>

      <section class="grid gap-4 xl:grid-cols-2">
        <BarChartCard
          title="Payment Method Breakdown"
          :items="payment_breakdown.map(item => ({ label: item.method.toUpperCase(), value: Number(item.total) }))"
        />
        <article class="rounded-2xl border border-indigo-100/90 bg-white/90 p-4 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55">
          <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Recent Payouts</h3>
          <ul class="mt-3 space-y-2 text-sm text-slate-700 dark:text-slate-300">
            <li v-for="payout in payouts" :key="payout.id">
              {{ payout.reference || `Payout #${payout.id}` }} · {{ payout.status }} · {{ currency(payout.amount) }}
            </li>
          </ul>
        </article>
      </section>

      <article class="overflow-x-auto rounded-2xl border border-indigo-100/90 bg-white/90 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-left dark:bg-slate-800/70">
            <tr>
              <th class="px-4 py-3 font-semibold">Reference</th>
              <th class="px-4 py-3 font-semibold">Method</th>
              <th class="px-4 py-3 font-semibold">Amount</th>
              <th class="px-4 py-3 font-semibold">Status</th>
              <th class="px-4 py-3 font-semibold">Created</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="transaction in transactions.data" :key="transaction.id" class="border-t border-slate-100 dark:border-slate-800">
              <td class="px-4 py-3">{{ transaction.reference }}</td>
              <td class="px-4 py-3">{{ transaction.method }}</td>
              <td class="px-4 py-3">{{ currency(transaction.amount) }}</td>
              <td class="px-4 py-3">{{ transaction.status }}</td>
              <td class="px-4 py-3">{{ new Date(transaction.created_at).toLocaleString() }}</td>
            </tr>
          </tbody>
        </table>
      </article>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { CircleDollarSign, Clock3, Wallet } from '@lucide/vue';
import BarChartCard from '@/Components/Admin/BarChartCard.vue';
import SummaryCard from '@/Components/Admin/SummaryCard.vue';
import AdminLayout from '@/Shared/AdminLayout.vue';

defineProps({
  summary: {
    type: Object,
    required: true,
  },
  transactions: {
    type: Object,
    required: true,
  },
  payouts: {
    type: Array,
    default: () => [],
  },
  payment_breakdown: {
    type: Array,
    default: () => [],
  },
});

const currency = value =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 2 }).format(Number(value || 0));
</script>
