<template>
    <div v-if="slides.length" class="relative overflow-hidden rounded-xl border border-gray-200 bg-gray-900" style="aspect-ratio: 21 / 9;" @mouseenter="stop" @mouseleave="start">
        <div
            class="flex h-full transition-transform duration-700 ease-[cubic-bezier(0.25,0.46,0.45,0.94)]"
            :style="{ transform: `translateX(-${current * 100}%)` }"
        >
            <div v-for="slide in slides" :key="slide.id" class="relative h-full w-full shrink-0">
                <div
                    v-if="slide['featured-image'] && slide['featured-image'].full_url"
                    class="absolute inset-0 bg-cover bg-center"
                    :style="{ backgroundImage: `url('${slide['featured-image'].full_url}')` }"
                ></div>
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.4) 40%, rgba(0,0,0,0.1) 70%, transparent 100%);"
                ></div>

                <div class="absolute inset-0 z-10 flex items-end p-6 sm:p-10">
                    <div class="max-w-xl text-white">
                        <router-link
                            v-if="slide.category"
                            :to="`${pathPrefix}/category/${slide.category.url}`"
                            class="mb-3 inline-block rounded-full border border-white/50 px-2.5 py-0.5 text-xs uppercase tracking-widest"
                        >
                            {{ slide.category.title }}
                        </router-link>
                        <h2 class="mb-2 text-xl font-bold leading-tight sm:text-3xl">
                            <router-link :to="slideLink(slide, pathPrefix)" class="hover:opacity-90">
                                {{ slide.title }}
                            </router-link>
                        </h2>
                        <p v-if="slide.excerpt" class="mb-4 line-clamp-2 text-sm text-white/85">
                            {{ slide.excerpt }}
                        </p>
                        <router-link
                            :to="slideLink(slide, pathPrefix)"
                            class="inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                        >
                            Read more →
                        </router-link>
                    </div>
                </div>
            </div>
        </div>

        <template v-if="slides.length > 1">
            <button
                type="button"
                aria-label="Previous slide"
                class="absolute left-4 top-1/2 z-20 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/40 text-xl text-white hover:bg-black/70"
                @click="prev"
            >
                ‹
            </button>
            <button
                type="button"
                aria-label="Next slide"
                class="absolute right-4 top-1/2 z-20 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/40 text-xl text-white hover:bg-black/70"
                @click="next"
            >
                ›
            </button>

            <div class="absolute bottom-4 right-6 z-20 flex gap-2">
                <button
                    v-for="(slide, index) in slides"
                    :key="index"
                    type="button"
                    :aria-label="`Go to slide ${index + 1}`"
                    class="h-2 rounded-full transition-all duration-200"
                    :class="index === current ? 'w-6 bg-white' : 'w-2 bg-white/40'"
                    @click="go(index)"
                ></button>
            </div>
        </template>
    </div>
</template>

<script>
export default {
    name: "BannerSlider",
    props: {
        slides: {
            type: Array,
            default: () => [],
        },
    },
    props: {
        slides: {
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
            current: 0,
            timer: null,
        };
    },
    methods: {
        slideLink(slide, pathPrefix) {
            const category = slide.category ? slide.category.url : "articles";
            const url = slide.url || slide.id;
            return `${pathPrefix || ''}/${category}/${url}`;
        },
        go(index) {
            this.current = (index + this.slides.length) % this.slides.length;
        },
        next() {
            this.go(this.current + 1);
        },
        prev() {
            this.go(this.current - 1);
        },
        start() {
            if (this.timer || this.slides.length <= 1) return;
            this.timer = setInterval(this.next, 5000);
        },
        stop() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },
    },
    mounted() {
        this.start();
    },
    beforeUnmount() {
        this.stop();
    },
};
</script>
