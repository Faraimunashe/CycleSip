<template>
  <form class="grid gap-4 rounded-2xl border border-indigo-100/90 bg-white/90 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55" @submit.prevent="$emit('submit')">
    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Product name</label>
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
        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Brand</label>
        <input v-model="form.brand" type="text" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Category</label>
        <select v-model="form.product_category_id" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
          <option :value="null">No category</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
        </select>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-[auto_1fr] md:items-start">
      <EntityImage
        :image-url="previewUrl"
        alt="Product preview"
        placeholder-label="Product image"
        img-class="h-20 w-20 rounded-xl border border-indigo-100 object-cover"
      />
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Upload product image</label>
        <input
          type="file"
          accept="image/*"
          class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-indigo-700 dark:border-slate-700 dark:bg-slate-950/70"
          @change="handleImageSelect"
        >
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">PNG, JPG, WEBP up to 5MB.</p>
        <p v-if="form.errors.image" class="mt-1 text-xs text-rose-600">{{ form.errors.image }}</p>

        <label v-if="existingImageUrl" class="mt-2 inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
          <input v-model="form.remove_image" type="checkbox" class="rounded border-slate-300">
          Remove current image
        </label>
      </div>
    </div>

    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Description</label>
      <textarea v-model="form.description" rows="3" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"></textarea>
      <p v-if="form.errors.description" class="mt-1 text-xs text-rose-600">{{ form.errors.description }}</p>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
      <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
        <input v-model="form.is_featured" type="checkbox" class="rounded border-slate-300">
        Featured
      </label>
      <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
        <input v-model="form.is_promoted" type="checkbox" class="rounded border-slate-300">
        Promoted
      </label>
      <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
        <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300">
        Active
      </label>
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

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  categories: {
    type: Array,
    default: () => [],
  },
  existingImageUrl: {
    type: String,
    default: '',
  },
  submitLabel: {
    type: String,
    default: 'Save product',
  },
});

defineEmits(['submit']);

const uploadedPreviewUrl = ref('');

const previewUrl = computed(() => {
  if (props.form.remove_image) {
    return uploadedPreviewUrl.value;
  }

  return uploadedPreviewUrl.value || props.existingImageUrl;
});

const handleImageSelect = event => {
  const [file] = event.target.files || [];
  props.form.image = file || null;
  props.form.remove_image = false;

  if (uploadedPreviewUrl.value) {
    URL.revokeObjectURL(uploadedPreviewUrl.value);
    uploadedPreviewUrl.value = '';
  }

  if (file) {
    uploadedPreviewUrl.value = URL.createObjectURL(file);
  }
};
</script>
