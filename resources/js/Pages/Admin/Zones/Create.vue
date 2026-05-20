<template>
  <Head title="Create Zone" />

  <AdminLayout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60">
        <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Create Delivery Zone</h2>
        <p class="text-sm text-slate-600 dark:text-slate-400">Add a new delivery zone with radius, pricing, and ETA rules.</p>
      </header>

      <ZoneForm :form="form" submit-label="Create zone" @submit="submit" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import ZoneForm from '@/Components/Admin/ZoneForm.vue';
import AdminLayout from '@/Shared/AdminLayout.vue';

const props = defineProps({
  zone: {
    type: Object,
    required: true,
  },
});

const form = useForm({
  ...props.zone,
});

const submit = () => {
  form.post('/admin/zones');
};
</script>
