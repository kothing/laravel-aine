import Vue from "vue";
import VCalendar from "v-calendar";
import VSelect from "vue-select";
import "vue-select/dist/vue-select.css";
import "./vendor/fontawesome-pro/css/all.min.css";

import "./utils/directives";
import "./utils/filters";
import "./plugins/colorpicker";

window.axios = require("axios");
window.axios.defaults.headers.common = {
  "X-Requested-With": "XMLHttpRequest",
  "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
};

// aineForm
Vue.component("aineForm", require("./form/Form.vue").default);

// VCalendar
Vue.use(VCalendar, {
  componentPrefix: "v",
});

// vSelect
Vue.component("v-select", VSelect);

new Vue({
  el: "#aineForm",
});
