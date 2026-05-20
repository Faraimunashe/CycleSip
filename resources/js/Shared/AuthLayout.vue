<template>
  <div class="relative min-h-screen bg-transparent text-slate-900 dark:bg-slate-950 dark:text-slate-100">
    <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
      <div class="absolute -left-28 -top-24 h-64 w-64 rounded-full bg-indigo-200/30 blur-3xl dark:bg-indigo-500/10" />
      <div class="absolute -right-20 top-36 h-64 w-64 rounded-full bg-emerald-200/20 blur-3xl dark:bg-emerald-500/10" />
    </div>

    <header class="border-b border-indigo-100/80 bg-white/85 backdrop-blur-xl dark:border-slate-700/40 dark:bg-slate-900/60">
      <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-3">
        <Link href="/" class="inline-flex items-center gap-3">
          <BrandMark />
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-700 dark:text-indigo-300">CycleSip</p>
            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">Operations Platform</p>
          </div>
        </Link>
      </div>
    </header>

    <main class="mx-auto w-full max-w-6xl px-4 py-8 lg:py-12">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import BrandMark from '@/Shared/BrandMark.vue';
</script>
