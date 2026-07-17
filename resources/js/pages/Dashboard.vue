<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import App from '@/core/App.vue';
import { Product } from '@/types/products';

const props = defineProps<{
  products: Product[];
}>();

const formatCurrency = (value: number) =>
  new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value);
</script>

<template>
  <App>

    <Head title="Dashboard" />
    <div class="space-y-6 p-6">
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <p class="text-sm font-medium text-slate-500">Produk tersedia</p>
            <h1 class="text-2xl font-semibold text-slate-900">Dashboard Produk</h1>
          </div>
          <span class="inline-flex w-fit rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700">
            {{ props.products.length }} item
          </span>
        </div>
      </div>

      <div v-if="props.products.length" class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        <article v-for="product in props.products" :key="product.id"
          class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md">
          <div class="mb-4 flex items-start justify-between gap-3">
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ product.sku }}</p>
              <h2 class="mt-1 text-lg font-semibold text-slate-900">{{ product.name }}</h2>
            </div>
            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
              Ready
            </span>
          </div>

          <div class="space-y-3 text-sm text-slate-600">
            <div class="flex items-center justify-between">
              <span>Harga</span>
              <span class="font-semibold text-slate-900">{{ formatCurrency(product.price) }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Reference</span>
              <span class="font-medium text-slate-700">{{ product.reference }}</span>
            </div>
          </div>
        </article>
      </div>

      <div v-else
        class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">
        Belum ada data produk.
      </div>
    </div>
  </App>
</template>