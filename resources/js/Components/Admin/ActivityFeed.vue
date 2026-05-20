<template>
  <article class="rounded-2xl border border-indigo-100/90 bg-white/88 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55">
    <div class="flex items-center justify-between">
      <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Live Activity Feed</h3>
      <Activity class="h-4 w-4 text-emerald-500" />
    </div>
    <ul class="mt-4 space-y-3">
      <li
        v-for="activity in activities"
        :key="activity.id"
        class="rounded-xl border border-slate-100 px-3 py-2 text-sm dark:border-slate-800"
      >
        <p class="flex items-start gap-2 font-medium text-slate-800 dark:text-slate-200">
          <span class="mt-1 inline-block h-2 w-2 rounded-full bg-emerald-500" />
          <span>{{ activity.event }}</span>
        </p>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ formatDate(activity.created_at) }}</p>
      </li>
      <li v-if="activities.length === 0" class="text-sm text-slate-500 dark:text-slate-400">
        No recent activity.
      </li>
    </ul>
  </article>
</template>

<script setup>
import { Activity } from '@lucide/vue';

defineProps({
  activities: {
    type: Array,
    default: () => [],
  },
});

const formatDate = value => new Date(value).toLocaleString();
</script>
