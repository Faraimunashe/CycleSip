<template>
  <Head title="Customer Management" />

  <AdminLayout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/90 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55">
        <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Customer Management</h2>
        <p class="text-sm text-slate-600 dark:text-slate-400">Profiles, age verification, order volumes, and support readiness.</p>
      </header>

      <div class="overflow-x-auto rounded-2xl border border-indigo-100/90 bg-white/90 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-left dark:bg-slate-800/70">
            <tr>
              <th class="px-4 py-3 font-semibold">Name</th>
              <th class="px-4 py-3 font-semibold">Email</th>
              <th class="px-4 py-3 font-semibold">Phone</th>
              <th class="px-4 py-3 font-semibold">Age Verified</th>
              <th class="px-4 py-3 font-semibold">Orders</th>
              <th class="px-4 py-3 font-semibold">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="customer in customers.data"
              :key="customer.id"
              class="border-t border-slate-100 dark:border-slate-800"
            >
              <td class="px-4 py-3">{{ customer.name }}</td>
              <td class="px-4 py-3">{{ customer.email }}</td>
              <td class="px-4 py-3">{{ customer.phone || 'N/A' }}</td>
              <td class="px-4 py-3">{{ customer.age_verified_at ? 'Yes' : 'No' }}</td>
              <td class="px-4 py-3">{{ customer.orders_count }}</td>
              <td class="px-4 py-3">{{ customer.status }}</td>
            </tr>
            <tr v-if="customers.data.length === 0">
              <td colspan="6" class="px-4 py-6 text-center text-slate-500 dark:text-slate-400">No customers found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Shared/AdminLayout.vue';

defineProps({
  customers: {
    type: Object,
    required: true,
  },
});
</script>
