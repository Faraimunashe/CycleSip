<template>
  <Head title="Register" />

  <Layout>
    <div class="mx-auto w-full max-w-6xl">
      <div class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
        <section class="glass rounded-3xl p-6 shadow-sm sm:p-8">
          <div class="mb-6">
            <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Create your account</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Join CycleSip and start ordering from nearby stores.</p>
          </div>

          <form class="space-y-4" @submit.prevent="submit">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Full name</label>
              <div class="relative">
                <UserRound class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                  v-model="form.name"
                  type="text"
                  class="w-full rounded-xl border border-indigo-100 bg-white py-2.5 pl-9 pr-3 dark:border-slate-700 dark:bg-slate-950/70"
                  autocomplete="name"
                />
              </div>
              <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Email</label>
              <div class="relative">
                <Mail class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                  v-model="form.email"
                  type="email"
                  class="w-full rounded-xl border border-indigo-100 bg-white py-2.5 pl-9 pr-3 dark:border-slate-700 dark:bg-slate-950/70"
                  autocomplete="email"
                />
              </div>
              <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Password</label>
                <div class="relative">
                  <Lock class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                  <input
                    v-model="form.password"
                    type="password"
                    class="w-full rounded-xl border border-indigo-100 bg-white py-2.5 pl-9 pr-3 dark:border-slate-700 dark:bg-slate-950/70"
                    autocomplete="new-password"
                  />
                </div>
                <p v-if="form.errors.password" class="mt-1 text-xs text-rose-600">{{ form.errors.password }}</p>
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Confirm password</label>
                <div class="relative">
                  <ShieldCheck class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                  <input
                    v-model="form.password_confirmation"
                    type="password"
                    class="w-full rounded-xl border border-indigo-100 bg-white py-2.5 pl-9 pr-3 dark:border-slate-700 dark:bg-slate-950/70"
                    autocomplete="new-password"
                  />
                </div>
              </div>
            </div>

            <p class="text-xs text-slate-500 dark:text-slate-400">By creating an account, you confirm you are 18+ and agree to platform policies.</p>

            <div class="flex items-center justify-between gap-2">
              <Link href="/login" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                Already have an account?
              </Link>
              <button
                type="submit"
                class="cursor-pointer rounded-xl bg-indigo-600 px-5 py-2.5 font-semibold text-white transition hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                :disabled="form.processing"
              >
                {{ form.processing ? 'Creating account...' : 'Create account' }}
              </button>
            </div>
          </form>
        </section>

        <section class="glass hidden rounded-3xl p-8 shadow-sm lg:flex lg:flex-col lg:justify-between">
          <div>
            <div class="mb-6 flex items-center gap-3">
              <BrandMark />
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-700 dark:text-indigo-300">CycleSip</p>
                <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Fast Local Delivery</h2>
              </div>
            </div>
            <p class="max-w-md text-sm leading-relaxed text-slate-600 dark:text-slate-400">
              Shop your favorite drinks, get fast rider delivery, and track every order from checkout to doorstep.
            </p>
          </div>

          <div class="rounded-2xl border border-indigo-100/90 bg-white/88 p-4 backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55">
            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Why users choose CycleSip</p>
            <ul class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-400">
              <li class="inline-flex items-center gap-2"><CheckCircle2 class="h-4 w-4 text-emerald-500" /> Real-time order tracking</li>
              <li class="inline-flex items-center gap-2"><CheckCircle2 class="h-4 w-4 text-emerald-500" /> Rider-first delivery model</li>
              <li class="inline-flex items-center gap-2"><CheckCircle2 class="h-4 w-4 text-emerald-500" /> Fast checkout and dispatch</li>
            </ul>
          </div>
        </section>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CheckCircle2, Lock, Mail, ShieldCheck, UserRound } from '@lucide/vue';
import BrandMark from '@/Shared/BrandMark.vue';
import Layout from '@/Shared/Layout.vue';

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post('/register');
};
</script>
