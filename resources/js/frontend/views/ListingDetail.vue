<template>
    <div class="mx-auto w-full max-w-3xl px-4 py-10">
        <router-link to="/" class="mb-6 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-indigo-600">
            ← Back to home
        </router-link>

        <div v-if="loading" class="space-y-4">
            <div class="h-8 w-2/3 animate-pulse rounded bg-gray-200"></div>
            <div class="h-64 animate-pulse rounded-xl bg-gray-100"></div>
            <div class="h-4 w-full animate-pulse rounded bg-gray-200"></div>
        </div>

        <div v-else-if="!item" class="py-16 text-center">
            <h2 class="text-xl font-bold text-gray-900">Listing not found</h2>
            <p class="mt-2 text-sm text-gray-500">This business listing may have been unpublished or removed.</p>
            <router-link to="/" class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:opacity-80">
                Back to home →
            </router-link>
        </div>

        <template v-else>
            <div class="mb-3 flex flex-wrap items-center gap-2 text-sm">
                <router-link
                    v-if="item.category"
                    :to="`/directory/category/${item.category.url}`"
                    class="font-medium text-indigo-600 hover:text-indigo-700"
                >
                    {{ item.category.title }}
                </router-link>
                <span v-if="item.location" class="text-gray-400">·</span>
                <router-link
                    v-if="item.location"
                    :to="`/directory/location/${item.location.url}`"
                    class="text-gray-500 hover:text-indigo-600"
                >
                    {{ item.location.name }}
                </router-link>
                <span v-if="item['price-range']" class="ml-auto font-semibold text-emerald-600">
                    {{ item['price-range'] }}
                </span>
            </div>

            <div class="flex items-start gap-4">
                <img
                    v-if="item.logo && item.logo.full_url"
                    :src="item.logo.full_url"
                    :alt="item.title"
                    class="h-20 w-20 shrink-0 rounded-xl border border-gray-200 object-cover"
                />
                <div>
                    <h1 class="text-3xl font-bold leading-tight text-gray-900 sm:text-4xl">
                        {{ item.title }}
                    </h1>
                    <span
                        v-if="item.featured"
                        class="mt-2 inline-block rounded-full bg-amber-400 px-2.5 py-0.5 text-xs font-bold text-amber-900"
                    >
                        ★ Featured
                    </span>
                </div>
            </div>

            <p v-if="item.description" class="mt-6 text-base leading-relaxed text-gray-600">
                {{ item.description }}
            </p>

            <!-- Gallery -->
            <div v-if="gallery.length" class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3">
                <img
                    v-for="img in gallery"
                    :key="img.id"
                    :src="img.full_url"
                    :alt="item.title"
                    class="aspect-[16/10] w-full rounded-lg border border-gray-200 object-cover"
                />
            </div>

            <!-- Contact & details -->
            <div class="mt-8 grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-white p-5 sm:grid-cols-2">
                <div v-if="item.phone" class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-phone-alt w-5 text-indigo-500"></i>
                    <a :href="`tel:${item.phone}`" class="hover:text-indigo-600">{{ item.phone }}</a>
                </div>
                <div v-if="item.email" class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-envelope w-5 text-indigo-500"></i>
                    <a :href="`mailto:${item.email}`" class="hover:text-indigo-600">{{ item.email }}</a>
                </div>
                <div v-if="item.website" class="flex items-center gap-2 text-sm text-gray-700">
                    <i class="fas fa-globe w-5 text-indigo-500"></i>
                    <a :href="item.website" target="_blank" rel="noopener noreferrer" class="hover:text-indigo-600">
                        {{ item.website }}
                    </a>
                </div>
                <div v-if="item.address" class="flex items-start gap-2 text-sm text-gray-700">
                    <i class="fas fa-map-marker-alt mt-0.5 w-5 text-indigo-500"></i>
                    <span>{{ item.address }}</span>
                </div>
                <div v-if="item['opening-hours']" class="flex items-start gap-2 text-sm text-gray-700 sm:col-span-2">
                    <i class="fas fa-clock mt-0.5 w-5 text-indigo-500"></i>
                    <span>{{ item['opening-hours'] }}</span>
                </div>
            </div>

            <!-- Tags -->
            <div class="mt-6 flex flex-wrap gap-2">
                <router-link
                    v-for="tag in item.tags || []"
                    :key="tag.id"
                    :to="`/directory/tag/${(tag.tag || '').toLowerCase().replace(/\s+/g, '-')}`"
                    class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600 transition hover:bg-indigo-50 hover:text-indigo-600"
                >
                    #{{ tag.tag }}
                </router-link>
            </div>

            <!-- Reviews -->
            <div v-if="reviews.length" class="mt-10">
                <h2 class="mb-4 text-xl font-bold text-gray-900">Reviews ({{ reviews.length }})</h2>
                <div class="space-y-3">
                    <div
                        v-for="review in reviews"
                        :key="review.id"
                        class="rounded-xl border border-gray-200 bg-white p-4"
                    >
                        <div class="mb-1 flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-800">{{ review.name }}</span>
                            <span class="text-sm text-amber-500">{{ stars(review.rating) }}</span>
                        </div>
                        <p v-if="review.review" class="text-sm leading-relaxed text-gray-600">{{ review.review }}</p>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script>
import { api } from "../api";
import { PROJECTS } from "../config";

export default {
    name: "ListingDetail",
    data() {
        return {
            item: null,
            reviews: [],
            loading: true,
        };
    },
    computed: {
        gallery() {
            if (!this.item || !Array.isArray(this.item.gallery)) return [];
            return this.item.gallery;
        },
        params() {
            return `${this.$route.params.category}/${this.$route.params.listing}`;
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
        stars(rating) {
            const n = parseInt(rating, 10) || 0;
            return "★".repeat(Math.max(0, Math.min(5, n))) + "☆".repeat(Math.max(0, 5 - n));
        },
        async loadItem() {
            this.loading = true;
            this.item = null;
            this.reviews = [];
            const listingUrl = this.$route.params.listing;
            const categoryUrl = this.$route.params.category;

            try {
                const matches = await api.collection(PROJECTS.directory.identifier, PROJECTS.directory.contentCollection, {
                    where: { url: listingUrl },
                    timestamps: true,
                });
                const match = (matches || []).find((l) => (l.category ? l.category.url : null) === categoryUrl);
                this.item = match || (matches || [])[0] || null;

                if (this.item) {
                    this.reviews = (await api.related(
                        PROJECTS.directory.identifier,
                        PROJECTS.directory.contentCollection,
                        this.item.id,
                        "reviews",
                        { sort: "created_at:desc", timestamps: true, state: "only_published" }
                    )) || [];
                }
            } catch (error) {
                console.error("Failed to load listing:", error);
                this.item = null;
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
