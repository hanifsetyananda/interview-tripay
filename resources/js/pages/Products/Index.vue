<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { create, destroy, edit } from '@/routes/products';
import App from '@/core/App.vue';
import { Product } from '@/types/products';



defineProps<{
    products: Product[];
    success?: string;
}>();

const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

function remove(product: Product): void {
    if (window.confirm(`Hapus produk ${product.name}?`)) {
        router.delete(destroy(product).url);
    }
}
</script>

<template>
    <App>
        <main class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">

            <Head title="Products" />

            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">Products</h1>
                    <p class="mt-1 text-sm text-slate-600">Kelola daftar produk Anda.</p>
                </div>

                <Link :href="create().url"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                    Tambah produk
                </Link>
            </div>

            <p v-if="success" class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ success }}
            </p>

            <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div v-if="products.length === 0" class="p-8 text-center text-sm text-slate-500">
                    Belum ada produk. Tambahkan produk pertama Anda.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-4 py-3 font-medium">SKU</th>
                                <th class="px-4 py-3 font-medium">Nama</th>
                                <th class="px-4 py-3 font-medium">Harga</th>
                                <th class="px-4 py-3 font-medium">Reference</th>
                                <th class="px-4 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-slate-700">
                            <tr v-for="product in products" :key="product.id">
                                <td class="px-4 py-3 font-medium">{{ product.sku }}</td>
                                <td class="px-4 py-3">{{ product.name }}</td>
                                <td class="px-4 py-3">{{ currency.format(product.price) }}</td>
                                <td class="px-4 py-3">{{ product.reference }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-3">
                                        <Link :href="edit(product).url"
                                            class="font-medium text-sky-700 hover:text-sky-900">
                                            Edit
                                        </Link>
                                        <button type="button" class="font-medium text-rose-700 hover:text-rose-900"
                                            @click="remove(product)">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </App>

</template>
