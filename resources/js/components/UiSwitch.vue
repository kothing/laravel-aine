<template>
    <button
        type="button"
        role="switch"
        :aria-checked="modelValue"
        :aria-label="label"
        :disabled="disabled"
        class="relative inline-flex items-center h-6 w-10 rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
        :class="[
            modelValue ? 'bg-indigo-500' : 'bg-gray-300',
            disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
        ]"
        @click="toggle"
    >
        <span
            class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-200 ease-in-out"
            :class="modelValue ? 'translate-x-5' : 'translate-x-1'"
        />
    </button>
</template>

<script>
export default {
    name: "UiSwitch",
    props: {
        modelValue: {
            type: Boolean,
            default: false,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        label: {
            type: String,
            default: "",
        },
    },
    emits: ["update:modelValue", "change"],
    methods: {
        toggle() {
            if (this.disabled) return;
            this.$emit("update:modelValue", !this.modelValue);
            this.$emit("change", !this.modelValue);
        },
    },
};
</script>
