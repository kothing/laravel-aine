import { createApp } from "vue";
import { createPinia } from "pinia";
import VCalendar from "v-calendar";
import "v-calendar/dist/style.css";
import VSelect from "vue-select";
import "vue-select/dist/vue-select.css";
import "./vendor/fontawesome-pro/css/all.min.css";

import { registerDirectives } from "./utils/directives";
import { registerFilters } from "./utils/filters";

/**
 * We'll load the axios HTTP library
 */
import axios from "axios";
window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
window.axios.defaults.headers.common["X-CSRF-TOKEN"] = document.querySelector('meta[name="csrf-token"]').content;

// aineForm component
import AineForm from "./components/Form.vue";

/**
 * Create Vue app
 */
const app = createApp(AineForm);
const pinia = createPinia();

app.use(pinia);
app.use(VCalendar, {});

// Register directives and filters
registerDirectives(app);
registerFilters(app);

// Global components
app.component("v-select", VSelect);

// Mount
app.mount("#aineForm");
