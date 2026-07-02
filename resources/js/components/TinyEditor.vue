<template>
    <div class="tiny-editor-wrapper">
        <Editor
            v-model="editorContent"
            :init="editorInit"
            :disabled="disabled"
            :id="editorId"
            @input="handleInput"
            class="w-full"
        />
    </div>
</template>

<script>
import Editor from "@tinymce/tinymce-vue";
import tinymce from "tinymce";
import "tinymce/icons/default";
import "tinymce/themes/silver";
import "tinymce/plugins/advlist";
import "tinymce/plugins/autolink";
import "tinymce/plugins/lists";
import "tinymce/plugins/link";
import "tinymce/plugins/image";
import "tinymce/plugins/charmap";
import "tinymce/plugins/print";
import "tinymce/plugins/preview";
import "tinymce/plugins/hr";
import "tinymce/plugins/anchor";
import "tinymce/plugins/pagebreak";
import "tinymce/plugins/searchreplace";
import "tinymce/plugins/wordcount";
import "tinymce/plugins/visualblocks";
import "tinymce/plugins/visualchars";
import "tinymce/plugins/code";
import "tinymce/plugins/fullscreen";
import "tinymce/plugins/insertdatetime";
import "tinymce/plugins/media";
import "tinymce/plugins/nonbreaking";
import "tinymce/plugins/save";
import "tinymce/plugins/table";
import "tinymce/plugins/template";
import "tinymce/plugins/codesample";
import "tinymce/plugins/help";
import "tinymce/plugins/textpattern";
import "tinymce/plugins/emoticons";
import "tinymce/plugins/paste";
import "tinymce/plugins/autoresize";

export default {
    name: "TinyEditor",
    components: {
        Editor,
    },
    props: {
        value: {
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
                "undo redo | formatselect | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist | outdent indent | link image media | forecolor backcolor | code",
        },
        plugins: {
            type: String,
            default:
                "advlist autolink lists link image charmap print preview hr anchor pagebreak searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking save table template codesample help textpattern paste autoresize",
        },
    },
    data() {
        return {
            editorContent: this.value,
        };
    },
    watch: {
        value(newVal) {
            if (newVal !== this.editorContent) {
                this.editorContent = newVal;
            }
        },
    },
    computed: {
        editorInit() {
            return {
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
            this.$emit("input", this.editorContent);
        },
        insertContent(content) {
            this.editorContent += content;
            this.$emit("input", this.editorContent);
        },
    },
};
</script>

<style scoped>
.tiny-editor-wrapper {
    display: block;
    position: relative;
}

.tiny-editor-wrapper ::v-deep(.tox-tinymce) {
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    position: relative;
    min-height: 320px !important;
}

.tiny-editor-wrapper ::v-deep(.tox-editor-header) {
    background-color: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.tiny-editor-wrapper ::v-deep(.tox-toolbar__group) {
    margin-right: 8px;
}

.tiny-editor-wrapper ::v-deep(.tox-edit-area) {
    min-height: 200px;
}
</style>

<style>
/* TinyMCE toolbar overflow popup fix */
.tox-pop {
    position: fixed !important;
}
</style>