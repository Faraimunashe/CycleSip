<template>
  <Head title="Checkout" />

  <Layout>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
      <div class="space-y-1">
        <p class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">
          <ShieldCheck class="h-3.5 w-3.5" />
          Secure checkout
        </p>
        <h2 class="inline-flex items-center gap-2 text-2xl font-semibold">
          <ShoppingBasket class="h-6 w-6 text-indigo-600" />
          Checkout
        </h2>
        <p class="text-sm text-slate-600">Review cart items and place your order.</p>
      </div>
      <Link href="/products" class="rounded-xl border border-indigo-100 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">
        <span class="inline-flex items-center gap-1.5">
          <ChevronLeft class="h-4 w-4" />
          Continue shopping
        </span>
      </Link>
    </div>

    <div v-if="cart.line_count === 0" class="glass rounded-2xl p-5 text-slate-600 shadow-sm">
      Cart is empty. Add items from products to continue.
    </div>

    <div v-else class="space-y-5">
      <section class="glass rounded-2xl p-5 shadow-sm">
        <div class="mb-4 grid gap-3 md:grid-cols-[auto_minmax(0,280px)] md:items-center">
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="rounded-xl px-3 py-2 text-sm font-semibold"
              :class="form.store_scope === 'all' ? 'bg-indigo-600 text-white' : 'border border-indigo-100 text-slate-700 hover:bg-indigo-50'"
              @click="form.store_scope = 'all'"
            >
              <span class="inline-flex items-center gap-1.5">
                <Store class="h-4 w-4" />
                Checkout all stores
              </span>
            </button>
            <button
              type="button"
              class="rounded-xl px-3 py-2 text-sm font-semibold"
              :class="form.store_scope === 'single' ? 'bg-indigo-600 text-white' : 'border border-indigo-100 text-slate-700 hover:bg-indigo-50'"
              @click="form.store_scope = 'single'"
            >
              <span class="inline-flex items-center gap-1.5">
                <MapPin class="h-4 w-4" />
                One store only
              </span>
            </button>
          </div>

          <select
            v-if="form.store_scope === 'single'"
            v-model="form.selected_store_id"
            class="rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"
          >
            <option :value="null">Select store</option>
            <option v-for="store in cart.stores" :key="store.id" :value="store.id">{{ store.name }}</option>
          </select>
        </div>
        <p v-if="form.errors.selected_store_id" class="text-xs text-rose-600">{{ form.errors.selected_store_id }}</p>

        <div class="space-y-4">
          <article v-for="store in visibleStores" :key="store.id" class="rounded-xl border border-indigo-100/80 bg-white/70 p-4 dark:border-slate-700/45 dark:bg-slate-900/40">
            <h3 class="inline-flex items-center gap-2 font-semibold text-slate-900 dark:text-slate-100">
              <Store class="h-4 w-4 text-indigo-600" />
              {{ store.name }}
            </h3>
            <p class="inline-flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
              <MapPin class="h-3.5 w-3.5" />
              {{ store.address || 'Address unavailable' }}
            </p>

            <div class="mt-3 space-y-2">
              <div
                v-for="item in store.items"
                :key="item.store_product_id"
                class="grid grid-cols-[1fr_auto] items-center gap-3 rounded-lg border border-slate-100 px-3 py-2 dark:border-slate-800"
              >
                <div class="flex items-center gap-3">
                  <EntityImage
                    :image-url="item.image_url"
                    :alt="`${item.product_name} image`"
                    placeholder-label="Product"
                    img-class="h-10 w-10 rounded-lg border border-indigo-100 object-cover"
                    fallback-class="flex h-10 w-10 items-center justify-center rounded-lg border border-dashed border-indigo-200 bg-indigo-50/70"
                  />
                  <div>
                    <p class="text-sm font-medium">{{ item.product_name }}</p>
                    <p class="inline-flex items-center gap-1 text-xs text-slate-500">
                      <CircleDollarSign class="h-3.5 w-3.5" />
                      ${{ Number(item.unit_price).toFixed(2) }} each
                    </p>
                  </div>
                </div>

                <div class="flex items-center gap-2">
                  <div class="inline-flex items-center rounded-xl border border-indigo-100 bg-white/95">
                    <button
                      type="button"
                      class="h-8 w-8 rounded-l-xl text-slate-700 hover:bg-indigo-50 disabled:opacity-50"
                      :disabled="getQuantity(item.store_product_id) <= 0"
                      @click="decreaseQuantity(item.store_product_id, item)"
                    >
                      <Minus class="mx-auto h-4 w-4" />
                    </button>
                    <span class="min-w-7 px-1 text-center text-sm font-semibold text-slate-800">
                      {{ getQuantity(item.store_product_id) }}
                    </span>
                    <button
                      type="button"
                      class="h-8 w-8 rounded-r-xl text-slate-700 hover:bg-indigo-50 disabled:opacity-50"
                      :disabled="getQuantity(item.store_product_id) >= item.stock_quantity"
                      @click="increaseQuantity(item.store_product_id, item.stock_quantity, item)"
                    >
                      <Plus class="mx-auto h-4 w-4" />
                    </button>
                  </div>
                  <button
                    type="button"
                    class="rounded border border-rose-200 px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50"
                    @click="removeItem(item)"
                  >
                    <span class="inline-flex items-center gap-1">
                      <Trash2 class="h-3.5 w-3.5" />
                      Remove
                    </span>
                  </button>
                </div>
              </div>
            </div>
          </article>
        </div>
      </section>

      <form class="glass grid gap-4 rounded-2xl p-5 shadow-sm" @submit.prevent="submitCheckout">
        <div class="rounded-xl border border-indigo-100/80 bg-white/85 p-4">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <p class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-800">
                <MapPinHouse class="h-4 w-4 text-indigo-600" />
                Deliver to: {{ selectedAddress.label }}
              </p>
              <p class="mt-1 text-sm text-slate-600">{{ selectedAddress.address_line }}</p>
              <p v-if="selectedAddress.latitude && selectedAddress.longitude" class="mt-1 text-xs text-slate-500">
                {{ selectedAddress.latitude }}, {{ selectedAddress.longitude }}
              </p>
            </div>
            <Link href="/addresses/select" class="rounded-lg border border-indigo-100 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
              Change address
            </Link>
          </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <label class="mb-1 inline-flex items-center gap-1.5 text-sm font-medium text-slate-700">
              <Phone class="h-4 w-4 text-indigo-600" />
              Your phone (optional)
            </label>
            <input v-model="form.customer_phone" type="text" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
            <p v-if="form.errors.customer_phone" class="mt-1 text-xs text-rose-600">{{ form.errors.customer_phone }}</p>
          </div>
          <div>
            <label class="mb-1 inline-flex items-center gap-1.5 text-sm font-medium text-slate-700">
              <MessageSquareQuote class="h-4 w-4 text-indigo-600" />
              Rider instructions
            </label>
            <input
              v-model="form.delivery_instructions"
              type="text"
              placeholder="Call at gate, leave with security, floor/room details..."
              class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70"
            >
            <p v-if="form.errors.delivery_instructions" class="mt-1 text-xs text-rose-600">{{ form.errors.delivery_instructions }}</p>
          </div>
        </div>

        <div class="rounded-xl border border-indigo-100/80 bg-white/85 p-4">
          <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
            <input v-model="form.delivering_for_someone" type="checkbox" class="rounded border-slate-300">
            Delivering to someone else
          </label>

          <div v-if="form.delivering_for_someone" class="mt-3 grid gap-3 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Recipient name</label>
              <input v-model="form.recipient_name" type="text" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm">
              <p v-if="form.errors.recipient_name" class="mt-1 text-xs text-rose-600">{{ form.errors.recipient_name }}</p>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Recipient phone</label>
              <input v-model="form.recipient_phone" type="text" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm">
              <p v-if="form.errors.recipient_phone" class="mt-1 text-xs text-rose-600">{{ form.errors.recipient_phone }}</p>
            </div>
          </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <label class="mb-1 inline-flex items-center gap-1.5 text-sm font-medium text-slate-700">
              <Wallet class="h-4 w-4 text-indigo-600" />
              Payment method
            </label>
            <select v-model="form.payment_method" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
              <option v-for="method in paymentMethods" :key="method.code" :value="method.code">
                {{ method.name }} — {{ method.timing === 'prepay' ? 'Pay now (mobile only)' : 'Pay on delivery' }}
              </option>
            </select>
            <p v-if="selectedPaymentMethod?.timing === 'prepay'" class="mt-2 text-xs text-amber-700">
              Prepaid methods are completed in the mobile app. Choose cash or card swipe to place orders on the web.
            </p>
          </div>
          <div>
            <label class="mb-1 inline-flex items-center gap-1.5 text-sm font-medium text-slate-700">
              <FileText class="h-4 w-4 text-indigo-600" />
              Notes (optional)
            </label>
            <input v-model="form.notes" type="text" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950/70">
          </div>
        </div>

        <div class="flex items-center justify-between">
          <p class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-700">
            <ReceiptText class="h-4 w-4 text-indigo-600" />
            Subtotal: ${{ checkoutSubtotal.toFixed(2) }}
          </p>
          <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" :disabled="form.processing">
            <span class="inline-flex items-center gap-1.5">
              <BadgeCheck class="h-4 w-4" />
              {{ form.processing ? 'Placing order...' : 'Place order(s)' }}
            </span>
          </button>
        </div>
        <p v-if="form.errors.cart" class="text-xs text-rose-600">{{ form.errors.cart }}</p>
      </form>
    </div>
  </Layout>
