<template>
  <Head :title="`Order #${order.id}`" />

  <Layout>
    <div class="space-y-4">
      <header class="glass rounded-2xl p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-2xl font-semibold text-slate-900">Order #{{ order.id }}</h2>
            <p class="text-sm text-slate-600">Complete order details, timeline, and ratings.</p>
          </div>
          <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold uppercase text-indigo-700">
            {{ prettyStatus(order.status) }}
          </span>
        </div>
      </header>

      <p v-if="liveMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">
        {{ liveMessage }}
      </p>

      <section class="grid gap-4 md:grid-cols-2">
        <article class="glass rounded-2xl p-5 shadow-sm">
          <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">Delivery & Contacts</h3>
          <div class="space-y-2 text-sm text-slate-700">
            <p><span class="font-semibold">Address:</span> {{ order.delivery_address || 'N/A' }}</p>
            <p><span class="font-semibold">Your phone:</span> {{ order.customer_phone || 'N/A' }}</p>
            <p><span class="font-semibold">Recipient:</span> {{ order.recipient_name || 'Self' }}</p>
            <p><span class="font-semibold">Recipient phone:</span> {{ order.recipient_phone || order.customer_phone || 'N/A' }}</p>
            <p><span class="font-semibold">Instructions:</span> {{ order.delivery_instructions || 'None' }}</p>
            <p><span class="font-semibold">Notes:</span> {{ order.notes || 'None' }}</p>
          </div>
        </article>

        <article class="glass rounded-2xl p-5 shadow-sm">
          <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">Order Summary</h3>
          <div class="space-y-2 text-sm text-slate-700">
            <p><span class="font-semibold">Store:</span> {{ order.store?.name || 'N/A' }}</p>
            <p><span class="font-semibold">Store address:</span> {{ order.store?.address || 'N/A' }}</p>
            <p><span class="font-semibold">Rider:</span> {{ order.rider?.name || 'Not assigned yet' }}</p>
            <p><span class="font-semibold">Rider phone:</span> {{ order.rider?.phone || 'N/A' }}</p>
            <p><span class="font-semibold">Payment:</span> {{ order.payment_method }} ({{ order.payment_status }})</p>
            <p><span class="font-semibold">Subtotal:</span> ${{ Number(order.subtotal_amount || 0).toFixed(2) }}</p>
            <p><span class="font-semibold">Delivery fee:</span> ${{ Number(order.delivery_fee || 0).toFixed(2) }}</p>
            <p><span class="font-semibold">Total:</span> ${{ Number(order.total_amount || 0).toFixed(2) }}</p>
          </div>
        </article>
      </section>

      <section class="glass rounded-2xl p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">Products Bought</h3>
        <div v-if="!order.items?.length" class="text-sm text-slate-600">
          No items found.
        </div>
        <div v-else class="space-y-2">
          <article
            v-for="item in order.items"
            :key="item.id"
            class="rounded-xl border border-indigo-100 bg-white/90 px-3 py-2"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <p class="text-sm font-semibold text-slate-900">{{ item.name || `Product #${item.product_id}` }}</p>
              <p class="text-sm text-slate-700">Qty {{ item.quantity }}</p>
            </div>
            <p class="text-xs text-slate-500">Unit ${{ Number(item.unit_price || 0).toFixed(2) }}</p>
            <p class="text-sm font-semibold text-slate-800">${{ Number(item.line_total || 0).toFixed(2) }}</p>
          </article>
        </div>
      </section>

      <section class="glass rounded-2xl p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">Order progress</h3>
        <div v-if="!order.progress_steps?.length" class="text-sm text-slate-600">
          No progress updates yet.
        </div>
        <ol v-else class="space-y-3">
          <li
            v-for="step in order.progress_steps"
            :key="step.key"
            class="flex items-start gap-3 rounded-xl border border-indigo-100 bg-white/90 px-3 py-2"
          >
            <span
              class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold"
              :class="step.failed
                ? 'bg-rose-100 text-rose-700'
                : step.completed
                  ? 'bg-emerald-100 text-emerald-700'
                  : 'bg-slate-100 text-slate-500'"
            >
              {{ step.failed ? '!' : step.completed ? '✓' : '·' }}
            </span>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm font-semibold text-slate-900">{{ step.label }}</p>
                <p v-if="step.at" class="text-xs text-slate-500">{{ formatDate(step.at) }}</p>
              </div>
              <p v-if="step.type === 'payment'" class="text-xs text-slate-500">Payment step</p>
            </div>
          </li>
        </ol>
      </section>

      <section class="glass rounded-2xl p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">Timeline</h3>
        <div v-if="!order.timeline?.length" class="text-sm text-slate-600">
          No timeline updates yet.
        </div>
        <ul v-else class="space-y-2">
          <li
            v-for="entry in order.timeline"
            :key="entry.id"
            class="rounded-xl border border-indigo-100 bg-white/90 px-3 py-2"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <p class="text-sm font-semibold text-slate-900">{{ prettyStatus(entry.status) }}</p>
              <p class="text-xs text-slate-500">{{ formatDate(entry.created_at) }}</p>
            </div>
            <p class="text-sm text-slate-600">{{ entry.note || 'No note' }}</p>
            <p class="text-xs text-slate-500">Updated by: {{ entry.changed_by || 'System' }}</p>
          </li>
        </ul>
      </section>

      <section class="grid gap-4 md:grid-cols-2">
        <form class="glass rounded-2xl p-5 shadow-sm" @submit.prevent="submitOrderRating">
          <h3 class="mb-1 text-sm font-semibold uppercase tracking-wide text-slate-600">Rate This Order</h3>
          <p class="mb-3 text-xs text-slate-500">Rate packaging, order accuracy, and overall service quality.</p>
          <p v-if="!order.can_rate" class="mb-3 text-sm text-amber-700">You can rate once order is delivered or completed.</p>

          <div class="space-y-3">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Rating (1-5)</label>
              <select v-model.number="orderRatingForm.rating" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm">
                <option v-for="score in [5,4,3,2,1]" :key="score" :value="score">{{ score }} Star{{ score > 1 ? 's' : '' }}</option>
              </select>
              <p v-if="orderRatingForm.errors.rating" class="mt-1 text-xs text-rose-600">{{ orderRatingForm.errors.rating }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Comment</label>
              <textarea
                v-model="orderRatingForm.comment"
                rows="3"
                maxlength="500"
                class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm"
                placeholder="How was your order experience?"
              />
              <p v-if="orderRatingForm.errors.comment" class="mt-1 text-xs text-rose-600">{{ orderRatingForm.errors.comment }}</p>
            </div>

            <button
              type="submit"
              class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:opacity-50"
              :disabled="!order.can_rate || orderRatingForm.processing"
            >
              {{ order.order_rating ? 'Update Order Rating' : 'Submit Order Rating' }}
            </button>
          </div>
        </form>

        <form class="glass rounded-2xl p-5 shadow-sm" @submit.prevent="submitRiderRating">
          <h3 class="mb-1 text-sm font-semibold uppercase tracking-wide text-slate-600">Rate Rider</h3>
          <p class="mb-3 text-xs text-slate-500">Rate rider professionalism, communication, and delivery handling.</p>
          <p v-if="!order.rider?.id" class="mb-3 text-sm text-amber-700">No rider assigned for this order.</p>
          <p v-if="!order.can_rate" class="mb-3 text-sm text-amber-700">You can rate once order is delivered or completed.</p>

          <div class="space-y-3">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Rating (1-5)</label>
              <select v-model.number="riderRatingForm.rating" class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm">
                <option v-for="score in [5,4,3,2,1]" :key="score" :value="score">{{ score }} Star{{ score > 1 ? 's' : '' }}</option>
              </select>
              <p v-if="riderRatingForm.errors.rating" class="mt-1 text-xs text-rose-600">{{ riderRatingForm.errors.rating }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Comment</label>
              <textarea
                v-model="riderRatingForm.comment"
                rows="3"
                maxlength="500"
                class="w-full rounded-xl border border-indigo-100 bg-white px-3 py-2 text-sm"
                placeholder="How was your rider experience?"
              />
              <p v-if="riderRatingForm.errors.comment" class="mt-1 text-xs text-rose-600">{{ riderRatingForm.errors.comment }}</p>
            </div>

            <button
              type="submit"
              class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:opacity-50"
              :disabled="!order.can_rate || !order.rider?.id || riderRatingForm.processing"
            >
              {{ order.rider_rating ? 'Update Rider Rating' : 'Submit Rider Rating' }}
            </button>
          </div>
        </form>
      </section>
    </div>
  </Layout>
</template>

<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Layout from '@/Shared/Layout.vue';
import { useOrderRealtime } from '@/composables/useOrderRealtime';

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
});

const liveMessage = ref('');

useOrderRealtime(props.order.id, ({ type }) => {
  liveMessage.value = type === 'location' ? 'Rider location updated.' : 'Order status updated.';
  router.reload({ only: ['order'], preserveScroll: true });
});

const prettyStatus = (status) => status ? status.replaceAll('_', ' ') : 'unknown';

const formatDate = (value) => {
  if (!value) return 'N/A';
  return new Date(value).toLocaleString();
};

const orderRatingForm = useForm({
  rating: props.order.order_rating?.rating ?? 5,
  comment: props.order.order_rating?.comment ?? '',
});

const riderRatingForm = useForm({
  rating: props.order.rider_rating?.rating ?? 5,
  comment: props.order.rider_rating?.comment ?? '',
});

const submitOrderRating = () => {
  orderRatingForm.post(`/orders/${props.order.id}/ratings/order`, { preserveScroll: true });
};

const submitRiderRating = () => {
  riderRatingForm.post(`/orders/${props.order.id}/ratings/rider`, { preserveScroll: true });
};
</script>
