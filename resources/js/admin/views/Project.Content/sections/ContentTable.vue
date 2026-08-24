<template>
    <div
        class="admin__project-content-table"
    >
        <h4 class="h-10 flex justify-end items-center font-bold text-lg mb-2">
            <div class="flex-1">
                {{ __(collection.name) }}
                <small class="text-gray-400 ml-1">#{{ collection.slug }}</small>
            </div>

            <router-link
                v-if="$route.params.col_id !== undefined && !relationSelect"
                :to="{
                    name: 'projects.content.forms',
                    params: {
                        project_id: $route.params.project_id,
                        col_id: $route.params.col_id,
                    },
                }"
                class="bg-green-500 items-center px-4 py-2 border border-transparent rounded-md text-sm text-white focus:outline-none transition ease-in-out duration-150 mr-2"
            >
                <i class="fab fa-wpforms"></i> {{ __('Forms') }} ({{ form_count }})
            </router-link>

            <router-link
                v-if="!relationSelect && collection_id !== undefined"
                :to="{
                    name: 'projects.content.new',
                    params: {
                        project_id: $route.params.project_id,
                        col_id: collection_id,
                    },
                }"
                class="bg-indigo-500 items-center px-4 py-2 border border-transparent rounded-md text-sm text-white focus:outline-none transition ease-in-out duration-150"
            >
                {{ __('+ Create New') }}
            </router-link>

            <ui-button color="green-500" v-if="relationSelect && selected.length !== 0" @click="addSelected()"><i class="fa fa-link"></i> {{ __('Add Selected') }}</ui-button>
        </h4>

        <div class="flex space-between mb-2">
            <div class="relative flex w-full flex-wrap items-stretch">
                <span class="h-full leading-snug font-normal absolute text-center text-gray-400 absolute bg-transparent rounded-md text-base items-center justify-center w-8 pl-3 py-2">
                    <i class="fas fa-search"></i>
                </span>
                <form class="w-full" @submit.prevent="getContent()">
                    <input
                        type="text"
                        v-model="search"
                        @input="getContent()"
                        :placeholder="__('Type something and press enter')"
                        class="px-3 py-2 placeholder-gray-400 text-gray-700 bg-white rounded-md text-sm w-full pl-10 border-gray-200 focus:border-gray-300"
                    />
                </form>
                <span
                    v-show="search != ''"
                    class="h-full leading-snug font-normal absolute text-center text-gray-400 absolute bg-transparent rounded-md text-base items-center justify-center w-8 py-2 right-0 pr-3 cursor-pointer"
                    @click="(search = ''), getContent()"
                >
                    <i class="fas fa-times-circle"></i>
                </span>
            </div>

            <div class="w-auto h-auto ml-2">
                <ui-dropdown align="right" :closeable="false">
                    <template #trigger>
                        <ui-button :color="'white'" class="border border-gray-200">
                            <i class="fa fa-cog text-gray-700"></i>
                        </ui-button>
                    </template>

                    <template #content>
                        <div class="border border-gray-200 p-3">
                            <div class="flex items-start m-2">
                                <div class="flex items-center h-5">
                                    <input id="created_at" v-model="columns.created_at" type="checkbox" v-formcheckbox @click="changeColumnSettings()" />
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="created_at" class="font-medium text-gray-700">{{ __('Created At') }}</label>
                                </div>
                            </div>
                            <div class="flex items-start m-2">
                                <div class="flex items-center h-5">
                                    <input id="created_by" v-model="columns.created_by" type="checkbox" v-formcheckbox @click="changeColumnSettings()" />
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="created_by" class="font-medium text-gray-700">{{ __('Created By') }}</label>
                                </div>
                            </div>
                            <div class="flex items-start m-2">
                                <div class="flex items-center h-5">
                                    <input id="updated_at" v-model="columns.updated_at" type="checkbox" v-formcheckbox @click="changeColumnSettings()" />
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="updated_at" class="font-medium text-gray-700">{{ __('Updated At') }}</label>
                                </div>
                            </div>
                            <div class="flex items-start m-2">
                                <div class="flex items-center h-5">
                                    <input id="updated_by" v-model="columns.updated_by" type="checkbox" v-formcheckbox @click="changeColumnSettings()" />
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="updated_by" class="font-medium text-gray-700">{{ __('Updated By') }}</label>
                                </div>
                            </div>
                            <div class="flex items-start m-2">
                                <div class="flex items-center h-5">
                                    <input id="published_at" v-model="columns.published_at" type="checkbox" v-formcheckbox @click="changeColumnSettings()" />
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="published_at" class="font-medium text-gray-700">{{ __('Published At') }}</label>
                                </div>
                            </div>
                            <div class="flex items-start m-2">
                                <div class="flex items-center h-5">
                                    <input id="published_by" v-model="columns.published_by" type="checkbox" v-formcheckbox @click="changeColumnSettings()" />
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="published_by" class="font-medium text-gray-700">{{ __('Published By') }}</label>
                                </div>
                            </div>
                            <div v-for="field in collection.fields" :key="field.id">
                                <div class="flex items-start m-2" v-if="field.type != 'password' && field.type != 'json'">
                                    <div class="flex items-center h-5">
                                        <input :id="field.name" v-model="columns[field.name]" type="checkbox" v-formcheckbox @click="changeColumnSettings()" />
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label :for="field.name" class="font-medium text-gray-700">{{ field.label }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </ui-dropdown>
            </div>
        </div>

        <div class="w-full flex justify-between text-sm text-gray-700 mb-2 pl-1">
            <div class="flex">
                <div class="py-1">{{ selected.length }} {{ __('items selected') }}</div>
                <div
                    v-if="selected.length !== 0 && listOptions.getItems !== 'trashed'"
                    class="ml-2 cursor-pointer text-green-500 font-bold py-1 px-3 rounded-md hover:bg-gray-100"
                    @click="publishSelected"
                >
                    <i class="fa fa-cloud-upload-alt"></i> {{ __('publish') }}
                </div>
                <div
                    v-if="selected.length !== 0 && listOptions.getItems !== 'trashed'"
                    class="ml-2 cursor-pointer text-gray-500 font-bold py-1 px-3 rounded-md hover:bg-gray-100"
                    @click="unPublishSelected"
                >
                    <i class="fa fa-cloud-download-alt"></i> {{ __('unpublish') }}
                </div>
                <div
                    v-if="selected.length !== 0 && listOptions.getItems !== 'trashed'"
                    class="ml-2 cursor-pointer text-orange-500 font-bold py-1 px-3 rounded-md hover:bg-gray-100"
                    @click="moveToTrashSelected"
                >
                    <i class="fa fa-trash-restore"></i> {{ __('move to trash') }}
                </div>
                <div
                    v-if="selected.length !== 0 && listOptions.getItems === 'trashed'"
                    class="ml-2 cursor-pointer text-orange-500 font-bold py-1 px-3 rounded-md hover:bg-gray-100"
                    @click="restoreSelected"
                >
                    <i class="fa fa-recycle"></i> {{ __('restore') }}
                </div>
                <div v-if="selected.length !== 0" class="ml-2 cursor-pointer text-red-500 font-bold py-1 px-3 rounded-md hover:bg-gray-100" @click="deleteSelected">
                    <i class="fa fa-trash-alt"></i> {{ __('delete') }}
                </div>
            </div>

            <div class="flex">
                <div class="ml-1 cursor-pointer text-blue-500 py-1 px-1 rounded-md hover:bg-gray-100" @click="changeGetItems('all')" :class="{ 'bg-gray-200': listOptions.getItems == 'all' }">
                    {{ __('All') }}({{ totalCount }})
                </div>
                <div
                    class="ml-1 cursor-pointer text-blue-500 py-1 px-1 rounded-md hover:bg-gray-100"
                    @click="changeGetItems('published')"
                    :class="{
                        'bg-gray-200': listOptions.getItems == 'published',
                    }"
                >
                    {{ __('Published') }}({{ publishedCount }})
                </div>
                <div class="ml-1 cursor-pointer text-blue-500 py-1 px-1 rounded-md hover:bg-gray-100" @click="changeGetItems('draft')" :class="{ 'bg-gray-200': listOptions.getItems == 'draft' }">
                    {{ __('Draft') }}({{ draftCount }})
                </div>
                <div
                    v-if="!hasAddSelectedListener"
                    class="ml-1 cursor-pointer text-blue-500 py-1 px-1 rounded-md hover:bg-gray-100"
                    @click="changeGetItems('trashed')"
                    :class="{
                        'bg-gray-200': listOptions.getItems == 'trashed',
                    }"
                >
                    {{ __('Trashed') }}({{ trashedCount }})
                </div>
            </div>
        </div>

        <div class="w-full border rounded-md">
            <div class="overflow-x-auto sm:rounded-md">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr class="relative">
                            <th scope="col" class="px-6 py-3 text-center">
                                <div class="w-full pl-1">
                                    <input type="checkbox" v-if="relation_type != 1" v-model="selectAll" @click="selectAllFn()" v-formcheckbox class="cursor-pointer" />
                                </div>
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap"
                            >
                                {{ __('ID') }}
                            </th>
                            <th
                                scope="col"
                                v-for="field in collection.fields"
                                :key="field.id"
                                v-show="field.type != 'password' && field.type != 'json' && columns[field.name]"
                                @click="sortBy(field.name, 1)"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap cursor-pointer"
                            >
                                <div class="w-full flex justify-between item-center">
                                    {{ field.label
                                    }}<!--Title-->
                                    <i v-if="listOptions.orderBy == field.name && listOptions.criteria == 'ASC'" class="fa fa-sort-amount-up-alt text-indigo-500 ml-2"></i>
                                    <i v-if="listOptions.orderBy == field.name && listOptions.criteria == 'DESC'" class="fa fa-sort-amount-down-alt text-indigo-500 ml-2"></i>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                            <th
                                scope="col"
                                v-if="columns.created_at"
                                @click="sortBy('created_at')"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap cursor-pointer"
                            >
                                <div class="w-full flex justify-between item-center">
                                    {{ __('Created At') }}
                                    <i v-if="listOptions.orderBy == 'created_at' && listOptions.criteria == 'ASC'" class="fa fa-sort-amount-up-alt text-indigo-500 ml-2"></i>
                                    <i v-if="listOptions.orderBy == 'created_at' && listOptions.criteria == 'DESC'" class="fa fa-sort-amount-down-alt text-indigo-500 ml-2"></i>
                                </div>
                            </th>
                            <th
                                scope="col"
                                v-if="columns.created_by"
                                @click="sortBy('created_by')"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap cursor-pointer"
                            >
                                <div class="w-full flex justify-between item-center">
                                    {{ __('Created By') }}
                                    <i v-if="listOptions.orderBy == 'created_by' && listOptions.criteria == 'ASC'" class="fa fa-sort-amount-up-alt text-indigo-500 ml-2"></i>
                                    <i v-if="listOptions.orderBy == 'created_by' && listOptions.criteria == 'DESC'" class="fa fa-sort-amount-down-alt text-indigo-500 ml-2"></i>
                                </div>
                            </th>
                            <th
                                scope="col"
                                v-if="columns.updated_at"
                                @click="sortBy('updated_at')"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap cursor-pointer"
                            >
                                <div class="w-full flex justify-between item-center">
                                    {{ __('Updated At') }}
                                    <i v-if="listOptions.orderBy == 'updated_at' && listOptions.criteria == 'ASC'" class="fa fa-sort-amount-up-alt text-indigo-500 ml-2"></i>
                                    <i v-if="listOptions.orderBy == 'updated_at' && listOptions.criteria == 'DESC'" class="fa fa-sort-amount-down-alt text-indigo-500 ml-2"></i>
                                </div>
                            </th>
                            <th
                                scope="col"
                                v-if="columns.updated_by"
                                @click="sortBy('updated_by')"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap cursor-pointer"
                            >
                                <div class="w-full flex justify-between item-center">
                                    {{ __('Updated By') }}
                                    <i v-if="listOptions.orderBy == 'updated_by' && listOptions.criteria == 'ASC'" class="fa fa-sort-amount-up-alt text-indigo-500 ml-2"></i>
                                    <i v-if="listOptions.orderBy == 'updated_by' && listOptions.criteria == 'DESC'" class="fa fa-sort-amount-down-alt text-indigo-500 ml-2"></i>
                                </div>
                            </th>
                            <th
                                scope="col"
                                v-if="columns.published_at"
                                @click="sortBy('published_at')"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap cursor-pointer"
                            >
                                <div class="w-full flex justify-between item-center">
                                    {{ __('Published At') }}
                                    <i v-if="listOptions.orderBy == 'published_at' && listOptions.criteria == 'ASC'" class="fa fa-sort-amount-up-alt text-indigo-500 ml-2"></i>
                                    <i v-if="listOptions.orderBy == 'published_at' && listOptions.criteria == 'DESC'" class="fa fa-sort-amount-down-alt text-indigo-500 ml-2"></i>
                                </div>
                            </th>
                            <th
                                scope="col"
                                v-if="columns.published_by"
                                @click="sortBy('published_by')"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap cursor-pointer"
                            >
                                <div class="w-full flex justify-between item-center">
                                    {{ __('Published By') }}
                                    <i v-if="listOptions.orderBy == 'published_by' && listOptions.criteria == 'ASC'" class="fa fa-sort-amount-up-alt text-indigo-500 ml-2"></i>
                                    <i v-if="listOptions.orderBy == 'published_by' && listOptions.criteria == 'DESC'" class="fa fa-sort-amount-down-alt text-indigo-500 ml-2"></i>
                                </div>
                            </th>
                            <th
                                v-if="listOptions.getItems !== 'trashed'"
                                scope="col"
                                class="an__sticky right-0 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase shadow-sm bg-gray-100"
                            >
                                <span class="inline-block">{{ __('Action') }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="item in content.data" :key="item.id" class="relative">
                            <td class="pl-2 py-4 text-sm text-center w-px">
                                <input type="checkbox" :checked="checkIfSelected(item.id)" @click="selectRecord(item.id)" v-formcheckbox class="cursor-pointer" />
                            </td>
                            <td class="px-6 py-3 text-sm w-px whitespace-nowrap text-gray-600">
                                {{ item.id }}
                            </td>
                            <td
                                v-for="field in collection.fields"
                                :key="field.id"
                                v-show="field.type != 'password' && field.type != 'json' && columns[field.name]"
                                class="px-6 py-3 text-sm min-w-full whitespace-nowrap"
                            >
                                <span v-for="meta in item.meta" :key="meta.id">
                                    <span v-if="meta.field_name == field.name">
                                        <span
                                            v-if="field.type == 'date'"
                                            :class="{
                                                'rounded-md bg-gray-100 p-1 mr-1': field.options.repeatable && meta.value !== null,
                                            }"
                                        >
                                            <span v-if="field.options.timepicker">{{ $filters.date(meta.value, "YYYY-MM-DD hh:mm A") }}</span>
                                            <span v-else>{{ $filters.date(meta.value) }}</span>
                                        </span>
                                        <span
                                            v-else-if="field.type == 'longtext' && meta.value !== null"
                                            :title="meta.value"
                                            :class="{
                                                'rounded-md bg-gray-100 p-1 mr-1': field.options.repeatable && meta.value !== null,
                                            }"
                                        >
                                            {{ meta.value.substring(0, 20) }}
                                            <span v-if="meta.value.length > 20">...</span>
                                        </span>
                                        <span v-else-if="field.type == 'richtext'">
                                            <span v-if="meta.value !== ''" class="text-indigo-500 cursor-pointer hover:bg-gray-100 rounded-md p-2" @click="showText(field, meta.value)"
                                                ><i class="fas fa-align-center"></i
                                            ></span>
                                        </span>
                                        <span v-else-if="field.type == 'media'">
                                            <span v-if="meta.value !== ''" class="text-indigo-500 cursor-pointer hover:bg-gray-100 rounded-md p-2" @click="showMedia(field, meta.value)"
                                                ><i class="fa fa-photo-video"></i
                                            ></span>
                                        </span>
                                        <span v-else-if="field.type == 'relation'">
                                            <span v-if="meta.value !== ''" class="text-indigo-500 cursor-pointer hover:bg-gray-100 rounded-md p-2" @click="showRelationlist(field, meta.value)"
                                                ><i class="fa fa-link"></i
                                            ></span>
                                        </span>
                                        <span v-else>
                                            <span
                                                :class="{
                                                    'rounded-md bg-gray-100 p-1 mr-1': field.options.repeatable && meta.value !== null,
                                                }"
                                                >{{ meta.value }}</span
                                            >
                                        </span>
                                    </span>
                                </span>
                            </td>
                            <td class="pl-2 py-4 text-sm text-center w-24">
                                <div v-if="item.form_id === null">
                                    <span v-if="item.published_at !== null" class="text-gray-500 rounded-md bg-green-200 px-3 py-1 whitespace-nowrap">{{ __('published') }}</span>
                                    <span v-else class="text-gray-500 rounded-md bg-gray-200 px-3 py-1 whitespace-nowrap">{{ __('draft') }}</span>
                                </div>
                                <div v-else>
                                    <span
                                        v-if="item.published_at !== null"
                                        class="text-gray-200 rounded-md bg-blue-400 px-3 py-1 whitespace-nowrap"
                                        v-tooltip="'Submitted at ' + dateFormat(item.form.created_at) + '. Form name: ' + item.form.name"
                                        >{{ __('published') }}</span
                                    >
                                    <span
                                        v-else
                                        class="text-gray-500 rounded-md bg-blue-200 px-3 py-1 whitespace-nowrap"
                                        v-tooltip="'Submitted at ' + dateFormat(item.form.created_at) + '. Form name: ' + item.form.name"
                                        >{{ __('draft') }}</span
                                    >
                                </div>
                            </td>
                            <td v-if="columns.created_at" class="px-6 py-3 text-sm w-px whitespace-nowrap text-gray-600">
                                {{ $filters.date(item.created_at, "D MMM YYYY, H:mm") }}
                            </td>
                            <td v-if="columns.created_by" class="px-6 py-3 text-sm w-px whitespace-nowrap text-gray-600 text-center">
                                <div class="bg-green-500 text-white p-2 text-md rounded-full text-center mr-2 w-9" v-tooltip="item.created_by.name">
                                    <div class="w-full text-center">
                                        {{ getUserNameInitials(item.created_by.name) }}
                                    </div>
                                </div>
                            </td>
                            <td v-if="columns.updated_at" class="px-6 py-3 text-sm w-px whitespace-nowrap text-gray-600">
                                {{ $filters.date(item.updated_at, "D MMM YYYY, H:mm") }}
                            </td>
                            <td v-if="columns.updated_by" class="px-6 py-3 text-sm w-px whitespace-nowrap text-gray-600">
                                <div v-if="item.updated_by !== null" class="bg-green-500 text-white p-2 text-md rounded-full text-center mr-2 w-9" v-tooltip="item.updated_by.name">
                                    <div class="w-full text-center">
                                        {{ getUserNameInitials(item.updated_by.name) }}
                                    </div>
                                </div>
                            </td>
                            <td v-if="columns.published_at" class="px-6 py-3 text-sm w-px whitespace-nowrap text-gray-600">
                                {{ $filters.date(item.published_at, "D MMM YYYY, H:mm") }}
                            </td>
                            <td v-if="columns.published_by" class="px-6 py-3 text-sm w-px whitespace-nowrap text-gray-600">
                                <div v-if="item.published_by !== null" class="bg-green-500 text-white p-2 text-md rounded-full text-center mr-2 w-9" v-tooltip="item.published_by.name">
                                    <div class="w-full text-center">
                                        {{ getUserNameInitials(item.published_by.name) }}
                                    </div>
                                </div>
                            </td>
                            <td v-if="listOptions.getItems != 'trashed'" class="an__sticky right-0 px-2 py-4 text-sm w-28 whitespace-nowrap text-center shadow-sm bg-white">
                                <div class="flex items-center justify-center gap-1 py-2">
                                    <router-link
                                        :to="{
                                            name: 'projects.content.edit',
                                            params: {
                                                project_id: $route.params.project_id,
                                                col_id: collection_id,
                                                content_id: item.id,
                                            },
                                        }"
                                        class="text-indigo-500 p-2 px-3 rounded-md hover:bg-gray-100 cursor-pointer bg-gray-50"
                                    >
                                        <i class="fa fa-pencil-alt"></i>
                                    </router-link>
                                    <a class="text-orange-500 p-2 px-3 rounded-md hover:bg-gray-100 cursor-pointer bg-gray-50" @click="moveToTrashContent(item)">
                                        <i class="fa fa-trash-restore"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="content.data != undefined && content.data.length === 0">
                            <td colspan="100%" class="text-center text-sm text-gray-500 p-5">{{ __('No data found') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="flex justify-between items-center mt-2">
            <div class="block">
                <pagination :data="content" size="small" :limit="3" @pagination-change-page="getContent"></pagination>
            </div>

            <div class="text-sm italic text-gray-500">{{ paginationInfo }}</div>

            <div class="text-sm italic text-gray-500">
                <select v-model="each" @change="getContent()" v-formselect>
                    <option value="5">5</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <ui-modal maxWidth="5xl" :show="openTextModal" @close="closeTextModal">
            <template #title>
                {{ textModalFieldName }}
            </template>

            <template #content>
                <div v-html="textRecord"></div>
            </template>

            <template #footer>
                <ui-button color="gray-200" hover="gray-300" @click="closeTextModal">
                    <span class="text-gray-800">{{ __('Close') }}</span>
                </ui-button>
            </template>
        </ui-modal>

        <ui-modal maxWidth="5xl" :show="openMediaModal" @close="closeMediaModal">
            <template #title>
                {{ mediaModalFieldName }}
            </template>

            <template #content>
                <div class="my-5 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <div v-for="file in mediaRecords" :key="file.id" class="relative">
                        <div class="rounded-md bg-white">
                            <div class="w-full h-48 absolute top-0 rounded-md left-0 bg-black bg-opacity-70 items-center text-center flex opacity-0 hover:opacity-100 z-10">
                                <div class="w-full z-20">
                                    <div class="text-sm text-white w-full mb-3" :title="file.name">
                                        {{ file.name.substring(0, 20) }}
                                        <span v-if="file.name.length > 20">...</span>
                                    </div>
                                    <div class="text-sm w-full">
                                        <a :href="file.full_url" target="_blank">
                                            <i
                                                v-if="file.type == 'jpg' || file.type == 'jpeg' || file.type == 'png' || file.type == 'bmp' || file.type == 'gif' || file.type == 'webp'"
                                                class="fa fa-eye text-gray-100 cursor-pointer hover:text-gray-200 text-lg mr-2"
                                            ></i>
                                            <i v-else class="fa fa-file-download text-gray-100 cursor-pointer hover:text-gray-200 text-lg mr-2"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <img
                                class="w-full h-48 object-cover rounded-md border rounded-md"
                                v-if="file.type == 'jpg' || file.type == 'jpeg' || file.type == 'png' || file.type == 'gif' || file.type == 'webp'"
                                :src="file.full_url_thumb"
                            />

                            <div class="w-full h-48 object-cover flex items-center text-center border rounded-md" v-else>
                                <div class="w-full">
                                    <i v-if="file.type == 'pdf'" class="far fa-file-pdf text-5xl text-red-500"></i>
                                    <i v-else-if="file.type == 'avi' || file.type == 'mp4' || file.type == 'mov' || file.type == 'webm'" class="far fa-file-video text-5xl text-blue-500"></i>
                                    <i v-else-if="file.type == 'wav' || file.type == 'ogg' || file.type == 'mpeg'" class="far fa-file-audio text-5xl text-yellow-500"></i>
                                    <i v-else-if="file.type == 'xls' || file.type == 'xlsx'" class="far fa-file-excel text-5xl text-green-500"></i>
                                    <i v-else-if="file.type == 'doc' || file.type == 'docx'" class="far fa-file-word text-5xl text-blue-500"></i>
                                    <i v-else-if="file.type == 'zip'" class="far fa-file-archive text-5xl text-yellow-300"></i>
                                    <i v-else class="far fa-file text-5xl text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <ui-button color="gray-100" hover="gray-200" @click="closeMediaModal">
                    <span class="text-gray-800">{{ __('Close') }}</span>
                </ui-button>
            </template>
        </ui-modal>

        <ui-modal maxWidth="5xl" :show="openRelationModal" @close="closeRelationModal">
            <template #title>
                {{ relationModalFieldName }}
            </template>

            <template #content>
                <div class="overflow-x-auto sm:rounded-md" v-if="relationRecords.length > 0">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">{{ __('Collection') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">{{ __('Status') }}</th>

                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">
                                    <div class="w-full flex justify-between item-center">{{ __('Created At') }}</div>
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap"
                                    v-for="field in relationRecords.collection.fields"
                                    :key="field.id"
                                    v-show="
                                        field.type != 'richtext' &&
                                        field.type != 'password' &&
                                        field.type != 'media' &&
                                        field.type != 'json' &&
                                        field.type != 'relation' &&
                                        !JSON.parse(field.options).hideInContentList
                                    "
                                >
                                    <div class="w-full flex justify-between item-center">
                                        {{ field.label }}
                                    </div>
                                </th>
                            </tr>
                        </thead>

                        <tr v-for="item in relationRecords" :key="item.id">
                            <td class="pl-2 py-4 text-sm text-center w-px">
                                <span class="text-gray-500 text-sm rounded-md bg-gray-200 px-3 py-1 whitespace-nowrap">
                                    {{ __(relationRecords.collection.name) }}
                                </span>
                            </td>
                            <td class="pl-2 py-4 text-sm text-center w-px">
                                <span v-if="item.published_at !== null" class="text-gray-500 text-sm rounded-md bg-green-200 px-3 py-1">{{ __('published') }}</span>
                                <span v-else class="text-gray-500 text-sm rounded-md bg-gray-200 px-3 py-1">{{ __('draft') }}</span>
                            </td>
                            <td class="px-6 py-3 text-sm w-px whitespace-nowrap text-gray-600">
                                {{ $filters.date(item.created_at, "D MMM YYYY, H:mm") }}
                            </td>
                            <td
                                class="px-6 py-3 text-sm min-w-full whitespace-nowrap"
                                v-for="field in relationRecords.collection.fields"
                                :key="field.id"
                                v-show="
                                    field.type != 'richtext' &&
                                    field.type != 'password' &&
                                    field.type != 'media' &&
                                    field.type != 'json' &&
                                    field.type != 'relation' &&
                                    !JSON.parse(field.options).hideInContentList
                                "
                            >
                                <span v-for="meta in item.meta" :key="meta.id">
                                    <span
                                        v-if="meta.field_name == field.name"
                                        :class="{
                                            'rounded-md bg-gray-100 p-1 mr-1': JSON.parse(field.options).repeatable && meta.value !== null,
                                        }"
                                    >
                                        <span v-if="field.type == 'date'">{{ $filters.date(meta.value) }}</span>
                                        <span v-else-if="field.type == 'longtext' && meta.value !== null" :title="meta.value">
                                            {{ meta.value.substring(0, 20) }}
                                            <span v-if="meta.value.length > 20">...</span>
                                        </span>
                                        <span v-else>{{ meta.value }}</span>
                                    </span>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </template>

            <template #footer>
                <ui-button color="gray-100" hover="gray-200" @click="closeRelationModal">
                    <span class="text-gray-800">{{ __('Close') }}</span>
                </ui-button>
            </template>
        </ui-modal>
    </div>
</template>

<script>
import { __ } from '../../../translations/engine';

import { useAttrs } from "vue";
import { formatDate } from "../../../../utils/filters";

import { useAdminStore } from "../../../store";

import UiModal from "../../../../components/Modal.vue";
import UiButton from "../../../../components/Button.vue";
import UiDropdown from "../../../../components/Dropdown.vue";

export default {
    components: {
        UiModal,
        UiButton,
        UiDropdown,
    },

    props: {
        relationSelect: {
            type: Boolean,
            default: false,
        },
        collection_id: {
            type: Number,
        },
        eachProp: {
            type: Number,
            default: 15,
        },
        relation_type: {
            type: Number,
        },
    },

    data() {
        return {
            project: {},
            collection: {},
            content: {},
            totalCount: 0,
            publishedCount: 0,
            draftCount: 0,
            trashedCount: 0,
            search: "",
            columns: {},
            listOptions: {
                orderBy: "created_at",
                criteria: "ASC",
                sortByMeta: 0,
                getItems: "all",
            },
            openTextModal: false,
            textRecord: null,
            textModalFieldName: null,

            openMediaModal: false,
            mediaRecords: {},
            mediaModalFieldName: null,

            selected: [],
            selectAll: false,

            openRelationModal: false,
            relationRecords: {
                collection: {
                    fields: {},
                },
            },
            relationModalFieldName: null,

            each: null,

            form_count: 0,
        };
    },

    methods: {
        getContent(page) {
            if (typeof page === "undefined") {
                page = 1;
            }

            const store = useAdminStore();

            axios
                .get(
                    "content/" +
                        this.$route.params.project_id +
                        "/" +
                        this.collection_id +
                        "?page=" +
                        page +
                        "&search=" +
                        this.search +
                        "&orderBy=" +
                        this.listOptions.orderBy +
                        "&cr=" +
                        this.listOptions.criteria +
                        "&sbm=" +
                        this.listOptions.sortByMeta +
                        "&each=" +
                        this.each +
                        "&getItems=" +
                        this.listOptions.getItems
                )
                .then((response) => {
                    this.project = response.data.project;
                    this.collection = response.data.collection;
                    this.content = response.data.content;
                    this.form_count = response.data.forms;

                    this.totalCount = response.data.totalCount;
                    this.publishedCount = response.data.published;
                    this.draftCount = response.data.draft;
                    this.trashedCount = response.data.trashed;

                    if (this.content.data == 0) this.selectAll = false;

                    if (store.columnSettings.length == 0) {
                        this.setInitialColumns();
                        store.setColumns({
                            project_id: this.$route.params.project_id,
                            collection_id: this.collection_id,
                            columns: this.columns,
                        });
                    } else {
                        let storeColumnSettings = store.columnSettings;
                        let storeHasSettings = storeColumnSettings.some(
                            (o) => o.project_id == this.$route.params.project_id && o.collection_id == this.collection_id
                        );

                        if (!storeHasSettings) {
                            this.setInitialColumns();
                            store.setColumns({
                                project_id: this.$route.params.project_id,
                                collection_id: this.collection_id,
                                columns: this.columns,
                            });
                        } else {
                            this.columns = storeColumnSettings.find(
                                (o) => o.project_id == this.$route.params.project_id && o.collection_id == this.collection_id
                            ).columns;
                        }
                    }

                    this.selectAll = false;
                });
        },

        setInitialColumns() {
            this.columns = {
                created_at: true,
                updated_at: true,
                published_at: false,
                created_by: false,
                updated_by: false,
                published_by: false,
            };

            this.collection.fields.forEach((field) => {
                if (field.options.hideInContentList) this.columns[field.name] = false;
                else this.columns[field.name] = true;
            });
        },

        changeColumnSettings() {
            const store = useAdminStore();
            store.updateColumn({
                project_id: this.$route.params.project_id,
                collection_id: this.collection_id,
                columns: this.columns,
            });
        },

        sortBy(field, meta = 0) {
            if (this.listOptions.orderBy != field) {
                this.listOptions.criteria = "ASC";
            } else {
                if (this.listOptions.criteria == null || this.listOptions.criteria == "DESC") this.listOptions.criteria = "ASC";
                else this.listOptions.criteria = "DESC";
            }
            this.listOptions.orderBy = field;

            this.listOptions.sortByMeta = meta;
            this.getContent(1, meta);
        },

        selectRecord(id) {
            if (this.relation_type == 1) {
                if (this.selected.includes(id)) {
                    this.selected.splice(
                        this.selected.findIndex((v) => v === id),
                        1
                    );
                } else {
                    this.selected = [];
                    this.selected.push(id);
                }
            } else {
                if (this.selected.includes(id)) {
                    this.selected.splice(
                        this.selected.findIndex((v) => v === id),
                        1
                    );
                } else {
                    this.selected.push(id);
                }
            }
            this.selectAll = false;
        },

        checkIfSelected(id) {
            return this.selected.includes(id);
        },

        addSelected() {
            this.$emit("addSelected", {
                selected: this.selected,
                collection_id: this.collection_id,
            });
            this.selected = [];
        },

        selectAllFn() {
            if (!this.selectAll) {
                for (let i = 0; i < this.content.data.length; i++) {
                    if (!this.selected.includes(this.content.data[i].id)) {
                        this.selected.push(this.content.data[i].id);
                    }
                }
            } else {
                this.selected = [];
            }
        },

        closeTextModal() {
            this.openTextModal = false;
        },

        showText(field, value) {
            this.openTextModal = true;
            this.textRecord = value;
            this.textModalFieldName = field.label;
        },

        closeMediaModal() {
            this.openMediaModal = false;
        },

        async showMedia(field, files) {
            await axios.post("content/get-selected-files/" + this.$route.params.project_id, { data: files.split(",") }).then((response) => {
                this.openMediaModal = true;
                this.mediaRecords = response.data;
                this.mediaModalFieldName = field.label;
                this.$forceUpdate();
            });
        },

        closeRelationModal() {
            this.openRelationModal = false;
        },

        async showRelationlist(field, value) {
            let data = {
                selected: value.split(","),
                collection_id: field.options.relation.collection,
            };

            await axios.post("content/get-selected-records/" + this.$route.params.project_id, { data: data }).then((response) => {
                this.openRelationModal = true;
                this.relationRecords = response.data.content;
                this.relationRecords.collection = response.data.collection;
                this.relationModalFieldName = field.label;
                this.$forceUpdate();
            });
        },

        changeGetItems(status) {
            (this.listOptions.getItems = status), this.getContent();
            this.selected = [];
            this.selectAll = false;
        },

        publishSelected() {
            this.$swal
                .fire({
                    title: __('Are you sure'),
                    text: __('you want to publish all selected items?'),
                })
                .then((result) => {
                    if (result.isConfirmed) {
                        axios.post("content/publish-selected/" + this.$route.params.project_id + "/" + this.$route.params.col_id, { selected: this.selected }).then((response) => {
                            this.$toast.success(__('Selected items has been published'));
                            this.getContent();
                            this.selected = [];
                            this.selectAll = false;
                        });
                    }
                });
        },

        unPublishSelected() {
            this.$swal
                .fire({
                    title: __('Are you sure'),
                    text: __('you want to unpublish all selected items?'),
                })
                .then((result) => {
                    if (result.isConfirmed) {
                        axios.post("content/unpublish-selected/" + this.$route.params.project_id + "/" + this.$route.params.col_id, { selected: this.selected }).then((response) => {
                            this.$toast.success(__('Selected items has been unpublished'));
                            this.getContent();
                            this.selected = [];
                            this.selectAll = false;
                        });
                    }
                });
        },

        moveToTrashContent(item) {
            this.$swal
                .fire({
                    title: __('Are you sure'),
                    text: __('you want to move this item to the trash?'),
                })
                .then((result) => {
                    if (result.isConfirmed) {
                        axios.delete("content/move-to-trash/" + this.$route.params.project_id + "/" + this.$route.params.col_id + "/" + item.id).then((response) => {
                            this.$toast.success(__('Content moved to the trash.'));
                            this.getContent();
                        });
                    }
                });
        },

        moveToTrashSelected() {
            this.$swal
                .fire({
                    title: __('Are you sure'),
                    text: __('you want to move all selected items to the trash?'),
                })
                .then((result) => {
                    if (result.isConfirmed) {
                        axios.post("content/move-to-trash-selected/" + this.$route.params.project_id + "/" + this.$route.params.col_id, { selected: this.selected }).then((response) => {
                            this.$toast.success(__('Selected items has been moved to the trash'));
                            this.getContent();
                            this.selected = [];
                            this.selectAll = false;
                        });
                    }
                });
        },

        deleteSelected() {
            this.$swal
                .fire({
                    title: __('Are you sure'),
                    text: __('you want to delete all selected items permanently?'),
                })
                .then((result) => {
                    if (result.isConfirmed) {
                        axios.post("content/delete-selected/" + this.$route.params.project_id + "/" + this.$route.params.col_id, { selected: this.selected }).then((response) => {
                            this.$toast.success(__('Selected items has been deleted'));
                            this.getContent();
                            this.selected = [];
                            this.selectAll = false;
                        });
                    }
                });
        },

        restoreSelected() {
            this.$swal
                .fire({
                    title: __('Are you sure'),
                    text: __('you want to restore all selected items?'),
                })
                .then((result) => {
                    if (result.isConfirmed) {
                        axios.post("content/restore-selected/" + this.$route.params.project_id + "/" + this.$route.params.col_id, { selected: this.selected }).then((response) => {
                            this.$toast.success(__('Selected items has been restored'));
                            this.getContent();
                            this.selected = [];
                            this.selectAll = false;
                        });
                    }
                });
        },

        getUserNameInitials(name) {
            let initials = name.split(" ");

            if (initials.length > 1) {
                initials = initials.shift().charAt(0) + initials.pop().charAt(0);
            } else {
                initials = name.substring(0, 2);
            }

            return initials.toUpperCase();
        },

        dateFormat(date) {
            return formatDate(date, "D MMM YYYY, H:mm");
        },
    },

    mounted() {
        if (this.collection_id !== undefined) this.getContent();
    },

    created() {
        this.each = this.eachProp;
    },

    computed: {
        paginationInfo() {
            return __('{total} records, {from} - {to} showing', { total: this.content.total, from: this.content.from, to: this.content.to });
        },
        hasAddSelectedListener() {
            const attrs = useAttrs();
            return attrs && attrs.onAddSelected;
        },
    },

    watch: {
        collection_id(newId, oldId) {
            this.search = "";
            this.getContent();
            this.selected = [];
            this.selectAll = false;
        },
    },
};
</script>
