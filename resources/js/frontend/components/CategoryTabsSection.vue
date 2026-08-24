<template>
    <section v-if="sections.length" :class="showTitle ? 'py-10' : ''">
        <!-- Section title (hidden when embedded under an outer heading) -->
        <h2 v-if="showTitle" class="mb-5 text-2xl font-bold tracking-tight text-gray-900">{{ title }}</h2>

        <!-- Category tabs -->
        <div class="mb-4 flex flex-wrap items-center gap-1 border-b border-gray-200">
            <button
                v-for="section in sections"
                :key="section.category.id"
                type="button"
                class="-mb-px border-b-2 px-4 py-2.5 text-sm font-medium transition"
                :class="activeId === section.category.id
                    ? 'border-indigo-600 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:text-gray-900'"
                @click="activeId = section.category.id"
            >
                {{ section.category.title }}
            </button>
        </div>

        <!-- Active tab: tags (left) + More (right), both follow the tab -->
        <div class="mb-6 flex flex-wrap items-center gap-2">
            <div v-if="activeSection.tags.length" class="flex flex-1 flex-wrap items-center gap-2">
                <router-link
                    v-for="tag in activeSection.tags"
                    :key="tag.id"
                    :to="tagLink(tag)"
                    class="rounded-full border border-gray-200 px-2.5 py-0.5 text-xs text-gray-500 transition hover:border-indigo-600 hover:text-indigo-600"
                >
                    #{{ tag.tag }}
                </router-link>
            </div>
            <span v-else class="flex-1"></span>

            <router-link
                :to="`${pathPrefix}/category/${activeSection.category.url}`"
                class="ml-auto text-sm font-medium text-indigo-600 hover:opacity-80"
            >
                More →
            </router-link>
        </div>

        <!-- Cards of the active category -->
        <p v-if="!activeSection.items.length" class="text-sm text-gray-400">
            No published items in this category yet.
        </p>
        <div v-else :class="gridClass">
            <template v-for="item in activeSection.items" :key="item.id">
                <slot name="card" :item="item"></slot>
            </template>
        </div>
    </section>
</template>

<script>
export default {
    name: "CategoryTabsSection",
    props: {
        title: {
            type: String,
            required: true,
        },
        pathPrefix: {
            type: String,
            required: true,
        },
        showTitle: {
            type: Boolean,
            default: true,
        },
        gridClass: {
            type: String,
            default: "grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4",
        },
        sections: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            activeId: null,
        };
    },
    computed: {
        activeSection() {
            if (!this.sections.length) return { category: {}, items: [], tags: [] };
            const found = this.sections.find((s) => s.category.id === this.activeId);
            return found || this.sections[0];
        },
    },
    watch: {
        sections(list) {
            if (list.length && !list.some((s) => s.category.id === this.activeId)) {
                this.activeId = list[0].category.id;
            }
        },
    },
    mounted() {
        if (this.sections.length) {
            this.activeId = this.sections[0].category.id;
        }
    },
    methods: {
        tagLink(tag) {
            const slug = (tag.tag || "").toLowerCase().replace(/\s+/g, "-");
            return `${this.pathPrefix}/tag/${slug}`;
        },
    },
};
</script>
