<template>
  <div class="relative min-h-screen bg-transparent text-slate-900 transition-colors dark:bg-slate-950 dark:text-slate-100">
    <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
      <div class="animate-blob absolute left-[-90px] top-[-120px] h-72 w-72 rounded-full bg-indigo-200/35 blur-3xl dark:bg-indigo-500/10" />
      <div class="animate-blob animation-delay-2000 absolute right-[-110px] top-[120px] h-72 w-72 rounded-full bg-teal-200/25 blur-3xl dark:bg-teal-500/10" />
    </div>
    <Header />
    <main class="mx-auto max-w-7xl px-4 py-6">
      <p
        v-if="flashSuccess"
        class="mb-4 rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300"
      >
        {{ flashSuccess }}
      </p>
      <p
        v-if="flashError"
        class="mb-4 rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-300"
      >
        {{ flashError }}
      </p>
      <slot />
    </main>
    <Footer />
  </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Footer from '@/Shared/Footer.vue';
import Header from '@/Shared/Header.vue';

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success ?? null);
const flashError = computed(() => page.props.flash?.error ?? null);
</script>
