<template>
  <Head :title="`Edit ${zone.name}`" />

  <AdminLayout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60">
        <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Edit Delivery Zone</h2>
        <p class="text-sm text-slate-600 dark:text-slate-400">Update operational coverage, fees, and time estimates.</p>
      </header>

      <ZoneForm :form="form" :available-stores="availableStores" submit-label="Update zone" @submit="submit" />
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
  availableStores: {
    type: Array,
    default: () => [],
  },
});

const form = useForm({
  name: props.zone.name,
  slug: props.zone.slug,
  center_latitude: props.zone.center_latitude,
  center_longitude: props.zone.center_longitude,
  radius_km: props.zone.radius_km,
  base_delivery_fee: props.zone.base_delivery_fee,
  distance_surcharge_per_km: props.zone.distance_surcharge_per_km,
  estimated_minutes: props.zone.estimated_minutes,
  is_active: props.zone.is_active,
  store_ids: props.zone.store_ids || [],
});

const submit = () => {
  form.put(`/admin/zones/${props.zone.id}`);
};
</script>
