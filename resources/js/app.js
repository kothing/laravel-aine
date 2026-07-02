/**
 * Frontend app entry
 */

import Vue from "vue";

// Routes
import router from "./frontend/routes";

// Store
import store from "./frontend/store";

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */
window.axios = require("axios");
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

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
