/**
 * Frontend app entry
 */

import Vue from "vue";

// Routes
import router from "./frontend/routes";

// Store
import store from "./frontend/store";

// app
Vue.component("app", require("./frontend/Layout.vue").default);

/**
 * Render
 */
new Vue({
  el: "#app",
  router,
  store,
});
