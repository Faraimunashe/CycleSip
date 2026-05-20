<template>
  <Head :title="`Edit ${store.name}`" />

  <AdminLayout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60">
        <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Edit Store</h2>
        <p class="text-sm text-slate-600 dark:text-slate-400">Update store details, schedule, and logo.</p>
      </header>

      <StoreForm :form="form" submit-label="Update store" @submit="submit" />
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
  name: props.store.name,
  logo_url: props.store.logo_url || '',
  slug: props.store.slug,
  address: props.store.address,
  phone: props.store.phone || '',
  opening_time: props.store.opening_time || '',
  closing_time: props.store.closing_time || '',
  commission_rate: props.store.commission_rate,
  is_active: props.store.is_active,
});

const submit = () => {
  form.put(`/admin/stores/${props.store.id}`);
};
</script>
