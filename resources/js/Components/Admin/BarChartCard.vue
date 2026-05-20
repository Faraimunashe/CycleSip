<template>
  <article class="rounded-2xl border border-indigo-100/90 bg-white/88 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55">
    <div class="mb-1 flex items-center justify-between">
      <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ title }}</h3>
      <BarChart3 class="h-4 w-4 text-slate-400 dark:text-slate-500" />
    </div>
    <p v-if="subtitle" class="text-xs text-slate-500 dark:text-slate-400">{{ subtitle }}</p>
    <div class="mt-4 space-y-3">
      <div v-for="item in normalizedData" :key="item.label" class="space-y-1">
        <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
          <span>{{ item.label }}</span>
          <span class="font-semibold">{{ item.value }}</span>
        </div>
        <div class="h-2 rounded-full bg-indigo-100/55 dark:bg-slate-800">
          <div class="h-2 rounded-full bg-indigo-500 dark:bg-indigo-400" :style="{ width: `${item.percentage}%` }" />
        </div>
      </div>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue';
import { BarChart3 } from '@lucide/vue';

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  items: {
    type: Array,
    default: () => [],
  },
  valueKey: {
    type: String,
    default: 'value',
  },
  labelKey: {
    type: String,
    default: 'label',
  },
  subtitle: {
    type: String,
    default: '',
  },
});

const normalizedData = computed(() => {
  const maxValue = Math.max(...props.items.map(item => Number(item[props.valueKey] || 0)), 1);

  return props.items.map(item => ({
    label: item[props.labelKey],
    value: Number(item[props.valueKey] || 0),
    percentage: Math.round((Number(item[props.valueKey] || 0) / maxValue) * 100),
  }));
});
</script>
