<template>
  <Head title="Customer Management" />

  <AdminLayout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/90 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55">
        <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Customer Management</h2>
        <p class="text-sm text-slate-600 dark:text-slate-400">Profiles, age verification, identity review, and order volumes.</p>
      </header>

      <div class="overflow-x-auto rounded-2xl border border-indigo-100/90 bg-white/90 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-left dark:bg-slate-800/70">
            <tr>
              <th class="px-4 py-3 font-semibold">Name</th>
              <th class="px-4 py-3 font-semibold">Email</th>
              <th class="px-4 py-3 font-semibold">DOB</th>
              <th class="px-4 py-3 font-semibold">Email Verified</th>
              <th class="px-4 py-3 font-semibold">Age Verified</th>
              <th class="px-4 py-3 font-semibold">ID Status</th>
              <th class="px-4 py-3 font-semibold">Orders</th>
              <th class="px-4 py-3 font-semibold">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="customer in customers.data"
              :key="customer.id"
              class="border-t border-slate-100 align-top dark:border-slate-800"
            >
              <td class="px-4 py-3">{{ customer.name }}</td>
              <td class="px-4 py-3">{{ customer.email }}</td>
              <td class="px-4 py-3">{{ customer.date_of_birth || 'N/A' }}</td>
              <td class="px-4 py-3">{{ customer.email_verified_at ? 'Yes' : 'No' }}</td>
              <td class="px-4 py-3">{{ customer.age_verified_at ? 'Yes' : 'No' }}</td>
              <td class="px-4 py-3">
                <span v-if="!customer.identity_document" class="text-slate-500">Not submitted</span>
                <span v-else class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold uppercase text-indigo-700">
                  {{ customer.identity_document.status }}
                </span>
              </td>
              <td class="px-4 py-3">{{ customer.orders_count }}</td>
              <td class="px-4 py-3">
                <div v-if="customer.identity_document?.status === 'pending'" class="flex flex-col gap-2">
                  <a
                    :href="customer.identity_document.file_url"
                    target="_blank"
                    class="text-xs font-semibold text-indigo-700 hover:underline"
                  >
                    View ID
                  </a>
                  <button
                    type="button"
                    class="rounded border border-emerald-200 px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50"
                    @click="reviewIdentity(customer.id, 'approved')"
                  >
                    Approve
                  </button>
                  <button
                    type="button"
                    class="rounded border border-rose-200 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50"
                    @click="reviewIdentity(customer.id, 'rejected', 'Document could not be verified')"
                  >
                    Reject
                  </button>
                </div>
                <span v-else class="text-xs text-slate-500">—</span>
              </td>
            </tr>
            <tr v-if="customers.data.length === 0">
              <td colspan="8" class="px-4 py-6 text-center text-slate-500 dark:text-slate-400">No customers found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Shared/AdminLayout.vue';

defineProps({
  customers: {
    type: Object,
    required: true,
  },
});

const reviewIdentity = (customerId, status, rejectionReason = null) => {
  router.patch(`/admin/customers/${customerId}/identity`, {
    status,
    rejection_reason: rejectionReason,
  }, { preserveScroll: true });
};
</script>
