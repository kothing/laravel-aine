<template>
    <article class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
        <router-link
            :to="itemLink"
            class="relative block aspect-[16/9] overflow-hidden bg-gray-100"
        >
            <img
                v-if="item.logo && item.logo.full_url"
                :src="item.logo.full_url"
                :alt="item.title"
                class="h-full w-full object-cover transition duration-300 hover:scale-105"
                loading="lazy"
            />
            <div
                v-else
                class="flex h-full w-full items-center justify-center bg-gradient-to-br from-indigo-500 to-purple-600 text-3xl font-bold text-white"
            >
                {{ initials }}
            </div>
            <span
                v-if="item.featured"
                class="absolute left-3 top-3 rounded-full bg-amber-400 px-2.5 py-0.5 text-xs font-bold text-amber-900"
            >
                ★ Featured
            </span>
        </router-link>

        <div class="flex flex-1 flex-col p-5">
            <div class="mb-2 flex flex-wrap items-center gap-1.5 text-xs">
                <router-link
                    v-if="item.category"
                    :to="`${pathPrefix}/category/${item.category.url}`"
                    class="font-medium text-indigo-600 hover:text-indigo-700"
                >
                    {{ item.category.title }}
                </router-link>
                <span v-if="item.location" class="text-gray-400">·</span>
                <span v-if="item.location" class="text-gray-400">{{ item.location.name }}</span>
                <span v-if="item['price-range']" class="ml-auto font-semibold text-emerald-600">
                    {{ item['price-range'] }}
                </span>
            </div>

            <h3 class="mb-2 text-lg font-bold leading-snug text-gray-900">
                <router-link :to="itemLink" class="hover:text-indigo-600">
                    {{ item.title }}
                </router-link>
            </h3>

            <p v-if="item.description" class="mb-3 line-clamp-2 text-sm leading-relaxed text-gray-500">
                {{ item.description }}
            </p>

            <div v-if="item.phone || item.website" class="mb-3 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500">
                <span v-if="item.phone" class="inline-flex items-center gap-1">
                    <i class="fas fa-phone-alt"></i> {{ item.phone }}
                </span>
                <span v-if="item.website" class="inline-flex items-center gap-1">
                    <i class="fas fa-globe"></i> {{ shortWebsite }}
                </span>
            </div>

            <div class="mt-auto flex flex-wrap gap-1.5">
                <router-link
                    v-for="tag in item.tags || []"
                    :key="tag.id"
                    :to="`${pathPrefix}/tag/${(tag.tag || '').toLowerCase().replace(/\s+/g, '-')}`"
                    class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 transition hover:bg-indigo-50 hover:text-indigo-600"
                >
                    #{{ tag.tag }}
                </router-link>
            </div>
        </div>
    </article>
</template>

<script>
export default {
    name: "ListingCard",
    props: {
        item: {
            type: Object,
            required: true,
        },
        pathPrefix: {
            type: String,
            default: "/directory",
        },
    },
    computed: {
        itemLink() {
            const category = this.item.category ? this.item.category.url : "listings";
            const url = this.item.url || this.item.id;
            return `${this.pathPrefix}/${category}/${url}`;
        },
        initials() {
            return (this.item.title || "?")
                .split(/\s+/)
                .slice(0, 2)
                .map((w) => w[0])
                .join("")
                .toUpperCase();
        },
        shortWebsite() {
            return (this.item.website || "").replace(/^https?:\/\//, "").replace(/\/$/, "");
        },
    },
};
</script>
