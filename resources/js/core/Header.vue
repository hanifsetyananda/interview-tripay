<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { home, cart } from '@/routes';
import { index } from '@/routes/products';
import { computed } from 'vue';

const page = usePage();
const currentUrl = computed(() => page.url);

const navItems = [
  { label: 'Dashboard', href: home().url },
  { label: 'Products', href: index().url },
  { label: 'Cart', href: cart().url },
];
</script>

<template>
  <header class="bg-white shadow-sm border-b border-gray-200">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
      <Link :href="home().url" class="text-xl font-semibold tracking-tight text-gray-900">
        TriPayShop
      </Link>

      <nav class="flex items-center gap-6 text-sm font-medium">
        <Link v-for="item in navItems" :key="item.href" :href="item.href" :class="[
          currentUrl === item.href
            ? 'text-blue-600 font-semibold'
            : 'text-gray-600 hover:text-gray-900',
        ]">
          {{ item.label }}
        </Link>
      </nav>
    </div>
  </header>
</template>
