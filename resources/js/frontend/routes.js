import Vue from "vue";
import VueRouter from "vue-router";
import store from "./store";

Vue.use(VueRouter);

const Home = () => import(/* webpackChunkName: "frontend.home" */ "./views/Home");
const About = () => import(/* webpackChunkName: "frontend.about" */ "./views/About");

/**
 * Routes
 */
const routes = [
    { path: "/", name: "home", component: Home },
    { path: "/about", name: "about", component: About },
];

const router = new VueRouter({
    routes: routes,
});

// Route guard: load settings on first visit
router.beforeEach((to, from, next) => {
    store.dispatch('loadSettings');
    next();
});

export default router;
