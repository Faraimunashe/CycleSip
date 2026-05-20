<template>
  <Head title="Rider Management" />

  <AdminLayout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/90 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55">
        <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Rider Management</h2>
        <p class="text-sm text-slate-600 dark:text-slate-400">Approval workflows, online status, and performance metrics.</p>
      </header>

      <div class="grid gap-3 rounded-2xl border border-indigo-100/90 bg-white/90 p-4 shadow-sm backdrop-blur-xl md:grid-cols-2 dark:border-slate-700/45 dark:bg-slate-900/55">
        <div>
          <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Filter by approval</label>
          <select
            v-model="approvalFilter"
            class="w-full rounded-xl border border-slate-300/80 bg-white/80 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"
            @change="applyFilter"
          >
            <option value="">All</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>
      </div>

      <div class="space-y-3">
        <article
          v-for="rider in riders.data"
          :key="rider.id"
          class="rounded-2xl border border-indigo-100/90 bg-white/90 p-4 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55"
        >
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
              <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ rider.user.name }}</h3>
              <p class="text-sm text-slate-600 dark:text-slate-400">{{ rider.user.email }} · {{ rider.user.phone || 'No phone' }}</p>
            </div>
            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold uppercase text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">{{ rider.approval_status }}</span>
          </div>

          <div class="mt-3 grid gap-2 text-sm text-slate-600 md:grid-cols-3 dark:text-slate-300">
            <p>Online: {{ rider.is_online ? 'Yes' : 'No' }}</p>
            <p>Acceptance: {{ rider.acceptance_rate }}%</p>
            <p>Cancellations: {{ rider.cancellation_rate }}%</p>
            <p>Completed: {{ rider.completed_deliveries }}</p>
            <p>Bike: {{ rider.bicycle_model || 'Not set' }}</p>
            <p>Zones: {{ rider.zones.map(zone => zone.name).join(', ') || 'Not assigned' }}</p>
          </div>

          <div class="mt-3 flex flex-wrap gap-2">
            <Link :href="`/admin/riders/${rider.id}`" class="rounded-lg border border-indigo-100 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
              View
            </Link>
            <Link :href="`/admin/riders/${rider.id}/edit`" class="rounded-lg border border-indigo-100 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
              Update Approval
            </Link>
          </div>
        </article>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Shared/AdminLayout.vue';

const props = defineProps({
  riders: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const approvalFilter = ref(props.filters.approval_status || '');

const applyFilter = () => {
  router.get('/admin/riders', { approval_status: approvalFilter.value || undefined }, { preserveState: true, preserveScroll: true });
};

</script>
