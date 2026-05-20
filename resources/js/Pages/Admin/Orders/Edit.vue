<template>
  <Head :title="`Update Order #${order.id}`" />

  <AdminLayout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60">
        <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Update Order Status</h2>
        <p class="text-sm text-slate-600 dark:text-slate-400">Apply workflow status changes on a dedicated edit page.</p>
      </header>

      <form class="grid gap-4 rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60" @submit.prevent="submit">
        <p class="text-sm text-slate-600 dark:text-slate-300">Order #{{ order.id }} currently <span class="font-semibold">{{ order.status }}</span></p>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Status</label>
          <select v-model="form.status" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
            <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
          </select>
          <p v-if="form.errors.status" class="mt-1 text-xs text-rose-600">{{ form.errors.status }}</p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Status note</label>
          <textarea v-model="form.note" rows="3" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"></textarea>
          <p v-if="form.errors.note" class="mt-1 text-xs text-rose-600">{{ form.errors.note }}</p>
        </div>

        <div class="flex items-center justify-end gap-2">
          <Link :href="`/admin/orders/${order.id}`" class="rounded-xl border border-indigo-100 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">
            Cancel
          </Link>
          <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" :disabled="form.processing">
            Save Status
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Shared/AdminLayout.vue';

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
  statuses: {
    type: Array,
    default: () => [],
  },
});

const form = useForm({
  status: props.order.status,
  note: '',
});

const submit = () => {
  form.patch(`/admin/orders/${props.order.id}/status`);
};
</script>
