<template>
  <Head title="Payment Methods" />

  <AdminLayout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/90 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55">
        <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Payment Methods</h2>
        <p class="text-sm text-slate-600 dark:text-slate-400">
          Enable or disable checkout options, set timing rules, and control sort order.
        </p>
      </header>

      <div class="grid gap-4">
        <article
          v-for="method in paymentMethods"
          :key="method.id"
          class="rounded-2xl border border-indigo-100/90 bg-white/90 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55"
        >
          <form class="space-y-4" @submit.prevent="submit(method.id)">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-300">
                  {{ method.code }}
                </p>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ method.name }}</h3>
                <p v-if="method.gateway" class="text-xs text-slate-500 dark:text-slate-400">
                  Gateway: {{ method.gateway }}
                </p>
              </div>
              <span
                class="rounded-full px-2.5 py-1 text-xs font-semibold"
                :class="method.is_enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'"
              >
                {{ method.is_enabled ? 'Enabled' : 'Disabled' }}
              </span>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Display name</label>
                <input
                  v-model="forms[method.id].name"
                  type="text"
                  class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"
                >
                <p v-if="forms[method.id].errors.name" class="mt-1 text-xs text-rose-600">{{ forms[method.id].errors.name }}</p>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Sort order</label>
                <input
                  v-model.number="forms[method.id].sort_order"
                  type="number"
                  min="0"
                  max="999"
                  class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"
                >
                <p v-if="forms[method.id].errors.sort_order" class="mt-1 text-xs text-rose-600">{{ forms[method.id].errors.sort_order }}</p>
              </div>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Description</label>
              <input
                v-model="forms[method.id].description"
                type="text"
                class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"
              >
              <p v-if="forms[method.id].errors.description" class="mt-1 text-xs text-rose-600">{{ forms[method.id].errors.description }}</p>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Timing</label>
                <select
                  v-model="forms[method.id].timing"
                  class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"
                >
                  <option v-for="(label, value) in timingOptions" :key="value" :value="value">{{ label }}</option>
                </select>
                <p v-if="forms[method.id].errors.timing" class="mt-1 text-xs text-rose-600">{{ forms[method.id].errors.timing }}</p>
              </div>
              <label class="flex items-center gap-2 rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
                <input v-model="forms[method.id].is_enabled" type="checkbox" class="rounded border-slate-300">
                Enabled at checkout
              </label>
              <label class="flex items-center gap-2 rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
                <input v-model="forms[method.id].requires_phone" type="checkbox" class="rounded border-slate-300">
                Requires customer phone
              </label>
            </div>

            <div class="flex justify-end">
              <button
                type="submit"
                class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                :disabled="forms[method.id].processing"
              >
                {{ forms[method.id].processing ? 'Saving...' : 'Save changes' }}
              </button>
            </div>
          </form>
        </article>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Shared/AdminLayout.vue';

const props = defineProps({
  paymentMethods: {
    type: Array,
    required: true,
  },
  timingOptions: {
    type: Object,
    required: true,
  },
});

const forms = Object.fromEntries(
  props.paymentMethods.map((method) => [
    method.id,
    useForm({
      name: method.name,
      description: method.description ?? '',
      timing: method.timing,
      is_enabled: method.is_enabled,
      requires_phone: method.requires_phone,
      sort_order: method.sort_order,
    }),
  ]),
);

const submit = (methodId) => {
  forms[methodId].patch(`/admin/payment-methods/${methodId}`, { preserveScroll: true });
};
</script>
