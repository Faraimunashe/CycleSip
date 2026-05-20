<template>
  <form class="grid gap-4 rounded-2xl border border-indigo-100/90 bg-white/90 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55" @submit.prevent="$emit('submit')">
    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Store name</label>
        <input v-model="form.name" type="text" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
        <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Slug</label>
        <input v-model="form.slug" type="text" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
        <p v-if="form.errors.slug" class="mt-1 text-xs text-rose-600">{{ form.errors.slug }}</p>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Store logo URL</label>
        <input v-model="form.logo_url" type="url" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
        <p v-if="form.errors.logo_url" class="mt-1 text-xs text-rose-600">{{ form.errors.logo_url }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Phone</label>
        <input v-model="form.phone" type="text" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
        <p v-if="form.errors.phone" class="mt-1 text-xs text-rose-600">{{ form.errors.phone }}</p>
      </div>
    </div>

    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Address</label>
      <input v-model="form.address" type="text" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
      <p v-if="form.errors.address" class="mt-1 text-xs text-rose-600">{{ form.errors.address }}</p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Opening time</label>
        <input v-model="form.opening_time" type="time" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Closing time</label>
        <input v-model="form.closing_time" type="time" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Commission rate (%)</label>
        <input v-model.number="form.commission_rate" type="number" step="0.01" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
      </div>
    </div>

    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
      <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300">
      Store is active
    </label>

    <div class="flex items-center justify-end gap-2">
      <button
        type="submit"
        class="cursor-pointer rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
        :disabled="form.processing"
      >
        {{ submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
defineProps({
  form: {
    type: Object,
    required: true,
  },
  submitLabel: {
    type: String,
    default: 'Save store',
  },
});

defineEmits(['submit']);
</script>
