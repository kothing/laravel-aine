<template>
    <section v-if="items.length">
        <!-- Featured heading + More (right-aligned in the same row) -->
        <div class="mb-4 flex items-center border-b border-gray-200">
            <span class="-mb-px border-b-2 border-indigo-600 px-4 py-2.5 text-sm font-medium text-indigo-600">
                Featured
            </span>
            <router-link
                :to="moreLink"
                class="-mb-px ml-auto px-4 py-2.5 text-sm font-medium text-indigo-600 hover:opacity-80"
            >
                More →
            </router-link>
        </div>

        <!-- Horizontal list items -->
        <div class="space-y-3">
            <article
                v-for="item in items"
                :key="item.id"
                class="flex gap-3 rounded-xl border border-gray-200 bg-white p-3 shadow-sm transition hover:shadow-md"
            >
                <router-link
                    :to="itemLink(item)"
                    class="block h-20 w-24 shrink-0 overflow-hidden rounded-lg bg-gray-100"
                >
                    <img
                        v-if="item.logo && item.logo.full_url"
                        :src="item.logo.full_url"
                        :alt="item.title"
                        class="h-full w-full object-cover"
                        loading="lazy"
                    />
                    <div
                        v-else
                        class="flex h-full w-full items-center justify-center bg-gradient-to-br from-indigo-500 to-purple-600 text-lg font-bold text-white"
                    >
                        {{ initials(item) }}
                    </div>
                </router-link>

                <div class="flex min-w-0 flex-1 flex-col">
                    <h3 class="mb-1 line-clamp-2 text-sm font-semibold leading-snug text-gray-900">
                        <router-link :to="itemLink(item)" class="hover:text-indigo-600">
                            {{ item.title }}
                        </router-link>
                    </h3>

                    <div class="mb-1 flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-xs text-gray-400">
                        <router-link
                            v-if="item.category"
                            :to="`${pathPrefix}/category/${item.category.url}`"
                            class="font-medium text-indigo-600 hover:text-indigo-700"
                        >
                            {{ item.category.title }}
                        </router-link>
                        <span v-if="item.location">·</span>
                        <span v-if="item.location">{{ item.location.name }}</span>
                    </div>

                    <div class="mt-auto flex items-center justify-between">
                        <div v-if="item.tags && item.tags.length" class="flex flex-wrap gap-1">
                            <router-link
                                v-for="tag in item.tags.slice(0, 2)"
                                :key="tag.id"
                                :to="`${pathPrefix}/tag/${(tag.tag || '').toLowerCase().replace(/\s+/g, '-')}`"
                                class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-500 transition hover:bg-indigo-50 hover:text-indigo-600"
                            >
                                #{{ tag.tag }}
                            </router-link>
                        </div>
                        <span v-if="item['price-range']" class="ml-auto text-xs font-semibold text-emerald-600">
                            {{ item['price-range'] }}
                        </span>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>

<script>
export default {
    name: "FeaturedListingsSidebar",
    props: {
        items: {
            type: Array,
            default: () => [],
        },
        pathPrefix: {
            type: String,
            default: "/directory",
        },
        moreLink: {
            type: String,
            default: "/directory/featured",
        },
    },
    methods: {
        itemLink(item) {
            const category = item.category ? item.category.url : "listings";
            const url = item.url || item.id;
            return `${this.pathPrefix}/${category}/${url}`;
        },
        initials(item) {
            return (item.title || "?")
                .split(/\s+/)
                .slice(0, 2)
                .map((w) => w[0])
                .join("")
                .toUpperCase();
        },
    },
};
</script>
