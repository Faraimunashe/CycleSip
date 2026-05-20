<template>
  <article class="rounded-2xl border border-indigo-100/90 bg-white/88 p-4 shadow-sm backdrop-blur-xl ring-1 ring-transparent transition hover:-translate-y-0.5 hover:shadow-md hover:ring-indigo-300/30 dark:border-slate-700/45 dark:bg-slate-900/55">
    <div class="flex items-start justify-between gap-3">
      <div>
        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ label }}</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ value }}</p>
      </div>
      <div
        class="flex h-10 w-10 items-center justify-center rounded-xl"
        :class="toneClasses[tone]"
      >
        <component :is="icon" class="h-5 w-5" />
      </div>
    </div>
    <p v-if="caption" class="mt-3 text-xs text-slate-500 dark:text-slate-400">{{ caption }}</p>
  </article>
</template>

<script setup>
const toneClasses = {
  indigo: 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300',
  emerald: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300',
  sky: 'bg-sky-50 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300',
  rose: 'bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300',
  amber: 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300',
  violet: 'bg-violet-50 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300',
};

defineProps({
  label: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    required: true,
  },
  icon: {
    type: Object,
    required: true,
  },
  tone: {
    type: String,
    default: 'indigo',
  },
  caption: {
    type: String,
    default: '',
  },
});
</script>
