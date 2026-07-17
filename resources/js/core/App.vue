<script setup lang="ts">
import Footer from './Footer.vue';
import Header from './Header.vue';
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-gray-800">
    <Header />

    <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <slot />
    </main>

    <Footer />
  </div>
</template>