</template>

<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
  BadgeCheck,
  ChevronLeft,
  CircleDollarSign,
  FileText,
  MapPin,
  MapPinHouse,
  MessageSquareQuote,
  Minus,
  Phone,
  Plus,
  ReceiptText,
  ShieldCheck,
  ShoppingBasket,
  Store,
  Trash2,
  Wallet,
} from '@lucide/vue';
import { computed, reactive } from 'vue';
import EntityImage from '@/Components/Admin/EntityImage.vue';
import Layout from '@/Shared/Layout.vue';

const props = defineProps({
  cart: {
    type: Object,
    required: true,
  },
  paymentMethods: {
    type: Array,
    default: () => [],
  },
  selectedAddress: {
    type: Object,
    required: true,
  },
});

const form = useForm({
  customer_phone: '',
  delivery_instructions: '',
  notes: '',
  payment_method: props.paymentMethods[0]?.code ?? 'cash',
  store_scope: 'all',
  selected_store_id: null,
  delivering_for_someone: false,
  recipient_name: '',
  recipient_phone: '',
});

const selectedPaymentMethod = computed(() =>
  props.paymentMethods.find((method) => method.code === form.payment_method) ?? null,
);

const quantities = reactive(
  Object.fromEntries(
    (props.cart.stores || [])
      .flatMap(store => store.items.map(item => [item.store_product_id, item.quantity])),
  ),
);

