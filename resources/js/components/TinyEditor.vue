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

        <!-- SSML editor modal -->
        <Modal
            :show="ssmlModalShow"
            max-width="4xl"
            @close="closeSsmlModal"
        >
            <template #title>
                {{ ssmlIsEditing ? __('Edit SSML block') : __('Insert SSML block') }}
            </template>
            <template #content>
                <div ref="ssmlEditorHost" class="ssml-editor-host"></div>
            </template>
            <template #footer>
                <button
                    class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800"
                    @click="closeSsmlModal"
                >{{ __('Cancel') }}</button>
                <button
                    class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                    @click="saveSsmlBlock"
                >{{ __('Confirm') }}</button>
            </template>
        </Modal>
    </div>
</template>

<script>
import Editor from "@tinymce/tinymce-vue";
import tinymce from "tinymce";
import Modal from "./Modal.vue";
import { loadSsmlEditor } from "../utils/loadSsmlEditor";

// Load TinyMCE runtime assets (theme, model, icons, plugins, skins) on
// demand from the public folder. Bundling them into the JS chunk would
// bloat it with code that only runs on editor pages.
tinymce.suffix = ".min";
tinymce.baseURL = "/js/tinymce";

export default {
    name: "TinyEditor",
    components: {
        Editor,
        Modal,
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
            ssmlModalShow: false,
            ssmlIsEditing: false,
            ssmlEditorInstance: null,
            ssmlEditingPlaceholder: null,
            ssmlTinyEditor: null,
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
                plugins: this.plugins + " noneditable",
                toolbar: this.toolbarButtons + " | ssml",
                height: parseInt(this.height),
                placeholder: this.placeholder,
                menubar: false,
                branding: false,
                resize: true,
                skin: "oxide",
                skin_url: "/js/tinymce/skins/ui/oxide",
                content_css: "/js/tinymce/skins/content/default/content.css",
                content_style:
                    "body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; line-height: 1.5; color: #333; }" +
                    " .ssml-block { border: 1px dashed #4f7cff; border-radius: 6px; padding: 10px 14px; margin: 10px 0; background: #f8faff; cursor: pointer; }" +
                    " .ssml-block:hover { border-color: #3b5fe0; background: #f0f4ff; }" +
                    " .ssml-empty { color: #999; }" +
                    " .ssml-edit-hint { color: #4f7cff; font-size: 12px; margin-top: 6px; }" +
                    " .ssml-block .se-editor { position: relative; padding: 0; background: transparent; font-size: 15px; line-height: 2rem; color: #1f2430; }" +
                    " .ssml-block .se-line { margin: 0; min-height: 1.9em; line-height: 2.4rem; }" +
                    " .ssml-block .se-ch { position: relative; display: inline-block; vertical-align: baseline; }" +
                    " .ssml-block .se-ch.se-py { display: inline-block; position: relative; text-align: center; vertical-align: baseline; margin-right: 0.25em; padding-top: 1.15em; }" +
                    " .ssml-block .se-ch.se-py .se-py-line { display: block; font-size: 0.75em; line-height: 0.1; font-weight: 500; white-space: nowrap; padding: 0 0.22em; visibility: hidden; }" +
                    " .ssml-block .se-ch.se-py::before { content: attr(data-val); position: absolute; left: 50%; top: -0.25rem; transform: translateX(-50%); font-size: 0.75em; line-height: 1.2; font-weight: 500; color: #4f7cff; white-space: nowrap; pointer-events: none; user-select: none; padding: 0 0.3em; }" +
                    " .ssml-block .se-bracket { display: inline; position: relative; cursor: default; user-select: none; vertical-align: baseline; }" +
                    " .ssml-block .se-bracket--prosody { --se-bracket-color: #f59e0b; }" +
                    " .ssml-block .se-bracket--emphasis { --se-bracket-color: #ef4444; }" +
                    " .ssml-block .se-bracket--sayAs { --se-bracket-color: #10b981; }" +
                    " .ssml-block .se-bracket::before { display: inline; font-weight: 400; color: var(--se-bracket-color); }" +
                    " .ssml-block .se-bracket--prosody.se-bracket--left::before { content: '['; }" +
                    " .ssml-block .se-bracket--prosody.se-bracket--right::before { content: ']'; }" +
                    " .ssml-block .se-bracket--emphasis.se-bracket--left::before { content: '{'; }" +
                    " .ssml-block .se-bracket--emphasis.se-bracket--right::before { content: '}'; }" +
                    " .ssml-block .se-bracket--sayAs.se-bracket--left::before { content: '('; }" +
                    " .ssml-block .se-bracket--sayAs.se-bracket--right::before { content: ')'; }" +
                    " .ssml-block .se-bracket--ro { cursor: default; opacity: 0.55; }" +
                    " .ssml-block .se-break { display: inline-flex; align-items: center; justify-content: center; vertical-align: middle; width: auto; height: 1.15em; position: relative; cursor: default; color: #94a3b8; }" +
                    " .ssml-block .se-break .se-break-svg { display: block; width: auto; height: 100%; max-height: 1.15em; flex: none; }" +
                    " .ssml-block .se-break--ro { cursor: default; opacity: 0.72; color: #94a3b8; background: transparent !important; box-shadow: none !important; }",
                noneditable_class: "ssml-block",
                extended_valid_elements:
                    "div[class|contenteditable|data-ssml],span[class|data-val],svg[class|viewBox|fill|stroke|stroke-width|stroke-linecap|aria-hidden],path[d|stroke-dasharray]",
                toolbar_mode: "sliding",
                autoresize_min_height: parseInt(this.height),
                autoresize_max_height: 600,
                setup: (editor) => {
                    this.ssmlTinyEditor = editor;

                    // Toolbar button
                    editor.ui.registry.addButton("ssml", {
                        text: "SSML",
                        tooltip: this.__("Insert SSML speech annotation block"),
                        onAction: () => this.openSsmlModal(null),
                    });

                    // Menu item
                    editor.ui.registry.addMenuItem("ssml", {
                        text: this.__("Insert SSML block"),
                        icon: "code-sample",
                        onAction: () => this.openSsmlModal(null),
                    });

                    // Click placeholder to edit
                    editor.on("click", (e) => {
                        const placeholder = e.target.closest(".ssml-block");
                        if (placeholder) {
                            e.preventDefault();
                            this.openSsmlModal(placeholder);
                        }
                    });
                },
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

        // ================================================================
        // SSML methods
        // ================================================================

        /**
         * Open SSML editor modal
         * @param {HTMLElement|null} placeholder - Placeholder element in TinyMCE iframe, null for new block
         */
        async openSsmlModal(placeholder) {
            this.ssmlIsEditing = !!placeholder;
            this.ssmlEditingPlaceholder = placeholder;
            this.ssmlModalShow = true;

            // Lazy-load SSML editor (IIFE bundle)
            let SSMLEditor;
            try {
                SSMLEditor = await loadSsmlEditor();
            } catch (e) {
                console.error("Failed to load SSML editor:", e);
                this.ssmlModalShow = false;
                return;
            }

            // Init SSML editor after modal DOM is rendered
            this.$nextTick(() => {
                const host = this.$refs.ssmlEditorHost;
                if (!host) return;

                // Parse initial value
                let initialValue = "";
                if (placeholder) {
                    const data = placeholder.getAttribute("data-ssml");
                    if (data) {
                        try {
                            initialValue = JSON.parse(data);
                        } catch (e) {
                            initialValue = "";
                        }
                    }
                }

                // Create SSML editor instance (full features, not read-only)
                this.ssmlEditorInstance = new SSMLEditor({
                    el: host,
                    value: initialValue,
                    placeholder: this.__(
                        "Enter speech synthesis text here. Select text and right-click to add phoneme/prosody/say-as annotations..."
                    ),
                    onChange: () => {
                        // No auto-save; persist on Confirm click
                    },
                });
            });
        },

        /** Close modal and destroy SSML editor instance */
        closeSsmlModal() {
            this.ssmlModalShow = false;
            if (this.ssmlEditorInstance) {
                this.ssmlEditorInstance.destroy();
                this.ssmlEditorInstance = null;
            }
            this.ssmlEditingPlaceholder = null;
        },

        /** Save SSML block to TinyMCE */
        saveSsmlBlock() {
            if (!this.ssmlEditorInstance || !this.ssmlTinyEditor) return;

            // Get SSML model and serialize to JSON
            const model = this.ssmlEditorInstance.getValue();
            const json = JSON.stringify(model);
            const previewHtml = this.modelToRichPreviewHtml(model);

            if (this.ssmlIsEditing && this.ssmlEditingPlaceholder) {
                // ---- Update existing placeholder ----
                const ph = this.ssmlEditingPlaceholder;
                ph.setAttribute("data-ssml", json);
                const previewEl = ph.querySelector(".ssml-rich-preview");
                if (previewEl) {
                    previewEl.innerHTML = previewHtml;
                }
            } else {
                // ---- Insert new placeholder at TinyMCE cursor ----
                // Wrap data-ssml in single quotes; escape JSON single quotes as &#39;
                const safeJson = json.replace(/'/g, "&#39;");
                const html =
                    `<div class="ssml-block" contenteditable="false" data-ssml='${safeJson}'>` +
                    `<div class="ssml-rich-preview">${previewHtml}</div>` +
                    `<div class="ssml-edit-hint">${this.__("Click to edit SSML speech annotations")}</div>` +
                    `</div>` +
                    `<p></p>`; // Empty paragraph after placeholder for easier continued typing
                this.ssmlTinyEditor.insertContent(html);
            }

            // Sync to v-model
            this.editorContent = this.ssmlTinyEditor.getContent();
            this.$emit("update:modelValue", this.editorContent);

            this.closeSsmlModal();
        },

        /**
         * Generate rich preview HTML from SSMLModel, replicating the editor's
         * annotation visuals: phoneme pinyin (above char), prosody/emphasis/sayAs
         * brackets, and break marks. Output is static HTML — no event handlers,
         * no editor instances, so TinyMCE's noneditable mechanism can protect it.
         */
        modelToRichPreviewHtml(model) {
            if (!model.blocks || model.blocks.length === 0) {
                return `<span class="ssml-empty">${this.__("(Empty SSML block, click to edit)")}</span>`;
            }

            // Inline SVG for break marks (identical to the editor's vnode.ts)
            const BREAK_SVG =
                '<svg viewBox="0 0 24 24" class="se-break-svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M5 4v16"></path><path d="M9 5.5v13" stroke-dasharray="2.5 3"></path></svg>';

            // HTML escape (no DOM dependency — safe for string building)
            const esc = (str) =>
                String(str)
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;");

            let html = '<div class="se-editor">';

            for (const block of model.blocks) {
                const chars = Array.from(block.text);
                const anns = (model.annotations || []).filter((a) => a.blockId === block.id);

                // Break annotations: char-offset -> annotation
                const breakAt = new Map();
                for (const a of anns) {
                    if (a.type === "break") breakAt.set(a.start, a);
                }

                // Phoneme annotations (each covers one char), sorted by start
                const phonemeList = anns
                    .filter((a) => a.type === "phoneme")
                    .sort((a, b) => a.start - b.start);
                const phonemeAt = (i) => {
                    for (const p of phonemeList) {
                        if (i >= p.start && i < p.end) return p;
                    }
                    return null;
                };

                // Range annotation brackets: char-offset -> [{ann, side}]
                // left bracket sits at ann.start, right bracket at ann.end
                const bracketsAt = new Map();
                const rangedAnns = anns.filter(
                    (a) => a.type !== "break" && a.type !== "phoneme"
                );
                for (const a of rangedAnns) {
                    for (const side of ["left", "right"]) {
                        const pos = side === "left" ? a.start : a.end;
                        const arr = bracketsAt.get(pos) || [];
                        arr.push({ ann: a, side });
                        bracketsAt.set(pos, arr);
                    }
                }

                // Bracket sort: right closes before left opens at same offset;
                // longer ranges render outer (sorted by span length descending)
                const sortSlots = (slots) =>
                    [...slots].sort((a, b) => {
                        if (a.side !== b.side) return a.side === "right" ? -1 : 1;
                        const lenA = a.ann.end - a.ann.start;
                        const lenB = b.ann.end - b.ann.start;
                        return a.side === "left" ? lenB - lenA : lenA - lenB;
                    });

                let blockHtml = '<div class="se-line">';

                for (let i = 0; i < chars.length; i++) {
                    const slots = bracketsAt.get(i);

                    if (slots) {
                        for (const slot of sortSlots(slots.filter((s) => s.side === "right"))) {
                            blockHtml += `<span class="se-bracket se-bracket--${slot.ann.type} se-bracket--right se-bracket--ro"></span>`;
                        }
                    }

                    if (breakAt.has(i)) {
                        blockHtml += `<span class="se-break se-break--ro">${BREAK_SVG}</span>`;
                    }

                    if (slots) {
                        for (const slot of sortSlots(slots.filter((s) => s.side === "left"))) {
                            blockHtml += `<span class="se-bracket se-bracket--${slot.ann.type} se-bracket--left se-bracket--ro"></span>`;
                        }
                    }

                    const ch = chars[i];
                    const py = phonemeAt(i);
                    if (py) {
                        const val = esc(py.attrs?.val || "");
                        blockHtml += `<span class="se-ch se-py" data-val="${val}"><span class="se-py-line">${val}</span>${esc(ch)}</span>`;
                    } else {
                        blockHtml += `<span class="se-ch">${esc(ch)}</span>`;
                    }
                }

                const endSlots = bracketsAt.get(chars.length);
                if (endSlots) {
                    for (const slot of sortSlots(endSlots.filter((s) => s.side === "right"))) {
                        blockHtml += `<span class="se-bracket se-bracket--${slot.ann.type} se-bracket--right se-bracket--ro"></span>`;
                    }
                }

                blockHtml += "</div>";
                html += blockHtml;
            }

            html += "</div>";
            return html;
        },

        /** Escape HTML */
        escapeHtml(str) {
            const div = document.createElement("div");
            div.textContent = str;
            return div.innerHTML;
        },
    },

    beforeUnmount() {
        if (this.ssmlEditorInstance) {
            this.ssmlEditorInstance.destroy();
            this.ssmlEditorInstance = null;
        }
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

/* Editor container inside modal */
.ssml-editor-host {
    min-height: 240px;
    max-height: 500px;
    overflow-y: auto;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 4px;
}

/* Fix placeholder positioning in modal SSML editor */
.ssml-editor-host :deep(.se-editor) {
    min-height: 220px;
}

.ssml-editor-host :deep(.se-placeholder) {
    top: 10px;
    left: 14px;
    line-height: 2rem;
    font-size: 15px;
    display: block;
    width: calc(100% - 28px);
}
</style>

<style>
/* TinyMCE toolbar overflow popup fix */
.tox-pop {
    position: fixed !important;
}
</style>
