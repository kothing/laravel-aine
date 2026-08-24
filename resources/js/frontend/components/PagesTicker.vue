<template>
    <div
        v-if="pages.length"
        class="flex items-center gap-3"
        @mouseenter="stop"
        @mouseleave="start"
    >
        <!-- Vertical ticker: exactly one title visible at a time -->
        <div class="relative flex-1 overflow-hidden" style="height: 36px">
            <div
                class="flex flex-col transition-transform duration-500 ease-in-out"
                :style="{ transform: `translateY(-${index * 36}px)` }"
            >
                <router-link
                    v-for="page in pages"
                    :key="page.id"
                    :to="`${pathPrefix}/${page.url}`"
                    class="flex h-9 items-center truncate text-sm text-gray-700 transition hover:text-indigo-600"
                >
                    {{ page.title }}
                </router-link>
            </div>
        </div>

        <!-- Prev / Next on the far right -->
        <div class="flex shrink-0 items-center gap-1">
            <button
                type="button"
                aria-label="Previous page"
                class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-600 transition hover:border-indigo-500 hover:text-indigo-600"
                @click="step(-1)"
            >
                ‹
            </button>
            <button
                type="button"
                aria-label="Next page"
                class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-600 transition hover:border-indigo-500 hover:text-indigo-600"
                @click="step(1)"
            >
                ›
            </button>
        </div>
    </div>
</template>

<script>
const ROW_HEIGHT = 36; // must match h-9

export default {
    name: "PagesTicker",
    props: {
        pages: {
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
            index: 0,
            timer: null,
        };
    },
    mounted() {
        this.start();
    },
    beforeUnmount() {
        this.stop();
    },
    methods: {
        step(dir) {
            const len = this.pages.length;
            if (!len) return;
            this.index = (this.index + dir + len) % len;
        },
        start() {
            if (this.timer || this.pages.length <= 1) return;
            this.timer = setInterval(() => this.step(1), 3000);
        },
        stop() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },
    },
};
</script>
