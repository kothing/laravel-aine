<template>
    <div>
        <!-- Full-page skeleton while the home data is loading -->
        <section v-if="loading" class="mx-auto w-full max-w-6xl px-4 pb-16 pt-8">
            <!-- Hero / slider -->
            <div class="mb-4 flex items-center justify-between">
                <div class="h-7 w-24 animate-pulse rounded bg-gray-200"></div>
                <div class="h-4 w-40 animate-pulse rounded bg-gray-200"></div>
            </div>
            <div class="h-64 animate-pulse rounded-xl bg-gray-100"></div>

            <!-- Content area -->
            <div class="pt-10">
                <div class="mb-7 flex items-center gap-4">
                    <div class="h-8 w-28 animate-pulse rounded bg-gray-200"></div>
                    <div class="h-4 min-w-0 flex-1 animate-pulse rounded bg-gray-200"></div>
                </div>
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:col-span-2 xl:grid-cols-2">
                        <div v-for="n in 4" :key="n" class="h-64 animate-pulse rounded-xl bg-gray-100"></div>
                    </div>
                    <div class="grid gap-6">
                        <div v-for="n in 3" :key="n" class="h-28 animate-pulse rounded-xl bg-gray-100"></div>
                    </div>
                </div>
            </div>

            <!-- Directory area -->
            <div class="pt-10">
                <div class="mb-7 h-8 w-36 animate-pulse rounded bg-gray-200"></div>
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:col-span-2 xl:grid-cols-2">
                        <div v-for="n in 4" :key="n" class="h-64 animate-pulse rounded-xl bg-gray-100"></div>
                    </div>
                    <div class="grid gap-6">
                        <div v-for="n in 3" :key="n" class="h-28 animate-pulse rounded-xl bg-gray-100"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Real content once the data is loaded -->
        <template v-else>
            <!-- Hero: banner slider (CMS articles) — full width -->
            <section v-if="showCmsSlider" class="mx-auto w-full max-w-6xl px-4 pt-8">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900">Slider</h2>
                    <span class="text-xs text-gray-400">{{ sliderArticles.length }} featured stories</span>
                </div>
                <banner-slider :slides="sliderArticles" path-prefix="/content" />
            </section>

            <!-- Content: full-width heading, then two columns
                 (left: category tabs content / right: Featured | Recommended) -->
            <section v-if="showCmsSection" class="mx-auto w-full max-w-6xl px-4">
                <div class="pt-10">
                    <div class="mb-7 flex items-center gap-4">
                        <h2 class="shrink-0 text-2xl font-bold tracking-tight text-gray-900">Content</h2>
                        <pages-ticker :pages="cmsPages" path-prefix="/content" class="min-w-0 flex-1" />
                    </div>

                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                        <!-- Left: category tabs (the content) -->
                        <div class="lg:col-span-2">
                            <category-tabs-section
                                :show-title="false"
                                path-prefix="/content"
                                :sections="cmsSections"
                                grid-class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-2"
                            >
                                <template #card="{ item }">
                                    <article-card :item="item" path-prefix="/content" />
                                </template>
                            </category-tabs-section>
                        </div>

                        <!-- Right: Featured | Recommended tabs -->
                        <div class="lg:col-span-1">
                            <featured-sidebar
                                :featured="featuredArticles"
                                :recommended="recommendedArticles"
                                path-prefix="/content"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Directory: full-width heading, then two columns
                 (left: category tabs / right: featured listings) -->
            <section v-if="showDirectorySection" class="mx-auto w-full max-w-6xl px-4 pb-16">
                <div class="pt-10">
                    <h2 class="mb-7 text-2xl font-bold tracking-tight text-gray-900">Directory</h2>

                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                        <!-- Left: category tabs (the listings) -->
                        <div class="lg:col-span-2">
                            <category-tabs-section
                                :show-title="false"
                                path-prefix="/directory"
                                :sections="directorySections"
                                grid-class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-2"
                            >
                                <template #card="{ item }">
                                    <listing-card :item="item" path-prefix="/directory" />
                                </template>
                            </category-tabs-section>
                        </div>

                        <!-- Right: featured listings (no tabs — single dimension) -->
                        <div class="lg:col-span-1">
                            <featured-listings-sidebar
                                :items="featuredListings"
                                path-prefix="/directory"
                                more-link="/directory/featured"
                            />
                        </div>
                    </div>
                </div>
            </section>
        </template>
    </div>
</template>

