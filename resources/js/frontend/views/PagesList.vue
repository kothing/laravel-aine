<template>
    <div class="mx-auto w-full max-w-6xl px-4 py-10">
        <!-- Breadcrumb -->
        <nav class="mb-6 flex flex-wrap items-center gap-2 text-sm text-gray-500">
            <router-link to="/" class="hover:text-indigo-600">{{ siteName || "Home" }}</router-link>
            <span>›</span>
            <router-link :to="'/content'" class="hover:text-indigo-600">Content</router-link>
            <span>›</span>
            <span class="text-gray-900">Pages</span>
        </nav>

        <h1 class="mb-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Pages</h1>
        <p class="mb-8 text-gray-500">All static pages of this site.</p>

        <content-tabs active="pages" />

        <div v-if="loading" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-20 animate-pulse rounded-xl bg-gray-100"></div>
        </div>

        <div v-else-if="!pages.length" class="py-16 text-center">
            <h2 class="text-xl font-bold text-gray-900">No pages yet</h2>
            <p class="mt-2 text-sm text-gray-500">Publish some pages from the admin panel to see them here.</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <router-link
                v-for="page in pages"
                :key="page.id"
                :to="`/content/${page.url}`"
                class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md"
            >
                <div class="mb-1 flex items-center justify-between gap-2">
                    <h3 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600">
                        {{ page.title }}
                    </h3>
                </div>
                <p v-if="snippet(page)" class="line-clamp-2 text-sm leading-relaxed text-gray-500">
                    {{ snippet(page) }}
                </p>
                <span class="mt-3 inline-block text-sm font-medium text-indigo-600 opacity-0 transition group-hover:opacity-100">
                    Open page →
                </span>
            </router-link>
        </div>
    </div>
</template>

<script>
import { useFrontendStore } from "../store";
import ContentTabs from "../components/ContentTabs.vue";

export default {
    name: "PagesList",
    components: {
        ContentTabs,
    },
    data() {
        return {
            pages: [],
            loading: true,
            siteName: "Home",
        };
    },
    async mounted() {
        const store = useFrontendStore();
        this.siteName = store.settings.name || "Home";
        try {
            this.pages = await store.loadPages(true);
        } catch (error) {
            console.error("Failed to load pages:", error);
        } finally {
            this.loading = false;
        }
    },
    methods: {
        snippet(page) {
            if (!page.content) return "";
            const text = page.content
                .replace(/<[^>]+>/g, " ")
                .replace(/&amp;/g, "&")
                .replace(/&nbsp;/g, " ")
                .replace(/\s+/g, " ")
                .trim();
            return text.length > 120 ? text.slice(0, 120) + "…" : text;
        },
    },
};
</script>
