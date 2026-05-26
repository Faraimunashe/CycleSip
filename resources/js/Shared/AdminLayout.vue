<template>
  <Layout>
    <div class="grid gap-6 lg:grid-cols-[270px_1fr]">
      <aside class="h-fit rounded-2xl border border-indigo-100/90 bg-white/88 p-4 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55">
        <div class="mb-4 rounded-xl border border-indigo-100 bg-white/95 p-4 shadow-sm backdrop-blur dark:border-indigo-500/35 dark:bg-slate-900/65">
          <div class="flex items-center gap-3">
            <BrandMark
              img-class="h-10 w-10 rounded-xl border border-indigo-200/70 object-cover shadow-sm dark:border-indigo-500/35"
              fallback-class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm"
            />
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-700 dark:text-indigo-300">CycleSip Ops</p>
              <h3 class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">Command Center</h3>
            </div>
          </div>
          <p class="mt-2 text-xs text-slate-600 dark:text-slate-400">Delivery intelligence and operations control</p>
        </div>

        <p class="mb-2 px-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Operations</p>
        <nav class="space-y-1.5">
          <Link
            v-for="item in menu"
            :key="item.href"
            :href="item.href"
            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
            :class="item.href === currentPath
              ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30'
              : 'text-slate-700 hover:bg-indigo-50 dark:text-slate-200 dark:hover:bg-indigo-500/20'"
          >
            <component :is="item.icon" class="h-4 w-4" />
            {{ item.label }}
          </Link>
        </nav>
      </aside>
      <section class="space-y-4">
        <slot />
      </section>
    </div>
  </Layout>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
  LayoutDashboard,
  ClipboardList,
  Package,
  Bike,
  Users,
  Wallet,
  Store,
  MapPinned,
  CreditCard,
} from '@lucide/vue';
import BrandMark from '@/Shared/BrandMark.vue';
import Layout from '@/Shared/Layout.vue';

const page = usePage();
const currentPath = computed(() => page.url.split('?')[0]);

const menu = [
  { label: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard },
  { label: 'Orders', href: '/admin/orders', icon: ClipboardList },
  { label: 'Riders', href: '/admin/riders', icon: Bike },
  { label: 'Stores', href: '/admin/stores', icon: Store },
  { label: 'Products', href: '/admin/products', icon: Package },
  { label: 'Zones', href: '/admin/zones', icon: MapPinned },
  { label: 'Finance', href: '/admin/finance', icon: Wallet },
  { label: 'Payment Methods', href: '/admin/payment-methods', icon: CreditCard },
  { label: 'Customers', href: '/admin/customers', icon: Users },
];
</script>
