<template>
  <Head title="Choose Delivery Address" />

  <Layout>
    <div class="mx-auto max-w-5xl space-y-5">
      <header class="glass rounded-2xl p-5 shadow-sm">
        <p class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">
          <MapPinHouse class="h-3.5 w-3.5" />
          Delivery setup
        </p>
        <h2 class="mt-1 text-2xl font-semibold text-slate-900">Choose your delivery address</h2>
        <p class="mt-1 text-sm text-slate-600">
          Select a saved address or add a new one before you continue ordering.
        </p>
      </header>

      <section class="glass rounded-2xl p-5 shadow-sm">
        <h3 class="mb-3 inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-600">
          <BookMarked class="h-4 w-4 text-indigo-600" />
          Saved addresses
        </h3>

        <div v-if="addresses.length === 0" class="rounded-xl border border-indigo-100 bg-white/80 p-4 text-sm text-slate-600">
          No saved addresses yet. Add your first one below.
        </div>

        <div class="grid gap-3 md:grid-cols-2">
          <article
            v-for="address in addresses"
            :key="address.id"
            class="rounded-xl border border-indigo-100 bg-white/85 p-4"
          >
            <div class="mb-2 flex items-center justify-between gap-2">
              <p class="font-semibold text-slate-900">{{ address.label }}</p>
              <span
                v-if="selectedAddressId === address.id"
                class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700"
              >
                Selected
              </span>
            </div>
            <p class="text-sm text-slate-600">{{ address.address_line }}</p>
            <p v-if="address.latitude && address.longitude" class="mt-1 text-xs text-slate-500">
              {{ address.latitude }}, {{ address.longitude }}
            </p>

            <button
              type="button"
              class="mt-3 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500"
              @click="selectAddress(address.id)"
            >
              Use this address
            </button>
          </article>
        </div>
      </section>

      <section class="glass rounded-2xl p-5 shadow-sm">
        <h3 class="mb-3 inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-600">
          <PlusCircle class="h-4 w-4 text-indigo-600" />
          Add new address
        </h3>

        <form class="grid gap-4" @submit.prevent="saveAddress">
          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Address label</label>
              <input v-model="form.label" type="text" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm">
              <p v-if="form.errors.label" class="mt-1 text-xs text-rose-600">{{ form.errors.label }}</p>
            </div>
            <div class="flex items-end">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-100 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50"
                @click="useCurrentLocation"
              >
                <LocateFixed class="h-4 w-4" />
                Use current location
              </button>
            </div>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Address line</label>
            <input
              v-model="form.address_line"
              type="text"
              placeholder="Street, building, unit, campus..."
              class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm"
            >
            <p v-if="form.errors.address_line" class="mt-1 text-xs text-rose-600">{{ form.errors.address_line }}</p>
          </div>

          <div class="grid gap-4 md:grid-cols-3">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Latitude</label>
              <input v-model="form.latitude" type="number" step="0.000001" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm">
              <p v-if="form.errors.latitude" class="mt-1 text-xs text-rose-600">{{ form.errors.latitude }}</p>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Longitude</label>
              <input v-model="form.longitude" type="number" step="0.000001" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm">
              <p v-if="form.errors.longitude" class="mt-1 text-xs text-rose-600">{{ form.errors.longitude }}</p>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Google Place ID (optional)</label>
              <input v-model="form.google_place_id" type="text" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm">
              <p v-if="form.errors.google_place_id" class="mt-1 text-xs text-rose-600">{{ form.errors.google_place_id }}</p>
            </div>
          </div>

          <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input v-model="form.is_default" type="checkbox" class="rounded border-slate-300">
            Save as default address
          </label>

          <div class="flex justify-end">
            <button
              type="submit"
              class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
              :disabled="form.processing"
            >
              {{ form.processing ? 'Saving...' : 'Save and continue' }}
            </button>
          </div>
        </form>
      </section>
    </div>
  </Layout>
</template>

<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { BookMarked, LocateFixed, MapPinHouse, PlusCircle } from '@lucide/vue';
import Layout from '@/Shared/Layout.vue';

const props = defineProps({
  addresses: {
    type: Array,
    default: () => [],
  },
  selectedAddressId: {
    type: Number,
    default: null,
  },
});

const form = useForm({
  label: 'Home',
  address_line: '',
  google_place_id: '',
  latitude: '',
  longitude: '',
  is_default: props.addresses.length === 0,
});

const selectAddress = (addressId) => {
  router.post(`/addresses/${addressId}/use`);
};

const useCurrentLocation = () => {
  if (!navigator.geolocation) {
    return;
  }

  navigator.geolocation.getCurrentPosition((position) => {
    form.latitude = Number(position.coords.latitude).toFixed(6);
    form.longitude = Number(position.coords.longitude).toFixed(6);
  });
};

const saveAddress = () => {
  form.post('/addresses');
};
</script>
