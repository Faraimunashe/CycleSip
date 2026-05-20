<template>
  <img v-if="showLogo" :src="logoUrl" alt="CycleSip Logo" :class="imgClass" @error="showLogo = false">
  <div
    v-else
    :class="fallbackClass"
  >
    <Bike class="h-5 w-5" />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Bike } from '@lucide/vue';
import logoUrl from '@/../images/logo.png';

const props = defineProps({
  imgClass: {
    type: String,
    default: 'h-10 w-10 rounded-xl border border-white/50 object-cover shadow-sm dark:border-slate-700/60',
  },
  fallbackClass: {
    type: String,
    default: 'flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm dark:bg-indigo-500 dark:text-white',
  },
});
const showLogo = ref(true);
</script>
