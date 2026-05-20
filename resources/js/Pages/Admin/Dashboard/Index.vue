<template>
  <Head title="Admin Dashboard" />

  <AdminLayout>
    <div class="space-y-6">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/90 p-6 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-700 dark:text-indigo-300">CycleSip Operations</p>
        <h2 class="mt-2 text-3xl font-semibold text-slate-900 dark:text-slate-100">Control Hub Dashboard</h2>
        <p class="mt-1 max-w-2xl text-sm text-slate-600 dark:text-slate-400">Real-time visibility across orders, rider fleet, revenue streams, and zone performance.</p>
      </header>

      <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <SummaryCard label="Total Orders Today" :value="analytics.summary.total_orders_today" :icon="ClipboardList" tone="indigo" caption="Orders created in the last 24 hours" />
        <SummaryCard label="Total Revenue" :value="currency(analytics.summary.total_revenue)" :icon="Wallet" tone="emerald" caption="Gross marketplace revenue" />
        <SummaryCard label="Active Riders" :value="analytics.summary.active_riders" :icon="Bike" tone="sky" caption="Approved riders in the fleet" />
        <SummaryCard label="Online Riders" :value="analytics.summary.online_riders" :icon="Signal" tone="violet" caption="Live riders available for dispatch" />
        <SummaryCard label="Pending Orders" :value="analytics.summary.pending_orders" :icon="Clock3" tone="amber" caption="Orders waiting for progression" />
        <SummaryCard label="Completed Orders" :value="analytics.summary.completed_orders" :icon="CheckCheck" tone="emerald" caption="Orders fully completed" />
        <SummaryCard label="Cancelled Orders" :value="analytics.summary.cancelled_orders" :icon="CircleX" tone="rose" caption="Cancelled orders requiring review" />
        <SummaryCard label="Stores Count" :value="analytics.summary.stores_count" :icon="Store" tone="indigo" caption="Partner stores onboarded" />
        <SummaryCard label="Customers Count" :value="analytics.summary.customers_count" :icon="Users" tone="sky" caption="Registered customer base" />
      </section>

      <section class="grid gap-4 xl:grid-cols-2">
        <BarChartCard
          title="Daily Sales (Revenue)"
          subtitle="Last 14 days"
          :items="analytics.charts.daily_sales.map(day => ({ label: day.date, value: day.revenue }))"
        />
        <BarChartCard
          title="Weekly Order Trends"
          subtitle="Rolling 8-week volume"
          :items="analytics.charts.weekly_orders.map(week => ({ label: `Week ${week.week}`, value: Number(week.total) }))"
        />
        <BarChartCard
          title="Rider Performance (Deliveries)"
          subtitle="Top dispatch performers"
          :items="analytics.charts.rider_performance.map(rider => ({ label: rider.name, value: rider.completed_deliveries }))"
        />
        <BarChartCard
          title="Most Active Delivery Zones"
          subtitle="Zone demand distribution"
          :items="analytics.charts.top_zones.map(zone => ({ label: zone.name, value: zone.orders }))"
        />
        <BarChartCard
          title="Top-Selling Products"
          subtitle="Best sellers by quantity"
          :items="analytics.charts.top_products.map(product => ({ label: product.name, value: product.total_qty }))"
        />
        <BarChartCard
          title="Peak Ordering Hours"
          subtitle="Highest traffic times"
          :items="analytics.charts.peak_hours.map(item => ({ label: item.hour, value: item.orders }))"
        />
      </section>

      <section class="grid gap-4 xl:grid-cols-2">
        <BarChartCard
          title="Revenue Breakdown"
          subtitle="Payment channel contribution"
          :items="analytics.charts.revenue_breakdown"
        />
        <ActivityFeed :activities="liveFeed.items" />
      </section>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { reactive } from 'vue';
import {
  Bike,
  CheckCheck,
  CircleX,
  ClipboardList,
  Clock3,
  Signal,
  Store,
  Users,
  Wallet,
} from '@lucide/vue';
import ActivityFeed from '@/Components/Admin/ActivityFeed.vue';
import BarChartCard from '@/Components/Admin/BarChartCard.vue';
import SummaryCard from '@/Components/Admin/SummaryCard.vue';
import { useOpsRealtime } from '@/composables/useOpsRealtime';
import AdminLayout from '@/Shared/AdminLayout.vue';

const props = defineProps({
  analytics: {
    type: Object,
    required: true,
  },
});

const liveFeed = reactive({
  items: props.analytics.activity_feed || [],
});

useOpsRealtime({
  onOrderStatusChanged: payload => {
    liveFeed.items.unshift({
      id: `order-${payload.order_id}-${Date.now()}`,
      event: `Order #${payload.order_id} moved to ${payload.to_status}`,
      created_at: new Date().toISOString(),
    });
    liveFeed.items = liveFeed.items.slice(0, 12);
  },
  onRiderStatusUpdated: payload => {
    liveFeed.items.unshift({
      id: `rider-${payload.rider_profile_id}-${Date.now()}`,
      event: `Rider #${payload.rider_profile_id} is now ${payload.is_online ? 'online' : 'offline'}`,
      created_at: new Date().toISOString(),
    });
    liveFeed.items = liveFeed.items.slice(0, 12);
  },
});

const currency = value =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 2 }).format(Number(value || 0));
</script>
