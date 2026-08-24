<template>
    <div class="relative inline-block text-left" ref="wrap">
        <!-- Trigger -->
        <button
            type="button"
            class="flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 transition hover:border-gray-400 focus:border-indigo-500 focus:outline-none"
            :title="__('UI Language')"
            @click.stop="toggle"
        >
            <span>{{ labelOf(modelValue) }}</span>
            <svg
                class="h-4 w-4 shrink-0 text-gray-400 transition-transform duration-200"
                :class="open ? 'rotate-180' : ''"
                viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
            >
                <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>

        <!-- Dropdown menu — positioned with position:fixed and viewport
             coordinates computed from the trigger, so it can never be
             clipped, no matter where the trigger sits or what its ancestors
             do. -->
        <transition name="dropdown">
            <div
                v-if="open"
                class="fixed z-[100] w-48 overflow-hidden rounded-md border border-gray-200 bg-white py-1 shadow-lg"
                :style="menuStyle"
            >
                <button
                    v-for="l in locales"
                    :key="l"
                    type="button"
                    class="block w-full px-3 py-2 text-left text-sm transition hover:bg-gray-50"
                    :class="l === modelValue ? 'font-medium text-indigo-600' : 'text-gray-700'"
                    @click="select(l)"
                >
                    {{ labelOf(l) }}
                    <span v-if="l === modelValue" class="float-right text-indigo-600">✓</span>
                </button>
            </div>
        </transition>
    </div>
</template>

<script>
export default {
    name: "LanguageSelect",
    props: {
        modelValue: {
            type: String,
            required: true,
        },
        locales: {
            type: Array,
            default: () => ["en", "zh"],
        },
    },
    emits: ["update:modelValue", "change"],
    data() {
        return {
            open: false,
            menuStyle: { left: "0px", top: "0px" },
        };
    },
    mounted() {
        document.addEventListener("click", this.onDocumentClick);
        window.addEventListener("resize", this.onViewportChange);
        window.addEventListener("scroll", this.onViewportChange, true);
    },
    beforeUnmount() {
        document.removeEventListener("click", this.onDocumentClick);
        window.removeEventListener("resize", this.onViewportChange);
        window.removeEventListener("scroll", this.onViewportChange, true);
    },
    methods: {
        labelOf(l) {
            if (l === "en") return "English (en)";
            if (l === "zh") return "中文 (zh)";
            return l.toUpperCase() + " (" + l + ")";
        },
        toggle() {
            this.open = !this.open;
            if (this.open) {
                // No need to wait for the menu to render — we only measure
                // the trigger, so position can be computed immediately.
                this.updatePosition();
            }
        },
        select(l) {
            this.open = false;
            if (l !== this.modelValue) {
                this.$emit("update:modelValue", l);
                this.$emit("change", l);
            }
        },
        onDocumentClick(event) {
            const wrap = this.$refs.wrap;
            if (wrap && !wrap.contains(event.target)) {
                this.open = false;
            }
        },
        onViewportChange() {
            if (this.open) {
                this.updatePosition();
            }
        },
        /**
         * Place the menu with fixed viewport coordinates next to the trigger.
         * Prefers opening below/right; flips up/left when there is not enough
         * room, and clamps to the viewport so it is never clipped.
         */
        updatePosition() {
            const wrap = this.$refs.wrap;
            if (!wrap) return;

            const rect = wrap.getBoundingClientRect();
            const menuWidth = 192;                                   // w-48
            const menuHeight = Math.min(this.locales.length * 36 + 16, 320);
            const vw = window.innerWidth || document.documentElement.clientWidth;
            const vh = window.innerHeight || document.documentElement.clientHeight;
            const margin = 8;
            const gap = 6;

            // Horizontal: align the menu's left edge with the trigger and let
            // it extend right; when that would overflow, extend left instead.
            let left;
            if (rect.left + menuWidth <= vw - margin) {
                left = rect.left;
            } else {
                left = Math.max(margin, rect.right - menuWidth);
            }

            // Vertical: open below; when that would overflow, open above.
            let top;
            if (rect.bottom + menuHeight <= vh - margin) {
                top = rect.bottom + gap;
            } else {
                top = Math.max(margin, rect.top - menuHeight - gap);
            }

            this.menuStyle = { left: left + "px", top: top + "px" };
        },
    },
};
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition: opacity 0.15s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
}
</style>
