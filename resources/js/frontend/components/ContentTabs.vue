<template>
    <div class="mb-6 flex items-center gap-1 border-b border-gray-200">
        <router-link
            :to="{ name: 'content.index' }"
            class="-mb-px border-b-2 px-4 py-2.5 text-sm font-medium transition"
            :class="active === 'articles'
                ? 'border-indigo-600 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-900'"
        >
            Articles
        </router-link>
        <router-link
            :to="{ name: 'content.pages' }"
            class="-mb-px border-b-2 px-4 py-2.5 text-sm font-medium transition"
            :class="active === 'pages'
                ? 'border-indigo-600 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-900'"
        >
            Pages
        </router-link>
    </div>
</template>

<script>
export default {
    name: "ContentTabs",
    props: {
        active: {
            type: String,
            default: "articles", // "articles" | "pages"
        },
    },
};
</script>
