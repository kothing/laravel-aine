<template>
    <div class="admin__project-collection-sidebar p-4 bg-white">
        <div class="mb-4 border-b border-gray-100">
            <h4
                class="mb-2 p-2 font-bold text-lg flex justify-between items-center h-10"
                v-if="!openSearchInput"
            >
                <div>Collections</div>
                <div>
                    <a
                        class="text-sm text-blue-500 p-1 px-3 cursor-pointer rounded-md hover:bg-gray-100 whitespace-nowrap"
                        @click="openNewCollectionModal = true"
                    >
                        + Add New
                    </a>
                </div>
                <div>
                    <a
                        class="text-sm text-blue-500 p-1 px-3 cursor-pointer rounded-md hover:bg-gray-100"
                        @click="openSearchInput = true"
                    >
                        <i class="fas fa-search"></i>
                    </a>
                </div>
            </h4>

            <div
                v-if="openSearchInput"
                class="mb-2 relative flex w-full flex-wrap items-stretch mb-2 h-10"
            >
                <span
                    class="h-full leading-snug font-normal absolute text-center text-gray-400 absolute bg-transparent rounded-md text-base items-center justify-center w-8 pl-3 py-3"
                >
                    <i class="fas fa-search"></i>
                </span>
                <input
                    type="text"
                    v-model="searchCollection"
                    placeholder="Search..."
                    class="px-3 py-3 placeholder-gray-400 text-gray-700 bg-white rounded-md text-sm w-full pl-10 border-gray-200 focus:border-gray-300"
                    autofocus
                />
                <span
                    class="h-full leading-snug font-normal absolute text-center text-gray-400 absolute bg-transparent rounded-md text-base items-center justify-center w-8 py-3 right-0 pr-3 cursor-pointer"
                    @click="(searchCollection = ''), (openSearchInput = false)"
                >
                    <i class="fas fa-times-circle"></i>
                </span>
            </div>
        </div>

        <draggable
            :list="project.collections"
            @end="sortCollections"
            v-bind="dragOptions"
            handle=".handle"
            class="_drag-box"
        >
            <transition-group type="transition" class="_trans-group">
                <div
                    class="mb-2 inline-flex items-center w-full"
                    v-for="collection in filterSearch"
                    :key="collection.id"
                >
                    <i class="fas fa-grip-vertical mr-4 text-gray-500 an__cursor-move handle"></i>
                    <router-link
                        :to="{
                            name: 'projects.collections.list',
                            params: {
                                id: project.id,
                                col_id: collection.id,
                            },
                        }"
                        class="block w-full p-2 cursor-pointer hover:bg-gray-100 rounded"
                        >{{ collection.name }}</router-link
                    >
                </div>
            </transition-group>
        </draggable>

        <ui-modal
            :show="openNewCollectionModal"
            @close="closeNewCollectionModal"
        >
            <template #title> Add New Collection </template>

            <template #content>
                <div class="mt-4">
                    <form @submit.prevent="addNewCollectionSubmit">
                        <div class="grid grid-cols-1 gap-4">
                            <div class="col-span-12 sm:col-span-12">
                                <label v-formlabel>Name</label>
                                <input
                                    type="text"
                                    v-model="new_collection.name"
                                    v-forminput
                                    @input="
                                        new_collection.slug = $slugify(
                                            new_collection.name
                                        )
                                    "
                                    autofocus
                                    placeholder="Name"
                                />
                                <p
                                    class="text-sm text-red-600 mt-2"
                                    v-if="new_collection.errors.name"
                                >
                                    {{ new_collection.errors.name[0] }}
                                </p>
                            </div>
                            <div class="col-span-12 sm:col-span-12">
                                <label v-formlabel>Slug</label>
                                <input
                                    type="text"
                                    v-model="new_collection.slug"
                                    v-forminput
                                    placeholder="Slug"
                                />
                                <p
                                    class="text-sm text-red-600 mt-2"
                                    v-if="new_collection.errors.slug"
                                >
                                    {{ new_collection.errors.slug[0] }}
                                </p>
                            </div>
                        </div>
                    </form>
                </div>
            </template>

            <template #footer>
                <ui-button
                    color="gray-200"
                    hover="gray-300"
                    @click.native="closeNewCollectionModal"
                >
                    <span class="text-gray-800">Cancel</span>
                </ui-button>

                <ui-button
                    color="indigo-500"
                    @click.native="addNewCollectionSubmit"
                    :class="{ 'opacity-25': processing }"
                    :disabled="processing"
                >
                    Save Collection
                </ui-button>
            </template>
        </ui-modal>
    </div>
</template>

<script>
import draggable from "vuedraggable";
import UiButton from "../../../../UI/Button.vue";
import UiModal from "../../../../UI/Modal.vue";

export default {
    components: {
        UiButton,
        UiModal,
        draggable,
    },

    props: ["project"],

    data() {
        return {
            openNewCollectionModal: false,
            processing: false,
            new_collection: {
                project_id: this.$route.params.project_id,
                errors: {
                    name: "",
                    slug: "",
                },
            },
            searchCollection: "",
            openSearchInput: false,
        };
    },

    methods: {
        addNewCollectionSubmit() {
            axios
                .post(
                    "/admin/collections/store/" + this.$route.params.project_id,
                    this.new_collection
                )
                .then(
                    (response) => {
                        this.$toast.success("New collection created.");
                        this.closeNewCollectionModal();
                        this.project.collections.push(response.data);
                    },
                    (error) => {
                        if (error.response.status == 422) {
                            this.new_collection.errors =
                                error.response.data.errors;
                        }
                    }
                );
        },

        closeNewCollectionModal() {
            this.openNewCollectionModal = false;
            this.new_collection = {
                project_id: this.$route.params.project_id,
                errors: {
                    name: "",
                    slug: "",
                },
            };
            this.processing = false;
        },

        sortCollections() {
            this.project.collections.map((collection, index) => {
                collection.order = index + 1;
            });
            axios.post(
                "/admin/collections/update-order/" +
                    this.$route.params.project_id,
                this.project.collections
            );
        },
    },

    computed: {
        dragOptions() {
            return {
                animation: 200,
            };
        },
        filterSearch() {
            if (this.project.collections !== undefined) {
                return this.project.collections.filter((collection) => {
                    return (
                        !this.searchCollection ||
                        collection.name
                            .toLowerCase()
                            .indexOf(this.searchCollection.toLowerCase()) > -1
                    );
                });
            }
        },
    },
};
</script>
