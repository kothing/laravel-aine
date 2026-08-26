<template>
    <ui-modal :show="show" max-width="4xl" @close="$emit('close')">
        <template #title>
            {{ __('Content History') }}
        </template>
        <template #content>
            <div v-if="loading" class="py-8 text-center text-gray-500">
                <i class="fa fa-spinner fa-spin mr-2"></i>{{ __('Loading revisions...') }}
            </div>

            <div v-else-if="revisions.length === 0" class="py-8 text-center text-gray-500">
                {{ __('No revisions found for this content.') }}
            </div>

            <div v-else class="max-h-96 overflow-y-auto">
                <div v-for="(revision, index) in revisions" :key="revision.id" class="flex items-start justify-between border-b border-gray-100 py-3 px-2 hover:bg-gray-50">
                    <div class="flex items-start space-x-3">
                        <div class="mt-1 text-gray-400">
                            <i class="fa fa-history"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-800">
                                {{ revision.note || __('Revision') }}
                                <span v-if="index === 0" class="ml-2 text-xs bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded-full">{{ __('Current') }}</span>
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                {{ revision.user ? revision.user.name : __('Unknown user') }}
                                &middot;
                                {{ formatDate(revision.created_at) }}
                            </div>
                            <div v-if="revision.data && Object.keys(revision.data).length" class="text-xs text-gray-500 mt-1 break-all">
                                <span class="text-gray-400">{{ __('Snapshot') }}:</span>
                                <code class="ml-1 bg-gray-100 px-1.5 py-0.5 rounded">{{ summarize(revision.data) }}</code>
                            </div>
                        </div>
                    </div>
                    <div class="flex-shrink-0 ml-4">
                        <ui-button v-if="index !== 0" color="white" hover="indigo-50" :disabled="restoring" @click="restore(revision)">
                            {{ __('Restore') }}
                        </ui-button>
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <ui-button color="white" hover="gray-200" @click="$emit('close')">
                {{ __('Close') }}
            </ui-button>
        </template>
    </ui-modal>
</template>

<script>
export default {
    props: {
        show: {
            type: Boolean,
            default: false,
        },
        projectId: {
            type: [Number, String],
            required: true,
        },
        collectionId: {
            type: [Number, String],
            required: true,
        },
        contentId: {
            type: [Number, String],
            required: true,
        },
    },

    data() {
        return {
            revisions: [],
            loading: false,
            restoring: false,
        };
    },

    watch: {
        show(value) {
            if (value) {
                this.load();
            }
        },
    },

    methods: {
        load() {
            this.loading = true;
            axios
                .get("content/revisions/" + this.projectId + "/" + this.collectionId + "/" + this.contentId)
                .then((response) => {
                    this.revisions = response.data;
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        restore(revision) {
            if (!window.confirm(__("Are you sure you want to restore this revision? Current changes will be overwritten."))) {
                return;
            }
            this.restoring = true;
            axios
                .post("content/revisions/" + this.projectId + "/" + this.collectionId + "/" + this.contentId + "/" + revision.id + "/restore")
                .then(() => {
                    this.$toast.success(__("Revision restored."));
                    this.$emit("restored");
                    this.load();
                })
                .finally(() => {
                    this.restoring = false;
                });
        },

        summarize(data) {
            const keys = Object.keys(data);
            const preview = keys.slice(0, 3).map((key) => {
                let value = data[key];
                if (typeof value === "string" && value.length > 40) {
                    value = value.slice(0, 40) + "...";
                }
                return key + ": " + value;
            });
            let text = preview.join(", ");
            if (keys.length > 3) {
                text += " +" + (keys.length - 3);
            }
            return text;
        },

        formatDate(value) {
            if (!value) return "";
            const date = new Date(value);
            return date.toLocaleString();
        },
    },
};
</script>
