<template>
  <Head :title="rider.user.name" />

  <AdminLayout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ rider.user.name }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ rider.user.email }} · {{ rider.user.phone || 'No phone' }}</p>
          </div>
          <Link :href="`/admin/riders/${rider.id}/edit`" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
            Update Approval
          </Link>
        </div>
      </header>

      <section class="grid gap-4 rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl md:grid-cols-2 dark:border-slate-700/45 dark:bg-slate-900/60">
        <p><span class="font-semibold">Approval:</span> {{ rider.approval_status }}</p>
        <p><span class="font-semibold">Online:</span> {{ rider.is_online ? 'Yes' : 'No' }}</p>
        <p><span class="font-semibold">Acceptance:</span> {{ rider.acceptance_rate }}%</p>
        <p><span class="font-semibold">Cancellation:</span> {{ rider.cancellation_rate }}%</p>
        <p><span class="font-semibold">Completed:</span> {{ rider.completed_deliveries }}</p>
        <p><span class="font-semibold">Bike:</span> {{ rider.bicycle_model || 'Not set' }}</p>
        <p><span class="font-semibold">License:</span> {{ rider.license_number || 'Not set' }}</p>
        <p><span class="font-semibold">Emergency:</span> {{ rider.emergency_contact_name || 'N/A' }} {{ rider.emergency_contact_phone || '' }}</p>
        <p class="md:col-span-2"><span class="font-semibold">Zones:</span> {{ rider.zones.map(zone => zone.name).join(', ') || 'Not assigned' }}</p>
      </section>

      <section class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Documents</h3>
        <ul class="space-y-2 text-sm text-slate-700 dark:text-slate-300">
          <li v-for="document in rider.documents" :key="document.id" class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-indigo-100/70 px-3 py-2 dark:border-slate-700/50">
            <span>{{ document.document_type }}</span>
            <span class="text-xs">{{ document.status }}</span>
          </li>
          <li v-if="rider.documents.length === 0" class="text-slate-500 dark:text-slate-400">No documents uploaded.</li>
        </ul>
      </section>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Shared/AdminLayout.vue';

defineProps({
  rider: {
    type: Object,
    required: true,
  },
});
</script>
