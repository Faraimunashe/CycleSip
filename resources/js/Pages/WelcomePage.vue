<template>
  <Head title="Welcome" />
  <Layout>
    <div class="glass rounded-2xl p-6 shadow-sm">
      <h2 class="text-2xl font-semibold">Welcome to Cycle Sip</h2>
      <p class="mt-2 text-slate-600">Use login or register to start placing orders.</p>
    </div>
  </Layout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import Layout from '@/Shared/Layout.vue';
</script>
