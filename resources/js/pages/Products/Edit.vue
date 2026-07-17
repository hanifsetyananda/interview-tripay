<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { index, update } from '@/routes/products';
import App from '@/core/App.vue';

type Product = {
    id: number;
    sku: string;
    name: string;
    price: number;
    reference: string;
};

const props = defineProps<{
    product: Product;
}>();

const form = useForm({
    sku: props.product.sku,
    name: props.product.name,
    price: props.product.price,
    reference: props.product.reference,
});

function submit(): void {
    form.put(update(props.product).url);
}
</script>

<template>
    <App>

        <Head title="Edit Produk" />
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold text-slate-900">Edit Produk</h1>
            <Link :href="index().url" class="text-sm font-medium text-slate-600 hover:text-slate-900">
                Kembali
            </Link>
        </div>

        <form class="mt-6 grid gap-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
            @submit.prevent="submit">
            <label class="grid gap-2 text-sm font-medium text-slate-700">
                SKU
                <input v-model="form.sku" type="text" class="rounded-lg border border-slate-300 px-3 py-2" />
                <span v-if="form.errors.sku" class="text-sm font-normal text-rose-600">{{ form.errors.sku }}</span>
            </label>

            <label class="grid gap-2 text-sm font-medium text-slate-700">
                Nama produk
                <input v-model="form.name" type="text" class="rounded-lg border border-slate-300 px-3 py-2" />
                <span v-if="form.errors.name" class="text-sm font-normal text-rose-600">{{ form.errors.name
                    }}</span>
            </label>

            <label class="grid gap-2 text-sm font-medium text-slate-700">
                Harga
                <input v-model="form.price" type="number" min="0"
                    class="rounded-lg border border-slate-300 px-3 py-2" />
                <span v-if="form.errors.price" class="text-sm font-normal text-rose-600">{{ form.errors.price
                    }}</span>
            </label>

            <label class="grid gap-2 text-sm font-medium text-slate-700">
                Reference TriPay
                <input v-model="form.reference" type="text" class="rounded-lg border border-slate-300 px-3 py-2" />
                <span v-if="form.errors.reference" class="text-sm font-normal text-rose-600">{{
                    form.errors.reference }}</span>
            </label>

            <button type="submit" :disabled="form.processing"
                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:bg-slate-400">
                {{ form.processing ? 'Menyimpan...' : 'Simpan perubahan' }}
            </button>
        </form>
    </App>
</template>
