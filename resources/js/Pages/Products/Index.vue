<template>
  <Head title="Products" />

  <Layout>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
      <div class="space-y-1">
        <p class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">
          <Sparkles class="h-3.5 w-3.5" />
          Storefront
        </p>
        <h2 class="inline-flex items-center gap-2 text-2xl font-semibold">
          <ShoppingBag class="h-6 w-6 text-indigo-600" />
          Products
        </h2>
        <p class="text-sm text-slate-600">Build your cart first, then checkout from all stores or one specific store.</p>
      </div>

      <div class="glass rounded-2xl px-4 py-3 text-sm shadow-sm">
        <p class="inline-flex items-center gap-2 font-semibold text-slate-800">
          <ShoppingCart class="h-4 w-4 text-indigo-600" />
          Cart: {{ cartSummary.item_count }} item(s)
        </p>
        <Link
          href="/checkout"
          class="mt-1 inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-500"
        >
          <ArrowRight class="h-4 w-4" />
          Proceed to checkout
        </Link>
      </div>
    </div>

    <section class="mb-6 space-y-3 rounded-2xl border border-indigo-100/90 bg-white/90 p-4 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/55">
      <div class="grid gap-3 md:grid-cols-[auto_minmax(0,280px)]">
        <div class="flex flex-wrap items-center gap-2">
          <span class="inline-flex items-center gap-1 rounded-xl border border-indigo-100 bg-white/85 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600">
            <SlidersHorizontal class="h-3.5 w-3.5 text-indigo-600" />
            Filters
          </span>
          <button
            type="button"
            class="rounded-xl px-3 py-2 text-sm font-semibold"
            :class="storeMode === 'all' ? 'bg-indigo-600 text-white' : 'border border-indigo-100 text-slate-700 hover:bg-indigo-50'"
            @click="storeMode = 'all'"
          >
            <span class="inline-flex items-center gap-1.5">
              <Store class="h-4 w-4" />
              All Stores
            </span>
          </button>
          <button
            type="button"
            class="rounded-xl px-3 py-2 text-sm font-semibold"
            :class="storeMode === 'single' ? 'bg-indigo-600 text-white' : 'border border-indigo-100 text-slate-700 hover:bg-indigo-50'"
            @click="storeMode = 'single'"
          >
            <span class="inline-flex items-center gap-1.5">
              <MapPin class="h-4 w-4" />
              Choose Store
            </span>
          </button>
        </div>

        <select
          v-if="storeMode === 'single'"
          v-model="selectedStoreId"
          class="rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"
        >
          <option :value="null">Select store</option>
          <option v-for="store in stores" :key="store.id" :value="store.id">{{ store.name }}</option>
        </select>
      </div>

      <div class="grid gap-3 md:grid-cols-2">
        <div class="relative">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by product name..."
            class="w-full rounded-xl border border-indigo-100 bg-white py-2 pl-9 pr-3 text-sm dark:border-slate-700 dark:bg-slate-950/70"
          >
        </div>

        <div class="relative">
          <Tag class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <select
            v-model="selectedCategory"
            class="w-full rounded-xl border border-indigo-100 bg-white py-2 pl-9 pr-3 text-sm dark:border-slate-700 dark:bg-slate-950/70"
          >
            <option value="">All categories</option>
            <option v-for="category in categoryOptions" :key="category" :value="category">{{ category }}</option>
          </select>
        </div>
      </div>
    </section>

    <div v-if="stores.length === 0" class="glass rounded-2xl p-5 text-slate-600 shadow-sm">
      No active stores with stock found.
    </div>

    <div v-if="filteredProductCards.length === 0" class="glass rounded-2xl p-5 text-slate-600 shadow-sm">
      No products match your current store selection.
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <article
        v-for="card in filteredProductCards"
        :key="card.id"
        class="group glass overflow-hidden rounded-2xl shadow-sm"
      >
        <div class="relative">
          <EntityImage
            :image-url="card.image_url"
            :alt="`${card.product_name} image`"
            placeholder-label="Product"
            img-class="h-52 w-full rounded-none border-0 object-cover transition duration-300 group-hover:scale-[1.02]"
            fallback-class="flex h-52 w-full flex-col items-center justify-center gap-1 rounded-none border-0 bg-indigo-50/70"
          />
          <div class="absolute left-3 top-3 rounded-full bg-white/90 px-3 py-1 text-[11px] font-semibold text-slate-700 shadow-sm">
            <span class="inline-flex items-center gap-1">
              <Store class="h-3.5 w-3.5 text-indigo-600" />
              {{ card.store_name }}
            </span>
          </div>
          <div class="absolute right-3 top-3 rounded-full bg-slate-900/80 px-2.5 py-1 text-[11px] font-semibold text-white">
            <span class="inline-flex items-center gap-1">
              <Package class="h-3.5 w-3.5" />
              Stock {{ card.stock_quantity }}
            </span>
          </div>
        </div>

        <div class="space-y-3 p-4">
          <div>
            <p class="text-lg font-semibold text-slate-900">{{ card.product_name }}</p>
            <p class="line-clamp-2 text-sm text-slate-600">{{ card.description || 'No description available.' }}</p>
          </div>

          <div class="flex items-center justify-between text-sm">
            <p class="inline-flex items-center gap-1 font-semibold text-indigo-700">
              <CircleDollarSign class="h-4 w-4" />
              ${{ card.price.toFixed(2) }}
            </p>
            <p class="inline-flex items-center gap-1 text-slate-500">
              <Tag class="h-3.5 w-3.5" />
              {{ card.category || 'General' }}
            </p>
          </div>

          <div class="flex items-center gap-2">
            <div class="inline-flex items-center rounded-xl border border-indigo-100 bg-white/95">
              <button
                type="button"
                class="h-9 w-9 rounded-l-xl text-sm font-semibold text-slate-700 transition hover:bg-indigo-50 disabled:opacity-50"
                :disabled="getQuantity(card.id) <= 1"
                @click="decreaseQuantity(card.id)"
              >
                <Minus class="mx-auto h-4 w-4" />
              </button>
              <span class="min-w-8 px-1 text-center text-sm font-semibold text-slate-800">
                {{ getQuantity(card.id) }}
              </span>
              <button
                type="button"
                class="h-9 w-9 rounded-r-xl text-sm font-semibold text-slate-700 transition hover:bg-indigo-50 disabled:opacity-50"
                :disabled="getQuantity(card.id) >= card.stock_quantity"
                @click="increaseQuantity(card.id, card.stock_quantity)"
              >
                <Plus class="mx-auto h-4 w-4" />
              </button>
            </div>
            <button
              type="button"
              class="flex-1 rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500"
              @click="addToCart(card)"
            >
              <span class="inline-flex items-center gap-1.5">
                <ShoppingCart class="h-4 w-4" />
                Add to Cart
              </span>
            </button>
          </div>
        </div>
      </article>
    </div>
  </Layout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import {
  ArrowRight,
  CircleDollarSign,
  MapPin,
  Minus,
  Package,
  Plus,
  Search,
  ShoppingBag,
  ShoppingCart,
  SlidersHorizontal,
  Sparkles,
  Store,
  Tag,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import EntityImage from '@/Components/Admin/EntityImage.vue';
import Layout from '@/Shared/Layout.vue';

const props = defineProps({
  stores: {
    type: Array,
    default: () => [],
  },
  cartSummary: {
    type: Object,
    default: () => ({ item_count: 0, line_count: 0 }),
  },
});

const quantities = ref({});
const storeMode = ref('all');
const selectedStoreId = ref(null);
const searchQuery = ref('');
const selectedCategory = ref('');

const visibleStores = computed(() => {
  if (storeMode.value === 'single' && selectedStoreId.value) {
    return props.stores.filter(store => store.id === selectedStoreId.value);
  }

  return props.stores;
});

const productCards = computed(() =>
  visibleStores.value.flatMap(store =>
    store.inventory.map(item => ({
      ...item,
      store_id: store.id,
      store_name: store.name,
      store_logo_url: store.logo_url,
      store_address: store.address,
    })),
  ),
);

const categoryOptions = computed(() =>
  [...new Set(productCards.value.map(card => card.category || 'General'))].sort((a, b) => a.localeCompare(b)),
);

const filteredProductCards = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();

  return productCards.value.filter((card) => {
    const matchesName = query.length === 0
      || card.product_name.toLowerCase().includes(query);

    const cardCategory = card.category || 'General';
    const matchesCategory = selectedCategory.value === ''
      || cardCategory === selectedCategory.value;

    return matchesName && matchesCategory;
  });
});

const getQuantity = (productId) => {
  const value = Number(quantities.value[productId] || 1);

  return Number.isFinite(value) && value > 0 ? Math.floor(value) : 1;
};

const increaseQuantity = (productId, maxStock) => {
  quantities.value[productId] = Math.min(getQuantity(productId) + 1, Math.max(1, Number(maxStock)));
};

const decreaseQuantity = (productId) => {
  quantities.value[productId] = Math.max(1, getQuantity(productId) - 1);
};

const addToCart = (item) => {
  const quantity = Math.min(getQuantity(item.id), Math.max(1, Number(item.stock_quantity)));

  router.post('/cart/items', {
    store_product_id: item.id,
    quantity,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      quantities.value[item.id] = 1;
    },
  });
};
</script>
