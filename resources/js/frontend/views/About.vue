<template>
  <div class="app__documents m-3 p-3">
    <div class="flex flex-col items-center justify-center text-center my-5">
        <img class="w-12 w-12 mx-auto" src="/images/logo-64x64-dark.svg">
        <p class="mt-4">「AineCMS」</p>
        <p class="mt-4">Aine is a self-hosted headless Content Management Framework built with Laravel and VueJs. It provides a clean and easy to use interface and a powerful Content API</p>
        <p class="mt-4"><a class="text-blue-500" href="/documents" target="_blank">Documents</a></p>
        <p><a class="text-blue-500" href="https://github.com/kothing/laravel-aine" target="_blank">Aine on GitHub</a></p>
    </div>
  </div>
</template>

<script>
export default {
    components: {},

    computed: {
        settings() {
            return this.$store.getters.settings;
        },
    },
};
</script>
