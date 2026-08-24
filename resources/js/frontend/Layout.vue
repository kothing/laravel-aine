<template>
    <div class="flex min-h-screen flex-col bg-gray-50 text-gray-900">
        <!-- Site header -->
        <header class="sticky top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex h-16 w-full max-w-6xl items-center gap-6 px-4">
                <router-link to="/" class="flex shrink-0 items-center gap-2 font-bold text-gray-900">
                    <img src="/images/logo-32x32-dark.svg" alt="Logo" class="h-7 w-7" />
                    <span class="truncate">{{ siteName }}</span>
                </router-link>

                <nav class="flex flex-1 items-center gap-1 overflow-x-auto text-sm">
                    <router-link
                        to="/"
                        class="whitespace-nowrap rounded-md px-3 py-2 font-medium text-gray-600 transition hover:text-indigo-600"
                        :class="{ 'text-indigo-600': isActive('home') }"
                    >
                        Home
                    </router-link>
                    <router-link
                        to="/content"
                        class="whitespace-nowrap rounded-md px-3 py-2 font-medium text-gray-600 transition hover:text-indigo-600"
                        :class="{ 'text-indigo-600': isActive('content') }"
                    >
                        Content
                    </router-link>
                    <router-link
                        to="/directory"
                        class="whitespace-nowrap rounded-md px-3 py-2 font-medium text-gray-600 transition hover:text-indigo-600"
                        :class="{ 'text-indigo-600': isActive('directory') }"
                    >
                        Directory
                    </router-link>
                </nav>

                <!-- Language switcher (project locales) -->
                <div v-if="store.cmsProjectLocales.length > 1" class="relative shrink-0" ref="langWrap">
                    <button
                        type="button"
                        class="flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm text-gray-700 transition hover:border-gray-400"
                        @click.stop="toggleLang"
                    >
                        <span>{{ labelOf(store.locale) }}</span>
                        <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="langOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                    <div
                        v-if="langOpen"
                        class="fixed z-[100] w-40 overflow-hidden rounded-md border border-gray-200 bg-white py-1 shadow-lg"
                        :style="langMenuStyle"
                    >
                        <button
                            v-for="l in store.cmsProjectLocales"
                            :key="l"
                            type="button"
                            class="block w-full px-3 py-2 text-left text-sm transition hover:bg-gray-50"
                            :class="l === store.locale ? 'font-medium text-indigo-600' : 'text-gray-700'"
                            @click="switchLocale(l)"
                        >
                            {{ labelOf(l) }}
                            <span v-if="l === store.locale" class="float-right text-indigo-600">✓</span>
                        </button>
                    </div>
                </div>

                <a
                    href="/admin"
                    class="shrink-0 rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-600 transition hover:border-indigo-600 hover:text-indigo-600"
                >
                    Admin
                </a>
            </div>
        </header>

        <!-- Page content (remounts when the language changes so views re-fetch) -->
        <main class="flex-1">
            <!-- Global skeleton while the router guard resolves settings + projects -->
            <div
                v-if="!store.localeReady"
                class="mx-auto w-full max-w-6xl px-4 pb-16 pt-8"
            >
                <div class="mb-4 flex items-center justify-between">
                    <div class="h-7 w-24 animate-pulse rounded bg-gray-200"></div>
                    <div class="h-4 w-40 animate-pulse rounded bg-gray-200"></div>
                </div>
                <div class="h-64 animate-pulse rounded-xl bg-gray-100"></div>
                <div class="grid grid-cols-1 gap-6 pt-10 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="n in 6" :key="n" class="h-40 animate-pulse rounded-xl bg-gray-100"></div>
                </div>
            </div>
            <router-view v-else :key="store.locale || 'default'"></router-view>
        </main>

        <!-- Site footer -->
        <footer class="border-t border-gray-200 bg-white">
            <div class="mx-auto w-full max-w-6xl px-4 py-8">
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <div class="text-sm text-gray-500">
                        <span class="font-semibold text-gray-900">{{ siteName }}</span>
                        <span v-if="siteDescription" class="ml-2">{{ siteDescription }}</span>
                    </div>
                    <div class="text-sm text-gray-400">
                        {{ `© ${year} ${siteName} — powered by Aine` }}
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<script>
import { useFrontendStore } from "./store";

export default {
    name: "FrontendLayout",
    data() {
        return {
            year: new Date().getFullYear(),
            langOpen: false,
            langMenuStyle: { left: "0px", top: "0px" },
        };
    },
    computed: {
        store() {
            return useFrontendStore();
        },
        siteName() {
            return this.store.settings.name || "Aine";
        },
        siteDescription() {
            return this.store.settings.description || "";
        },
    },
    async mounted() {
        document.addEventListener("click", this.onDocumentClick);
        window.addEventListener("resize", this.onLangViewportChange);
        window.addEventListener("scroll", this.onLangViewportChange, true);
    },
    beforeUnmount() {
        document.removeEventListener("click", this.onDocumentClick);
        window.removeEventListener("resize", this.onLangViewportChange);
        window.removeEventListener("scroll", this.onLangViewportChange, true);
    },
    methods: {
        isActive(name) {
            if (name === "home") return this.$route.name === "home";
            if (name === "content") return this.$route.name === "content.index";
            if (name === "directory") return this.$route.name === "directory.index";
            return false;
        },
        labelOf(l) {
            if (l === "en") return "English";
            if (l === "zh") return "中文";
            return l.toUpperCase();
        },
        toggleLang() {
            this.langOpen = !this.langOpen;
            if (this.langOpen) {
                this.updateLangPosition();
            }
        },
        switchLocale(l) {
            this.langOpen = false;
            if (l !== this.store.locale) {
                this.store.setLocale(l);
            }
        },
        updateLangPosition() {
            const wrap = this.$refs.langWrap;
            if (!wrap) return;

            const rect = wrap.getBoundingClientRect();
            const menuWidth = 160;                                   // w-40
            const menuHeight = Math.min(this.store.cmsProjectLocales.length * 36 + 16, 320);
            const vw = window.innerWidth || document.documentElement.clientWidth;
            const vh = window.innerHeight || document.documentElement.clientHeight;
            const margin = 8;
            const gap = 6;

            let left;
            if (rect.left + menuWidth <= vw - margin) {
                left = rect.left;
            } else {
                left = Math.max(margin, rect.right - menuWidth);
            }

            let top;
            if (rect.bottom + menuHeight <= vh - margin) {
                top = rect.bottom + gap;
            } else {
                top = Math.max(margin, rect.top - menuHeight - gap);
            }

            this.langMenuStyle = { left: left + "px", top: top + "px" };
        },
        onDocumentClick(event) {
            const wrap = this.$refs.langWrap;
            if (wrap && !wrap.contains(event.target)) {
                this.langOpen = false;
            }
        },
        onLangViewportChange() {
            if (this.langOpen) {
                this.updateLangPosition();
            }
        },
    },
};
</script>
