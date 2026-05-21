<template>
  <Head title="Verify Email" />

  <AuthLayout>
    <section class="glass mx-auto max-w-lg rounded-3xl p-6 shadow-sm sm:p-8">
      <h2 class="text-2xl font-semibold text-slate-900">Verify your email</h2>
      <p class="mt-2 text-sm text-slate-600">
        We sent a 6-digit code to <span class="font-semibold text-slate-800">{{ email }}</span>.
      </p>

      <form class="mt-6 space-y-4" @submit.prevent="submit">
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Verification code</label>
          <input
            v-model="form.code"
            type="text"
            maxlength="6"
            inputmode="numeric"
            class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-3 text-center text-lg tracking-[0.35em]"
            placeholder="000000"
          >
          <p v-if="form.errors.code" class="mt-1 text-xs text-rose-600">{{ form.errors.code }}</p>
        </div>

        <button
          type="submit"
          class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60"
          :disabled="form.processing"
        >
          Verify email
        </button>
      </form>

      <form class="mt-4" @submit.prevent="resend">
        <button type="submit" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500" :disabled="resendForm.processing">
          Resend code
        </button>
      </form>
    </section>
  </AuthLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Shared/AuthLayout.vue';

defineProps({
  email: {
    type: String,
    required: true,
  },
});

const form = useForm({
  code: '',
});

const resendForm = useForm({});

const submit = () => {
  form.post('/email/verify');
};

const resend = () => {
  resendForm.post('/email/verification-resend');
};
</script>
