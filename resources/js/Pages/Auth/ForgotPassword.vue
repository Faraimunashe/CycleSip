<template>
  <Head title="Forgot Password" />

  <AuthLayout>
    <section class="glass mx-auto max-w-lg rounded-3xl p-6 shadow-sm sm:p-8">
      <h2 class="text-2xl font-semibold text-slate-900">Forgot password</h2>
      <p class="mt-2 text-sm text-slate-600">Enter your email and we will send a reset link if the account exists.</p>

      <form class="mt-6 space-y-4" @submit.prevent="submit">
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Email address</label>
          <input
            v-model="form.email"
            type="email"
            class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-3 text-sm"
            autocomplete="email"
          >
          <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
        </div>

        <div class="flex items-center justify-between gap-2">
          <Link href="/login" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">Back to sign in</Link>
          <button
            type="submit"
            class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60"
            :disabled="form.processing"
          >
            Send reset link
          </button>
        </div>
      </form>
    </section>
  </AuthLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Shared/AuthLayout.vue';

const form = useForm({
  email: '',
});

const submit = () => {
  form.post('/forgot-password');
};
</script>
