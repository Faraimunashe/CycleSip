<template>
  <Head title="Create Product" />

  <AdminLayout>
    <div class="space-y-4">
      <header class="rounded-2xl border border-indigo-100/90 bg-white/95 p-5 shadow-sm backdrop-blur-xl dark:border-slate-700/45 dark:bg-slate-900/60">
        <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Create Product</h2>
        <p class="text-sm text-slate-600 dark:text-slate-400">Add a product with image, category, and merchandising attributes.</p>
      </header>

      <ProductForm :form="form" :categories="categories" submit-label="Create product" @submit="submit" />
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import ProductForm from '@/Components/Admin/ProductForm.vue';
import AdminLayout from '@/Shared/AdminLayout.vue';

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
  categories: {
    type: Array,
    default: () => [],
  },
});

const form = useForm({
  product_category_id: props.product.product_category_id,
  name: props.product.name,
  slug: props.product.slug,
  brand: props.product.brand,
  description: props.product.description,
  image: null,
  remove_image: false,
  is_featured: props.product.is_featured,
  is_promoted: props.product.is_promoted,
  is_active: props.product.is_active,
});

const submit = () => {
  form.post('/admin/products', {
    forceFormData: true,
  });
};
</script>
