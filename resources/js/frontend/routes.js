import Vue from "vue";
import VueRouter from "vue-router";
import store from "./store";

Vue.use(VueRouter);

const Home = () => import(/* webpackChunkName: "frontend.home" */ "./views/Home");
/**
 * Routes
 */
const routes = [
    { path: "/", name: "home", component: Home },
];

const router = new VueRouter({
    routes: routes,
});

export default router;
