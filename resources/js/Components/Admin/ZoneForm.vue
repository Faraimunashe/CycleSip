<template>
  <form class="grid gap-4 rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60" @submit.prevent="$emit('submit')">
    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Zone name</label>
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
        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Center latitude</label>
        <input v-model.number="form.center_latitude" type="number" step="0.000001" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Center longitude</label>
        <input v-model.number="form.center_longitude" type="number" step="0.000001" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Radius (km)</label>
        <input v-model.number="form.radius_km" type="number" step="0.1" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Base fee</label>
        <input v-model.number="form.base_delivery_fee" type="number" step="0.01" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Surcharge/km</label>
        <input v-model.number="form.distance_surcharge_per_km" type="number" step="0.01" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">ETA (minutes)</label>
        <input v-model.number="form.estimated_minutes" type="number" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
      </div>
    </div>

    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
      <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300">
      Zone is active
    </label>

    <div>
      <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Linked stores</label>
      <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">
        Choose which stores serve customers inside this delivery zone.
      </p>
      <div v-if="availableStores.length" class="grid gap-2 sm:grid-cols-2">
        <label
          v-for="store in availableStores"
          :key="store.id"
          class="flex items-center gap-2 rounded-xl border border-indigo-100 px-3 py-2 text-sm dark:border-slate-700"
        >
          <input
            type="checkbox"
            class="rounded border-slate-300"
            :checked="form.store_ids.includes(store.id)"
            @change="toggleStore(store.id)"
          >
          <span class="text-slate-700 dark:text-slate-300">
            {{ store.name }}
            <span v-if="!store.is_active" class="text-xs text-rose-500">(inactive)</span>
          </span>
        </label>
      </div>
      <p v-else class="text-sm text-slate-500 dark:text-slate-400">No stores exist yet. Create a store first.</p>
      <p v-if="form.errors.store_ids" class="mt-1 text-xs text-rose-600">{{ form.errors.store_ids }}</p>
    </div>

    <div class="flex justify-end">
      <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" :disabled="form.processing">
        {{ submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  availableStores: {
    type: Array,
    default: () => [],
  },
  submitLabel: {
    type: String,
    default: 'Save zone',
  },
});

defineEmits(['submit']);

const toggleStore = storeId => {
  const selected = props.form.store_ids || [];

  if (selected.includes(storeId)) {
    props.form.store_ids = selected.filter(id => id !== storeId);
    return;
  }

  props.form.store_ids = [...selected, storeId];
};
</script>
