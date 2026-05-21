<template>
  <Head title="Reset Password" />

  <AuthLayout>
    <section class="glass mx-auto max-w-lg rounded-3xl p-6 shadow-sm sm:p-8">
      <h2 class="text-2xl font-semibold text-slate-900">Reset password</h2>
      <p class="mt-2 text-sm text-slate-600">Choose a new password for your account.</p>

      <form class="mt-6 space-y-4" @submit.prevent="submit">
        <input type="hidden" v-model="form.token">

        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Email address</label>
          <input v-model="form.email" type="email" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-3 text-sm" autocomplete="email">
          <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">New password</label>
          <input v-model="form.password" type="password" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-3 text-sm" autocomplete="new-password">
          <p v-if="form.errors.password" class="mt-1 text-xs text-rose-600">{{ form.errors.password }}</p>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Confirm password</label>
          <input v-model="form.password_confirmation" type="password" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-3 text-sm" autocomplete="new-password">
        </div>

        <button
          type="submit"
          class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60"
          :disabled="form.processing"
        >
          Reset password
        </button>
      </form>
    </section>
  </AuthLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Shared/AuthLayout.vue';

const props = defineProps({
  token: {
    type: String,
    required: true,
  },
  email: {
    type: String,
    default: '',
  },
});

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post('/reset-password');
};
</script>
