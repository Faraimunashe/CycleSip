<template>
  <img
    v-if="src"
    :src="src"
    :alt="alt"
    :class="imgClass"
    @error="showFallback = true"
  >
  <div
    v-else
    :class="fallbackClass"
  >
    <ImageOff class="h-5 w-5 text-slate-400" />
    <span class="text-xs font-medium text-slate-500">{{ placeholderLabel }}</span>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { ImageOff } from '@lucide/vue';

const props = defineProps({
  imageUrl: {
    type: String,
    default: '',
  },
  alt: {
    type: String,
    default: 'Entity image',
  },
  placeholderLabel: {
    type: String,
    default: 'No image',
  },
  imgClass: {
    type: String,
    default: 'h-20 w-20 rounded-xl border border-indigo-100 object-cover',
  },
  fallbackClass: {
    type: String,
    default: 'flex h-20 w-20 flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-indigo-200 bg-indigo-50/70',
  },
});

const showFallback = ref(false);

const src = computed(() => {
  if (showFallback.value || !props.imageUrl) {
    return '';
  }

  return props.imageUrl;
});
</script>
