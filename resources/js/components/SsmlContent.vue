<template>
    <div ref="container" class="ssml-content-wrapper" v-html="content"></div>
</template>

<script>
import { loadSsmlEditor } from "../utils/loadSsmlEditor";

export default {
    name: "SsmlContent",
    props: {
        content: {
            type: String,
            default: "",
        },
    },
    data() {
        return {
            ssmlInstances: [],
        };
    },
    watch: {
        content() {
            this.$nextTick(() => {
                this.renderSsmlBlocks();
            });
        },
    },
    mounted() {
        this.$nextTick(() => {
            this.renderSsmlBlocks();
        });
    },
    beforeUnmount() {
        this.destroyAll();
    },
    methods: {
        async renderSsmlBlocks() {
            this.destroyAll();

            const blocks = this.$refs.container.querySelectorAll(".ssml-block");
            if (blocks.length === 0) { 
                return; 
            }

            let SSMLEditor;
            try {
                SSMLEditor = await loadSsmlEditor();
            } catch (e) {
                console.error("Failed to load SSML editor:", e);
                return;
            }

            blocks.forEach((block) => {
                const data = block.getAttribute("data-ssml");
                if (!data) { 
                    return; 
                }

                try {
                    const model = JSON.parse(data);

                    // Clear placeholder content and mount read-only editor
                    block.innerHTML = "";
                    block.style.cursor = "default";
                    block.style.border = "1px solid #e5e7eb";
                    block.style.borderRadius = "6px";
                    block.style.padding = "10px 14px";
                    block.style.margin = "10px 0";
                    block.style.background = "#fafafa";

                    const editor = new SSMLEditor({
                        el: block,
                        value: model,
                        readOnly: true, // Read-only mode
                    });

                    this.ssmlInstances.push(editor);
                } catch (e) {
                    console.error("Failed to render SSML block:", e);
                }
            });
        },

        /**
         * Destroy all SSML editor instances.
         */
        destroyAll() {
            this.ssmlInstances.forEach((editor) => {
                try {
                    editor.destroy();
                } catch (e) {
                    // Ignore destroy errors
                }
            });
            this.ssmlInstances = [];
        },
    },
};
</script>

<style scoped>
.ssml-content-wrapper {
    /* Inherit parent styles, no extra constraints */
}

.ssml-content-wrapper :deep(.se-editor) {
    border: none;
    background: transparent;
    box-shadow: none;
}

.ssml-content-wrapper :deep(.se-content) {
    padding: 0;
}
</style>
