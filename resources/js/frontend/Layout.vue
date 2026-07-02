<template>
  <div class="app__container flex h-screen">
    <div class="flex-1 overflow-x-hidden">
      <router-view></router-view>
    </div>
  </div>
</template>

<script>
export default {
  components: {},

  data() {
    return {
      locales: [],
    };
  },

  methods: {
    //
  },

  mounted() {
    //
  },
};
</script>
