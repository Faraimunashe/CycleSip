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

    <div class="grid gap-4 md:grid-cols-[auto_1fr] md:items-start">
      <EntityImage
        :image-url="previewUrl"
        alt="Store logo preview"
        placeholder-label="Store logo"
        img-class="h-20 w-20 rounded-xl border border-indigo-100 object-cover"
      />
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Upload store icon/logo</label>
        <input
          type="file"
          accept="image/*"
          class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-indigo-700 dark:border-slate-700 dark:bg-slate-950/70"
          @change="handleLogoSelect"
        >
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">PNG, JPG, WEBP up to 5MB.</p>
        <p v-if="form.errors.logo" class="mt-1 text-xs text-rose-600">{{ form.errors.logo }}</p>
        <p v-if="form.errors.logo_url" class="mt-1 text-xs text-rose-600">{{ form.errors.logo_url }}</p>

        <label v-if="existingImageUrl" class="mt-2 inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
          <input v-model="form.remove_logo" type="checkbox" class="rounded border-slate-300">
          Remove current logo
        </label>
      </div>
    </div>

    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Phone</label>
      <input v-model="form.phone" type="text" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
      <p v-if="form.errors.phone" class="mt-1 text-xs text-rose-600">{{ form.errors.phone }}</p>
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

    <div>
      <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Delivery zones</label>
      <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">
        Link this store to one or more zones so customers in those areas can order from it.
      </p>
      <div v-if="availableZones.length" class="grid gap-2 sm:grid-cols-2">
        <label
          v-for="zone in availableZones"
          :key="zone.id"
          class="flex items-center gap-2 rounded-xl border border-indigo-100 px-3 py-2 text-sm dark:border-slate-700"
        >
          <input
            type="checkbox"
            class="rounded border-slate-300"
            :checked="form.zone_ids.includes(zone.id)"
            @change="toggleZone(zone.id)"
          >
          <span class="text-slate-700 dark:text-slate-300">
            {{ zone.name }}
            <span v-if="!zone.is_active" class="text-xs text-rose-500">(inactive)</span>
          </span>
        </label>
      </div>
      <p v-else class="text-sm text-slate-500 dark:text-slate-400">No delivery zones exist yet. Create a zone first.</p>
      <p v-if="form.errors.zone_ids" class="mt-1 text-xs text-rose-600">{{ form.errors.zone_ids }}</p>
    </div>

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
import { computed, ref } from 'vue';
import EntityImage from '@/Components/Admin/EntityImage.vue';

defineEmits(['submit']);

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  existingImageUrl: {
    type: String,
    default: '',
  },
  availableZones: {
    type: Array,
    default: () => [],
  },
  submitLabel: {
    type: String,
    default: 'Save store',
  },
});

const toggleZone = zoneId => {
  const selected = props.form.zone_ids || [];

  if (selected.includes(zoneId)) {
    props.form.zone_ids = selected.filter(id => id !== zoneId);
    return;
  }

  props.form.zone_ids = [...selected, zoneId];
};

const uploadedPreviewUrl = ref('');

const previewUrl = computed(() => {
  if (props.form.remove_logo) {
    return uploadedPreviewUrl.value;
  }

  return uploadedPreviewUrl.value || props.existingImageUrl;
});

const handleLogoSelect = event => {
  const [file] = event.target.files || [];
  props.form.logo = file || null;
  props.form.remove_logo = false;

  if (uploadedPreviewUrl.value) {
    URL.revokeObjectURL(uploadedPreviewUrl.value);
    uploadedPreviewUrl.value = '';
  }

  if (file) {
    uploadedPreviewUrl.value = URL.createObjectURL(file);
  }
};
</script>
