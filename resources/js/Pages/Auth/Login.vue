<template>
  <Head title="Login" />

  <AuthLayout>
    <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
      <section class="glass relative hidden overflow-hidden rounded-3xl p-8 shadow-sm lg:flex lg:flex-col lg:justify-between">
        <div
          class="absolute inset-0 opacity-[0.12]"
          :style="{ backgroundImage: `url(${authCardBg})`, backgroundSize: 'cover', backgroundPosition: 'center' }"
        />
        <div class="absolute inset-0 bg-white/80 dark:bg-slate-900/65" />

        <div class="relative">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-700 dark:text-indigo-300">Welcome to CycleSip</p>
            <h1 class="mt-2 text-3xl font-semibold leading-tight text-slate-900 dark:text-slate-100">
              World-class rider operations, built for speed.
            </h1>
            <p class="mt-4 max-w-lg text-sm leading-relaxed text-slate-600 dark:text-slate-400">
              Sign in to monitor incoming orders, manage riders in real time, and keep dispatch quality consistently high.
            </p>
          </div>
        </div>

        <div class="relative space-y-3">
          <div class="rounded-2xl border border-indigo-100/90 bg-white/95 p-4 dark:border-slate-700/45 dark:bg-slate-900/55">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Why teams choose CycleSip</p>
            <ul class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-400">
              <li class="inline-flex items-center gap-2"><CheckCircle2 class="h-4 w-4 text-emerald-500" /> Real-time rider assignment control</li>
              <li class="inline-flex items-center gap-2"><CheckCircle2 class="h-4 w-4 text-emerald-500" /> Live compliance and delivery visibility</li>
              <li class="inline-flex items-center gap-2"><CheckCircle2 class="h-4 w-4 text-emerald-500" /> Modern role-based operations workspace</li>
            </ul>
          </div>
        </div>
      </section>

      <section class="glass rounded-3xl p-6 shadow-sm sm:p-8">
        <div class="mb-8">
          <h2 class="text-3xl font-semibold text-slate-900 dark:text-slate-100">Sign in</h2>
          <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Access your admin and operations dashboard.</p>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Email address</label>
            <div class="relative">
              <Mail class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
              <input
                v-model="form.email"
                type="email"
                class="w-full rounded-xl border border-indigo-100 bg-white py-3 pl-10 pr-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950/70 dark:focus:border-indigo-400/70 dark:focus:ring-indigo-500/20"
                autocomplete="email"
              >
            </div>
            <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Password</label>
            <div class="relative">
              <Lock class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
              <input
                v-model="form.password"
                type="password"
                class="w-full rounded-xl border border-indigo-100 bg-white py-3 pl-10 pr-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950/70 dark:focus:border-indigo-400/70 dark:focus:ring-indigo-500/20"
                autocomplete="current-password"
              >
            </div>
            <p v-if="form.errors.password" class="mt-1 text-xs text-rose-600">{{ form.errors.password }}</p>
          </div>

          <div class="flex items-center justify-between">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
              <input v-model="form.remember" type="checkbox" class="rounded border-slate-300">
              Keep me signed in
            </label>
            <Link href="/forgot-password" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-200">
              Forgot password?
            </Link>
          </div>

          <p class="text-center text-sm text-slate-600">
            <Link href="/register" class="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-200">
              Create account
            </Link>
          </p>

          <button
            type="submit"
            class="w-full cursor-pointer rounded-xl bg-indigo-600 px-4 py-3 font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="form.processing"
          >
            {{ form.processing ? 'Signing in...' : 'Sign in to CycleSip' }}
          </button>
        </form>
      </section>
    </div>
  </AuthLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CheckCircle2, Lock, Mail } from '@lucide/vue';
import AuthLayout from '@/Shared/AuthLayout.vue';
import authCardBg from '@/../images/logo2.png';

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post('/login');
};
</script>
