<template>
    <section v-if="featured.length || recommended.length" class="py-10">
        <div class="mb-7 flex items-center gap-1 border-b border-gray-200">
            <button
                v-if="featured.length"
                type="button"
                class="tab-btn -mb-px border-b-2 px-4 py-2.5 text-sm font-medium transition"
                :class="active === 'featured' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-900'"
                @click="active = 'featured'"
            >
                Featured
            </button>
            <button
                v-if="recommended.length"
                type="button"
                class="tab-btn -mb-px border-b-2 px-4 py-2.5 text-sm font-medium transition"
                :class="active === 'recommended' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-900'"
                @click="active = 'recommended'"
            >
                Recommended
            </button>
        </div>

        <div v-show="active === 'featured'" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <article-card v-for="article in featured" :key="article.id" :item="article" :path-prefix="pathPrefix" />
        </div>

        <div v-show="active === 'recommended'" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <article-card v-for="article in recommended" :key="article.id" :item="article" :path-prefix="pathPrefix" />
        </div>
    </section>
</template>

<script>
import ArticleCard from "./ArticleCard.vue";

export default {
    name: "TabsSection",
    components: {
        ArticleCard,
    },
    props: {
        featured: {
            type: Array,
            default: () => [],
        },
        recommended: {
            type: Array,
            default: () => [],
        },
        pathPrefix: {
            type: String,
            default: "/content",
        },
    },
    data() {
        return {
            active: "featured",
        };
    },
    watch: {
        featured(list) {
            if (list.length && this.active !== "featured") {
                this.active = "featured";
            } else if (!list.length && this.recommended.length) {
                this.active = "recommended";
            }
        },
    },
    mounted() {
        if (!this.featured.length && this.recommended.length) {
            this.active = "recommended";
        }
    },
};
</script>
