import { createRouter, createWebHistory } from "vue-router";
import { useFrontendStore } from "./store";

const Home = () => import("./views/Home.vue");
const Archive = () => import("./views/Archive.vue");
const ArticleDetail = () => import("./views/ArticleDetail.vue");
const ListingDetail = () => import("./views/ListingDetail.vue");
const PageDetail = () => import("./views/PageDetail.vue");
const PagesList = () => import("./views/PagesList.vue");

/**
 * Routes
 *
 * The site is split into two systems:
 *   /content/*    — the CMS project (articles, categories, tags, pages)
 *   /directory/*  — the Business Directory project (listings, categories,
 *                   tags, locations)
 */
const routes = [
    { path: "/", name: "home", component: Home },

    // ---- CMS system (/content) ----
    { path: "/content", name: "content.index", component: Archive, props: { project: "cms", mode: "all" } },
    { path: "/content/pages", name: "content.pages", component: PagesList },
    { path: "/content/featured", name: "content.featured", component: Archive, props: { project: "cms", mode: "featured" } },
    { path: "/content/recommended", name: "content.recommended", component: Archive, props: { project: "cms", mode: "recommended" } },
    { path: "/content/category/:slug", name: "content.category", component: Archive, props: { project: "cms", mode: "category" } },
    { path: "/content/tag/:slug", name: "content.tag", component: Archive, props: { project: "cms", mode: "tag" } },
    { path: "/content/:category/:article", name: "content.article", component: ArticleDetail },
    { path: "/content/:slug", name: "content.page", component: PageDetail },

    // ---- Directory system (/directory) ----
    { path: "/directory", name: "directory.index", component: Archive, props: { project: "directory", mode: "all" } },
    { path: "/directory/featured", name: "directory.featured", component: Archive, props: { project: "directory", mode: "featured" } },
    { path: "/directory/category/:slug", name: "directory.category", component: Archive, props: { project: "directory", mode: "category" } },
    { path: "/directory/tag/:slug", name: "directory.tag", component: Archive, props: { project: "directory", mode: "tag" } },
    { path: "/directory/location/:slug", name: "directory.location", component: Archive, props: { project: "directory", mode: "location" } },
    { path: "/directory/:category/:listing", name: "directory.listing", component: ListingDetail },
];

const router = createRouter({
    history: createWebHistory(),
    routes: routes,
});

// Route guard: settings + both projects (languages) once on first visit.
router.beforeEach(async (to, from, next) => {
    const store = useFrontendStore();
    await Promise.all([store.loadSettings(), store.initLocale()]);
    next();
});

export default router;
