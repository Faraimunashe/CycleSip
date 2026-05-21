<template>
  <Head title="Identity Verification" />

  <Layout>
    <div class="mx-auto max-w-2xl space-y-4">
      <header class="glass rounded-2xl p-5 shadow-sm">
        <h2 class="text-2xl font-semibold text-slate-900">Optional ID verification</h2>
        <p class="mt-1 text-sm text-slate-600">Upload a government ID for enhanced age compliance review.</p>
      </header>

      <article v-if="latestDocument" class="glass rounded-2xl p-5 shadow-sm">
        <p class="text-sm font-semibold text-slate-800">Latest submission</p>
        <p class="mt-1 text-sm text-slate-600">Type: {{ formatType(latestDocument.document_type) }}</p>
        <p class="text-sm text-slate-600">Status: {{ latestDocument.status }}</p>
        <p v-if="latestDocument.rejection_reason" class="mt-1 text-sm text-rose-600">Reason: {{ latestDocument.rejection_reason }}</p>
      </article>

      <form class="glass grid gap-4 rounded-2xl p-5 shadow-sm" @submit.prevent="submit">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Document type</label>
          <select v-model="form.document_type" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm">
            <option value="">Select type</option>
            <option v-for="type in documentTypes" :key="type" :value="type">{{ formatType(type) }}</option>
          </select>
          <p v-if="form.errors.document_type" class="mt-1 text-xs text-rose-600">{{ form.errors.document_type }}</p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Upload document</label>
          <input
            type="file"
            accept="image/*,.pdf"
            class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-indigo-700"
            @change="handleFileSelect"
          >
          <p class="mt-1 text-xs text-slate-500">JPG, PNG, or PDF up to 5MB.</p>
          <p v-if="form.errors.document" class="mt-1 text-xs text-rose-600">{{ form.errors.document }}</p>
        </div>

        <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" :disabled="form.processing">
          Submit for review
        </button>
      </form>
    </div>
  </Layout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import Layout from '@/Shared/Layout.vue';

defineProps({
  documentTypes: {
    type: Array,
    default: () => [],
  },
  latestDocument: {
    type: Object,
    default: null,
  },
});

const form = useForm({
  document_type: '',
  document: null,
});

const formatType = (value) => value ? value.replaceAll('_', ' ') : '';

const handleFileSelect = (event) => {
  const [file] = event.target.files || [];
  form.document = file || null;
};

const submit = () => {
  form.post('/compliance/identity', {
    forceFormData: true,
  });
};
</script>
