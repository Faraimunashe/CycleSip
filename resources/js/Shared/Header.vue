<template>
  <header class="sticky top-0 z-40 border-b border-indigo-100/80 bg-white/88 backdrop-blur-xl dark:border-slate-700/40 dark:bg-slate-900/60">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3">
      <div class="flex items-center gap-3">
        <BrandMark />
        <div>
          <h1 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Cycle Sip</h1>
          <p class="text-xs text-slate-500 dark:text-slate-400">Operations Marketplace</p>
        </div>
      </div>
      <nav class="flex flex-wrap items-center gap-2 text-sm">
        <Link
          v-if="isCustomer"
          href="/products"
          class="rounded px-3 py-2 text-slate-700 hover:bg-indigo-50 dark:text-slate-200 dark:hover:bg-indigo-500/20"
        >
          Products
        </Link>
        <Link
          v-if="isCustomer"
          href="/orders"
          class="rounded px-3 py-2 text-slate-700 hover:bg-indigo-50 dark:text-slate-200 dark:hover:bg-indigo-500/20"
        >
          My Orders
        </Link>
        <Link
          v-if="isRider"
          href="/rider/orders/available"
          class="rounded px-3 py-2 text-slate-700 hover:bg-indigo-50 dark:text-slate-200 dark:hover:bg-indigo-500/20"
        >
          Available Orders
        </Link>
        <Link
          v-if="isRider"
          href="/rider/orders/history"
          class="rounded px-3 py-2 text-slate-700 hover:bg-indigo-50 dark:text-slate-200 dark:hover:bg-indigo-500/20"
        >
          Worked-On Orders
        </Link>
        <Link
          v-if="isAdmin"
          href="/admin/dashboard"
          class="rounded px-3 py-2 text-slate-700 hover:bg-indigo-50 dark:text-slate-200 dark:hover:bg-indigo-500/20"
        >
          Admin
        </Link>

        <Link
          v-if="!user"
          href="/login"
          class="rounded bg-indigo-600 px-3 py-2 text-white hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400"
        >
          Login
        </Link>
        <Link
          v-if="!user"
          href="/register"
          class="rounded border border-indigo-100 px-3 py-2 text-slate-700 hover:bg-indigo-50 dark:border-indigo-500/35 dark:text-slate-200 dark:hover:bg-indigo-500/20"
        >
          Register
        </Link>

        <button
          v-if="user"
          type="button"
          class="cursor-pointer rounded border border-indigo-100 px-3 py-2 text-slate-700 hover:bg-indigo-50 dark:border-indigo-500/35 dark:text-slate-200 dark:hover:bg-indigo-500/20"
          @click="logout"
        >
          Logout
        </button>
      </nav>
    </div>
  </header>
</template>

<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import BrandMark from '@/Shared/BrandMark.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const isAdmin = computed(() => user.value?.roles?.some(role => ['super-admin', 'admin', 'operations-manager'].includes(role)));
const isCustomer = computed(() => user.value?.roles?.includes('customer'));
const isRider = computed(() => user.value?.roles?.includes('rider'));

const logout = () => {
  router.post('/logout');
};
</script>