const visibleStores = computed(() => {
  if (form.store_scope === 'single' && form.selected_store_id) {
    return props.cart.stores.filter(store => store.id === form.selected_store_id);
  }

  return props.cart.stores;
});

const checkoutSubtotal = computed(() =>
  visibleStores.value.reduce(
    (carry, store) => carry + store.items.reduce((sum, item) => sum + (Number(item.unit_price) * Number(item.quantity)), 0),
    0,
  ),
);

const getQuantity = (storeProductId) => {
  const value = Number(quantities[storeProductId] || 0);

  return Number.isFinite(value) && value > 0 ? Math.floor(value) : 0;
};

const increaseQuantity = (storeProductId, maxStock, item) => {
  quantities[storeProductId] = Math.min(getQuantity(storeProductId) + 1, Math.max(1, Number(maxStock)));
  updateQuantity(item);
};

const decreaseQuantity = (storeProductId, item) => {
  quantities[storeProductId] = Math.max(0, getQuantity(storeProductId) - 1);
  updateQuantity(item);
};

const updateQuantity = item => {
  router.patch(`/cart/items/${item.store_product_id}`, {
    quantity: getQuantity(item.store_product_id),
  }, {
    preserveScroll: true,
  });
};

const removeItem = item => {
  router.delete(`/cart/items/${item.store_product_id}`, {
    preserveScroll: true,
  });
};

const submitCheckout = () => {
  form.post('/checkout');
};
</script>
