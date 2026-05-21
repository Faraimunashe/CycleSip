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

      <form class="grid gap-4 rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60" @submit.prevent="submitItemAdjustments">
        <div>
          <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Adjust Ordered Items</h3>
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">You can reduce quantities or remove items. Increasing quantity is not allowed.</p>
        </div>

        <div class="space-y-2">
          <article
            v-for="item in itemAdjustments"
            :key="item.item_id"
            class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-indigo-100/70 px-3 py-2 dark:border-slate-700/50"
          >
            <div>
              <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ item.product_name || `Item #${item.item_id}` }}</p>
              <p class="text-xs text-slate-500 dark:text-slate-400">Original qty: {{ item.original_quantity }}</p>
            </div>

            <div class="flex items-center gap-2">
              <input
                v-model.number="item.quantity"
                type="number"
                min="0"
                :max="item.original_quantity"
                class="w-20 rounded-lg border border-slate-300 px-2 py-1 text-sm dark:border-slate-700 dark:bg-slate-950/70"
              >
              <button
                type="button"
                class="rounded border border-rose-200 px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50"
                @click="item.quantity = 0"
              >
                Remove
              </button>
            </div>
          </article>
        </div>

        <p v-if="itemForm.errors.items" class="text-xs text-rose-600">{{ itemForm.errors.items }}</p>

        <div class="flex items-center justify-end">
          <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" :disabled="itemForm.processing">
            {{ itemForm.processing ? 'Applying...' : 'Apply Item Adjustments' }}
          </button>
        </div>
      </form>

      <section class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Timeline Preview</h3>
        <ul v-if="order.timeline?.length" class="space-y-3">
          <li
            v-for="entry in order.timeline"
            :key="entry.id"
            class="relative rounded-xl border border-indigo-100/70 bg-white/80 px-4 py-3 pl-10 text-sm dark:border-slate-700/50 dark:bg-slate-900/30"
          >
            <span class="absolute left-4 top-4 h-2.5 w-2.5 rounded-full bg-indigo-500" />
            <span class="absolute left-[20px] top-7 h-[calc(100%-22px)] w-px bg-indigo-100" />
            <div class="flex flex-wrap items-center justify-between gap-2">
              <p class="font-semibold text-slate-800 dark:text-slate-100">{{ formatStatus(entry.status) }}</p>
              <p class="text-xs text-slate-500">{{ formatDate(entry.created_at) }}</p>
            </div>
            <p class="mt-1 text-slate-600 dark:text-slate-300">{{ entry.note || 'No note' }}</p>
            <p v-if="entry.changed_by_name" class="mt-1 text-xs text-slate-500">By: {{ entry.changed_by_name }}</p>
          </li>
        </ul>
        <p v-else class="text-sm text-slate-500 dark:text-slate-400">No timeline entries yet.</p>
      </section>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';
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

const itemAdjustments = reactive(
  (props.order.items || []).map(item => ({
    item_id: item.id,
    product_name: item.product_name,
    original_quantity: item.quantity,
    quantity: item.quantity,
  })),
);

const itemForm = useForm({
  items: [],
});

const submit = () => {
  form.patch(`/admin/orders/${props.order.id}/status`);
};

const submitItemAdjustments = () => {
  itemForm.items = itemAdjustments.map(item => ({
    item_id: item.item_id,
    quantity: Math.max(0, Math.min(Number(item.quantity || 0), Number(item.original_quantity))),
  }));

  itemForm.patch(`/admin/orders/${props.order.id}/items`);
};

const formatStatus = (value) => value ? value.replaceAll('_', ' ') : '-';
const formatDate = (value) => value ? new Date(value).toLocaleString() : 'N/A';
</script>
