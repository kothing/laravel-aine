<template>
    <div class="tiny-editor-wrapper">
        <Editor
            v-model="editorContent"
            :init="editorInit"
            :disabled="disabled"
            :id="editorId"
            @update:modelValue="handleInput"
            class="w-full"
        />
    </div>
</template>

<script>
import Editor from "@tinymce/tinymce-vue";
import tinymce from "tinymce";

// Load TinyMCE runtime assets (theme, model, icons, plugins, skins) on
// demand from the public folder. Bundling them into the JS chunk would
// bloat it with code that only runs on editor pages.
//
// `suffix` is normally inferred from the script src (.min.js); under Vite
// there is no such script tag, so the minified variants must be requested
// explicitly — otherwise the loader would fetch the 1.5 MB unminified
// theme.js instead of theme.min.js.
tinymce.suffix = ".min";
tinymce.baseURL = "/js/tinymce";

export default {
    name: "TinyEditor",
    components: {
        Editor,
    },
    props: {
        modelValue: {
            type: String,
            default: "",
        },
        placeholder: {
            type: String,
            default: "",
        },
        height: {
            type: String,
            default: "320px",
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        editorId: {
            type: String,
            default: "",
        },
        toolbarButtons: {
            type: String,
            default:
                "undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist | outdent indent | link image media | forecolor backcolor | code",
        },
        plugins: {
            type: String,
            default:
                "advlist autolink lists link image media code wordcount help quickbars autoresize",
        },
    },
    data() {
        return {
            editorContent: this.modelValue,
        };
    },
    watch: {
        modelValue(newVal) {
            if (newVal !== this.editorContent) {
                this.editorContent = newVal;
            }
        },
    },
    computed: {
        editorInit() {
            return {
                suffix: ".min",
                base_url: "/js/tinymce",
                license_key: "gpl",
                plugins: this.plugins,
                toolbar: this.toolbarButtons,
                height: parseInt(this.height),
                placeholder: this.placeholder,
                menubar: false,
                branding: false,
                resize: true,
                skin: "oxide",
                skin_url: "/js/tinymce/skins/ui/oxide",
                content_css: "/js/tinymce/skins/content/default/content.css",
                content_style:
                    "body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; line-height: 1.5; color: #333; }",
                toolbar_mode: 'sliding',
                autoresize_min_height: parseInt(this.height),
                autoresize_max_height: 600,
            };
        },
    },
    methods: {
        handleInput() {
            this.$emit("update:modelValue", this.editorContent);
        },
        insertContent(content) {
            this.editorContent += content;
            this.$emit("update:modelValue", this.editorContent);
        },
    },
};
</script>

<style scoped>
.tiny-editor-wrapper {
    display: block;
    position: relative;
}

.tiny-editor-wrapper :deep(.tox-tinymce) {
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    position: relative;
    min-height: 320px !important;
}

.tiny-editor-wrapper :deep(.tox-editor-header) {
    background-color: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.tiny-editor-wrapper :deep(.tox-toolbar__group) {
    margin-right: 8px;
}

.tiny-editor-wrapper :deep(.tox-edit-area) {
    min-height: 200px;
}
</style>

<style>
/* TinyMCE toolbar overflow popup fix */
.tox-pop {
    position: fixed !important;
}
</style>