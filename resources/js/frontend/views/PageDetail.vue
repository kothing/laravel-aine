<template>
    <div class="mx-auto w-full max-w-3xl px-4 py-10">
        <router-link to="/" class="mb-6 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-indigo-600">
            ← Back to home
        </router-link>

        <div v-if="loading" class="space-y-4">
            <div class="h-8 w-1/2 animate-pulse rounded bg-gray-200"></div>
            <div class="h-4 w-full animate-pulse rounded bg-gray-200"></div>
            <div class="h-4 w-5/6 animate-pulse rounded bg-gray-200"></div>
        </div>

        <div v-else-if="!page" class="py-16 text-center">
            <h2 class="text-xl font-bold text-gray-900">Page not found</h2>
            <p class="mt-2 text-sm text-gray-500">The page "{{ slug }}" does not exist.</p>
            <router-link to="/" class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:opacity-80">
                Back to home →
            </router-link>
        </div>

        <template v-else>
            <h1 class="mb-4 text-3xl font-bold leading-tight text-gray-900 sm:text-4xl">{{ page.title }}</h1>
            <SsmlContent class="page-body text-base leading-relaxed text-gray-800" :content="page.content" />
        </template>
    </div>
</template>

<script>
import { useFrontendStore } from "../store";
import SsmlContent from "../../components/SsmlContent.vue";

export default {
    name: "PageDetail",
    components: {
        SsmlContent,
    },
    data() {
        return {
            page: null,
            loading: true,
        };
    },
    computed: {
        slug() {
            return this.$route.params.slug;
        },
    },
    watch: {
        slug() {
            this.loadPage();
        },
    },
    async mounted() {
        this.loadPage();
    },
    methods: {
        async loadPage() {
            this.loading = true;
            this.page = null;
            try {
                const store = useFrontendStore();
                const pages = await store.loadPages();
                this.page = (pages || []).find((p) => p.url === this.slug) || null;
            } catch (error) {
                console.error("Failed to load page:", error);
                this.page = null;
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

<style scoped>
.page-body h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 2rem 0 1rem;
}
.page-body h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 1.5rem 0 0.75rem;
}
.page-body p {
    margin: 0 0 1rem;
}
.page-body img {
    max-width: 100%;
    border-radius: 0.5rem;
    margin: 1rem 0;
}
.page-body a {
    color: #4f46e5;
    text-decoration: underline;
}
.page-body ul,
.page-body ol {
    margin: 0 0 1rem 1.5rem;
}
.page-body li {
    margin-bottom: 0.25rem;
}
.page-body blockquote {
    border-left: 3px solid #4f46e5;
    padding-left: 1rem;
    margin: 1.5rem 0;
    color: #6b7280;
    font-style: italic;
}
.page-body pre {
    background: #f5f5f5;
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin: 1rem 0;
    font-size: 0.875rem;
}
</style>
