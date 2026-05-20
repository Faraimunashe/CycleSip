<template>
  <Head title="Create Store" />

  <AdminLayout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60">
        <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Create Store</h2>
        <p class="text-sm text-slate-600 dark:text-slate-400">Add a new store entity with operational and branding information.</p>
      </header>

      <StoreForm :form="form" submit-label="Create store" @submit="submit" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import StoreForm from '@/Components/Admin/StoreForm.vue';
import AdminLayout from '@/Shared/AdminLayout.vue';

const props = defineProps({
  store: {
    type: Object,
    required: true,
  },
});

const form = useForm({
  ...props.store,
});

const submit = () => {
  form.post('/admin/stores');
};
</script>