<script>
import { api } from "../api";
import { PROJECTS, COLLECTIONS, DEFAULT_PORTAL } from "../config";
import BannerSlider from "../components/BannerSlider.vue";
import CategoryTabsSection from "../components/CategoryTabsSection.vue";
import FeaturedSidebar from "../components/FeaturedSidebar.vue";
import FeaturedListingsSidebar from "../components/FeaturedListingsSidebar.vue";
import PagesTicker from "../components/PagesTicker.vue";
import ArticleCard from "../components/ArticleCard.vue";
import ListingCard from "../components/ListingCard.vue";

export default {
    name: "Home",
    components: {
        BannerSlider,
        CategoryTabsSection,
        FeaturedSidebar,
        FeaturedListingsSidebar,
        PagesTicker,
        ArticleCard,
        ListingCard,
    },
    data() {
        return {
            cms: PROJECTS.cms,
            directory: PROJECTS.directory,
            loading: true,
            sliderArticles: [],
            featuredArticles: [],
            recommendedArticles: [],
            featuredListings: [],
            cmsPages: [],
            cmsSections: [],
            directorySections: [],
        };
    },
    computed: {
        // Section visibility is driven by each project's portal config
        // (frontend pruning of the portal /portal skeleton).
        showCmsSlider() {
            return this.hasBlock(this.cms, "slider");
        },
        showCmsSection() {
            return this.hasAnyBlock(this.cms, ["categoryTabs", "featured", "recommended", "pages"]);
        },
        showDirectorySection() {
            return this.hasAnyBlock(this.directory, ["categoryTabs", "featured"]);
        },
    },
    async mounted() {
        try {
            const cms = this.cms;
            const directory = this.directory;

            const [cmsPortal, directoryPortal] = await Promise.all([
                api.collection(cms.identifier, COLLECTIONS.portal, { collection: cms.contentCollection }).catch(() => null),
                api.collection(directory.identifier, COLLECTIONS.portal, { collection: directory.contentCollection }).catch(() => null),
            ]);

            const cmsData = this.mapSections(cms, cmsPortal, true);
            this.featuredArticles = cmsData.featured;
            this.recommendedArticles = cmsData.recommended;
            this.sliderArticles = cmsData.slider;
            this.cmsPages = cmsData.pages;
            this.cmsSections = cmsData.sections;

            const directoryData = this.mapSections(directory, directoryPortal, false);
            this.featuredListings = directoryData.featured;
            this.directorySections = directoryData.sections;
        } catch (error) {
            console.error("Failed to load home data:", error);
        } finally {
            this.loading = false;
        }
    },
    methods: {
        /**
         * Map the portal /portal skeleton onto the home page state.
         * Keeps the original fallback rules (featured/recommended/slider fall
         * back to latest when empty) and prunes the blocks the project does
         * not declare in its portal config.
         */
        mapSections(project, portal, fallbackToLatest = false) {
            if (!portal) {
                return { featured: [], recommended: [], slider: [], pages: [], sections: [] };
            }
            const blocks = project.portal || DEFAULT_PORTAL;

            const featuredRaw = Array.isArray(portal.featured) ? portal.featured : [];
            const recommendedRaw = Array.isArray(portal.recommended) ? portal.recommended : [];
            const sliderRaw = Array.isArray(portal.slider) ? portal.slider : [];
            const latest = Array.isArray(portal.latest) ? portal.latest : [];

            const featured = fallbackToLatest && !featuredRaw.length ? latest : featuredRaw;
            const recommended = fallbackToLatest && !recommendedRaw.length ? latest : recommendedRaw;
            const withImage = latest.filter((a) => a["featured-image"] && a["featured-image"].full_url);
            const slider =
                fallbackToLatest && !sliderRaw.length
                    ? (withImage.length ? withImage : latest).slice(0, 5)
                    : sliderRaw.slice(0, 5);

            return {
                featured: blocks.includes("featured") ? featured : [],
                recommended: blocks.includes("recommended") ? recommended : [],
                slider: blocks.includes("slider") ? slider : [],
                pages: blocks.includes("pages") && Array.isArray(portal.pages) ? portal.pages : [],
                sections: blocks.includes("categoryTabs") && Array.isArray(portal.categories) ? portal.categories : [],
            };
        },

        hasBlock(project, block) {
            return (project.portal || DEFAULT_PORTAL).includes(block);
        },

        hasAnyBlock(project, blocks) {
            return blocks.some((b) => this.hasBlock(project, b));
        },
    },
};
</script>
