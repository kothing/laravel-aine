<template>
    <article class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
        <router-link
            v-if="item['featured-image'] && item['featured-image'].full_url"
            :to="itemLink"
            class="block aspect-[16/9] overflow-hidden bg-gray-100"
        >
            <img
                :src="item['featured-image'].full_url"
                :alt="item.title"
                class="h-full w-full object-cover transition duration-300 hover:scale-105"
                loading="lazy"
            />
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
                <span v-if="item.category && formattedDate" class="text-gray-400">·</span>
                <span v-if="formattedDate" class="text-gray-400">{{ formattedDate }}</span>
            </div>

            <h3 class="mb-2 text-lg font-bold leading-snug text-gray-900">
                <router-link :to="itemLink" class="hover:text-indigo-600">
                    {{ item.title }}
                </router-link>
            </h3>

            <p v-if="item.excerpt" class="mb-4 line-clamp-2 text-sm leading-relaxed text-gray-500">
                {{ item.excerpt }}
            </p>

            <div class="mt-auto">
                <router-link :to="itemLink" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                    Read more →
                </router-link>
            </div>
        </div>
    </article>
</template>

<script>
import { formatDate } from "../../utils/filters";

export default {
    name: "ArticleCard",
    props: {
        item: {
            type: Object,
            required: true,
        },
        pathPrefix: {
            type: String,
            default: "/content",
        },
    },
    computed: {
        itemLink() {
            const category = this.item.category ? this.item.category.url : "articles";
            const url = this.item.url || this.item.id;
            return `${this.pathPrefix}/${category}/${url}`;
        },
        formattedDate() {
            if (!this.item.published_at) return null;
            return formatDate(this.item.published_at, "MMM D, YYYY");
        },
    },
};
</script>
