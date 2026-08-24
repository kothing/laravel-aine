<template>
    <section v-if="featured.length || recommended.length">
        <!-- Tabs + More (right-aligned in the same row) -->
        <div class="mb-7 flex items-center gap-1 border-b border-gray-200">
            <button
                v-if="featured.length"
                type="button"
                class="-mb-px border-b-2 px-4 py-2.5 text-sm font-medium transition"
                :class="active === 'featured'
                    ? 'border-indigo-600 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:text-gray-900'"
                @click="active = 'featured'"
            >
                Featured
            </button>
            <button
                v-if="recommended.length"
                type="button"
                class="-mb-px border-b-2 px-4 py-2.5 text-sm font-medium transition"
                :class="active === 'recommended'
                    ? 'border-indigo-600 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:text-gray-900'"
                @click="active = 'recommended'"
            >
                Recommended
            </button>
            <router-link
                :to="moreLink"
                class="-mb-px ml-auto px-4 py-2.5 text-sm font-medium text-indigo-600 hover:opacity-80"
            >
                More →
            </router-link>
        </div>

        <!-- Horizontal list items -->
        <div v-show="active === 'featured'" class="space-y-3">
            <featured-item
                v-for="article in featured"
                :key="article.id"
                :item="article"
                :path-prefix="pathPrefix"
            />
        </div>

        <div v-show="active === 'recommended'" class="space-y-3">
            <featured-item
                v-for="article in recommended"
                :key="article.id"
                :item="article"
                :path-prefix="pathPrefix"
            />
        </div>
    </section>
</template>

<script>
import FeaturedItem from "./FeaturedItem.vue";

export default {
    name: "FeaturedSidebar",
    components: {
        FeaturedItem,
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
    computed: {
        moreLink() {
            return this.active === "featured" ? "/content/featured" : "/content/recommended";
        },
    },
    mounted() {
        if (!this.featured.length && this.recommended.length) {
            this.active = "recommended";
        }
    },
};
</script>
