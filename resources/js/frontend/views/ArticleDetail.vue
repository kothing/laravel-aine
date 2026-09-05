<template>
    <div class="mx-auto w-full max-w-3xl px-4 py-10">
        <router-link to="/" class="mb-6 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-indigo-600">
            ← Back to home
        </router-link>

        <div v-if="loading" class="space-y-4">
            <div class="h-8 w-3/4 animate-pulse rounded bg-gray-200"></div>
            <div class="h-64 animate-pulse rounded-xl bg-gray-100"></div>
            <div class="h-4 w-full animate-pulse rounded bg-gray-200"></div>
            <div class="h-4 w-5/6 animate-pulse rounded bg-gray-200"></div>
        </div>

        <div v-else-if="!item" class="py-16 text-center">
            <h2 class="text-xl font-bold text-gray-900">Article not found</h2>
            <p class="mt-2 text-sm text-gray-500">This article may have been unpublished or removed.</p>
            <router-link to="/" class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:opacity-80">
                Back to home →
            </router-link>
        </div>

        <template v-else>
            <div class="mb-3 flex flex-wrap items-center gap-2 text-sm">
                <router-link
                    v-if="item.category"
                    :to="`/content/category/${item.category.url}`"
                    class="font-medium text-indigo-600 hover:text-indigo-700"
                >
                    {{ item.category.title }}
                </router-link>
                <span v-if="item.category && formattedDate" class="text-gray-400">·</span>
                <span v-if="formattedDate" class="text-gray-400">{{ formattedDate }}</span>
            </div>

            <h1 class="mb-4 text-3xl font-bold leading-tight text-gray-900 sm:text-4xl">
                {{ item.title }}
            </h1>

            <p v-if="item.excerpt" class="mb-6 text-lg leading-relaxed text-gray-500">
                {{ item.excerpt }}
            </p>

            <img
                v-if="item['featured-image'] && item['featured-image'].full_url"
                :src="item['featured-image'].full_url"
                :alt="item.title"
                class="mb-8 aspect-[16/9] w-full rounded-xl object-cover"
            />

            <SsmlContent class="article-body text-base leading-relaxed text-gray-800" :content="item.content" />

            <div class="mt-8 flex flex-wrap gap-2 border-t border-gray-200 pt-6">
                <router-link
                    v-for="tag in item.tags || []"
                    :key="tag.id"
                    :to="`/content/tag/${(tag.tag || '').toLowerCase().replace(/\s+/g, '-')}`"
                    class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600 transition hover:bg-indigo-50 hover:text-indigo-600"
                >
                    #{{ tag.tag }}
                </router-link>
            </div>
        </template>
    </div>
</template>

<script>
import { formatDate } from "../../utils/filters";
import { api } from "../api";
import { PROJECTS } from "../config";
import SsmlContent from "../../components/SsmlContent.vue";

export default {
    name: "ArticleDetail",
    components: {
        SsmlContent,
    },
    data() {
        return {
            item: null,
            loading: true,
        };
    },
    computed: {
        formattedDate() {
            if (!this.item || !this.item.published_at) return null;
            return formatDate(this.item.published_at, "MMM D, YYYY");
        },
        params() {
            return `${this.$route.params.category}/${this.$route.params.article}`;
        },
    },
    watch: {
        params() {
            this.loadItem();
        },
    },
    async mounted() {
        this.loadItem();
    },
    methods: {
        async loadItem() {
            this.loading = true;
            this.item = null;
            const articleUrl = this.$route.params.article;
            const categoryUrl = this.$route.params.category;

            try {
                const matches = await api.collection(PROJECTS.cms.identifier, PROJECTS.cms.contentCollection, {
                    where: { url: articleUrl },
                    timestamps: true,
                });
                const match = (matches || []).find((a) => (a.category ? a.category.url : null) === categoryUrl);
                this.item = match || (matches || [])[0] || null;
            } catch (error) {
                console.error("Failed to load article:", error);
                this.item = null;
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

<style scoped>
.article-body h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 2rem 0 1rem;
}
.article-body h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 1.5rem 0 0.75rem;
}
.article-body p {
    margin: 0 0 1rem;
}
.article-body img {
    max-width: 100%;
    border-radius: 0.5rem;
    margin: 1rem 0;
}
.article-body a {
    color: #4f46e5;
    text-decoration: underline;
}
.article-body ul,
.article-body ol {
    margin: 0 0 1rem 1.5rem;
}
.article-body li {
    margin-bottom: 0.25rem;
}
.article-body blockquote {
    border-left: 3px solid #4f46e5;
    padding-left: 1rem;
    margin: 1.5rem 0;
    color: #6b7280;
    font-style: italic;
}
.article-body pre {
    background: #f5f5f5;
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin: 1rem 0;
    font-size: 0.875rem;
}
.article-body code {
    background: #f5f5f5;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
}
.article-body pre code {
    background: none;
    padding: 0;
}
</style>
