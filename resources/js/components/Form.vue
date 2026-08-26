<template>
    <div class="p-5 w-full m-auto">
        <div class="flex-1 p-4" v-if="!submitSuccess">
            <h3 class="text-lg font-bold mb-2">{{ form.name }}</h3>
            <h5>{{ form.description }}</h5>

            <form class="space-y-6 mt-5 w-full">
                <div
                    class="bg-gray-50 p-2 w-full relative"
                    v-for="field in form.fields"
                    :key="field.id"
                >
                    <label v-formlabel>
                        {{ field.label }}
                    </label>
                    <div class="mt-1 relative">
                        <div v-if="field.type == 'text'">
                            <div v-if="field.options.repeatable">
                                <div
                                    v-for="(input, index) in newData.data[
                                        field.name
                                    ]"
                                    :key="index"
                                >
                                    <div class="flex space-between">
                                        <div
                                            class="relative flex w-full flex-wrap items-stretch"
                                        >
                                            <input
                                                type="text"
                                                v-model="input.value"
                                                :placeholder="field.placeholder"
                                                class="mb-1"
                                                v-forminput
                                            />
                                        </div>
                                        <div
                                            class="w-auto h-auto text-right"
                                            v-if="index !== 0"
                                        >
                                            <div
                                                class="cursor-pointer text-sm border border-red-500 rounded-md text-white bg-red-500 p-3 ml-2 text-center hover:bg-red-400"
                                                @click="
                                                    removeLineFromRepeatableField(
                                                        field,
                                                        index
                                                    )
                                                "
                                            >
                                                <i class="fas fa-trash-alt"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p
                                        class="text-sm text-red-600 mt-1 mb-1"
                                        v-if="
                                            newData.errors[
                                                'data.' +
                                                    field.name +
                                                    '.' +
                                                    index +
                                                    '.value'
                                            ]
                                        "
                                    >
                                        {{
                                            newData.errors[
                                                "data." +
                                                    field.name +
                                                    "." +
                                                    index +
                                                    ".value"
                                            ][0]
                                        }}
                                    </p>
                                </div>
                                <div
                                    class="cursor-pointer text-xs rounded-md text-white bg-indigo-500 p-1 w-32 text-center mt-1 hover:bg-indigo-400"
                                    @click="addNewLineToRepeatableField(field)"
                                >
                                    + Add a new line
                                </div>
                            </div>
                            <div v-else>
                                <input
                                    v-if="field.slug_field === undefined"
                                    type="text"
                                    v-model="newData.data[field.name]"
                                    :placeholder="field.placeholder"
                                    v-forminput
                                />
                                <input
                                    v-else
                                    type="text"
                                    v-model="newData.data[field.name]"
                                    :placeholder="field.placeholder"
                                    v-forminput
                                    @input="
                                        newData.data[field.slug_field] =
                                            $slugify(newData.data[field.name])
                                    "
                                />
                            </div>
                        </div>
                        <div v-if="field.type == 'longtext'">
                            <div v-if="field.options.repeatable">
                                <div
                                    v-for="(input, index) in newData.data[
                                        field.name
                                    ]"
                                    :key="index"
                                >
                                    <div class="flex space-between">
                                        <div
                                            class="relative flex w-full flex-wrap items-stretch"
                                        >
                                            <textarea
                                                v-model="input.value"
                                                :placeholder="field.placeholder"
                                                class="mb-1"
                                                v-forminput
                                            ></textarea>
                                        </div>
                                        <div
                                            class="w-auto h-auto text-right"
                                            v-if="index !== 0"
                                        >
                                            <div
                                                class="cursor-pointer text-sm border border-red-500 rounded-md text-white bg-red-500 p-3 ml-2 text-center hover:bg-red-400"
                                                @click="
                                                    removeLineFromRepeatableField(
                                                        field,
                                                        index
                                                    )
                                                "
                                            >
                                                <i class="fas fa-trash-alt"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p
                                        class="text-sm text-red-600 mt-1 mb-1"
                                        v-if="
                                            newData.errors[
                                                'data.' +
                                                    field.name +
                                                    '.' +
                                                    index +
                                                    '.value'
                                            ]
                                        "
                                    >
                                        {{
                                            newData.errors[
                                                "data." +
                                                    field.name +
                                                    "." +
                                                    index +
                                                    ".value"
                                            ][0]
                                        }}
                                    </p>
                                </div>
                                <div
                                    class="cursor-pointer text-xs rounded-md text-white bg-indigo-500 p-1 w-32 text-center mt-1 hover:bg-indigo-400"
                                    @click="addNewLineToRepeatableField(field)"
                                >
                                    + Add a new line
                                </div>
                            </div>
                            <div v-else>
                                <textarea
                                    v-model="newData.data[field.name]"
                                    :placeholder="field.placeholder"
                                    v-forminput
                                ></textarea>
                            </div>
                        </div>
                        <div
                            v-if="field.type == 'richtext'"
                            class="w-full relative"
                        >
                            <tiny-editor
                                :modelValue="newData.data[field.name]"
                                :placeholder="field.placeholder"
                                height="120px"
                                @update:modelValue="newData.data[field.name] = $event"
                            />
                        </div>
                        <div v-if="field.type == 'email'">
                            <div v-if="field.options.repeatable">
                                <div
                                    v-for="(input, index) in newData.data[
                                        field.name
                                    ]"
                                    :key="index"
                                >
                                    <div class="flex space-between">
                                        <div
                                            class="relative flex w-full flex-wrap items-stretch mb-1"
                                        >
                                            <div
                                                class="flex rounded-md shadow-sm w-full"
                                            >
                                                <span
                                                    class="inline-flex items-center px-3 rounded-l-sm border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"
                                                    ><i class="fa fa-at"></i
                                                ></span>
                                                <input
                                                    type="email"
                                                    v-model="input.value"
                                                    :placeholder="field.placeholder"
                                                    v-forminput
                                                    class="rounded-l-none"
                                                />
                                            </div>
                                        </div>
                                        <div
                                            class="w-auto h-auto text-right"
                                            v-if="index !== 0"
                                        >
                                            <div
                                                class="cursor-pointer text-sm border border-red-500 rounded-md text-white bg-red-500 p-3 ml-2 text-center hover:bg-red-400"
                                                @click="
                                                    removeLineFromRepeatableField(
                                                        field,
                                                        index
                                                    )
                                                "
                                            >
                                                <i class="fas fa-trash-alt"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p
                                        class="text-sm text-red-600 mt-1 mb-1"
                                        v-if="
                                            newData.errors[
                                                'data.' +
                                                    field.name +
                                                    '.' +
                                                    index +
                                                    '.value'
                                            ]
                                        "
                                    >
                                        {{
                                            newData.errors[
                                                "data." +
                                                    field.name +
                                                    "." +
                                                    index +
                                                    ".value"
                                            ][0]
                                        }}
                                    </p>
                                </div>
                                <div
                                    class="cursor-pointer text-xs rounded-md text-white bg-indigo-500 p-1 w-32 text-center mt-1 hover:bg-indigo-400"
                                    @click="addNewLineToRepeatableField(field)"
                                >
                                    + Add a new line
                                </div>
                            </div>
                            <div v-else>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <span
                                        class="inline-flex items-center px-3 rounded-l-sm border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"
                                        ><i class="fa fa-at"></i
                                    ></span>
                                    <input
                                        type="email"
                                        v-model="newData.data[field.name]"
                                        :placeholder="field.placeholder"
                                        v-forminput
                                        class="rounded-l-none"
                                    />
                                </div>
                            </div>
                        </div>
                        <div v-if="field.type == 'password'">
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <span
                                    class="inline-flex items-center px-3 rounded-l-sm border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"
                                    ><i class="fa fa-lock"></i
                                ></span>
                                <input
                                    type="password"
                                    v-model="newData.data[field.name]"
                                    v-forminput
                                    placeholder="Password"
                                    class="rounded-l-none"
                                />
                            </div>
                        </div>
                        <div v-if="field.type == 'number'">
                            <div v-if="field.options.repeatable">
                                <div
                                    v-for="(input, index) in newData.data[
                                        field.name
                                    ]"
                                    :key="index"
                                >
                                    <div class="flex space-between">
                                        <div
                                            class="relative flex w-full flex-wrap items-stretch mb-1"
                                        >
                                            <input
                                                type="number"
                                                step="any"
                                                v-model="input.value"
                                                v-forminput
                                            />
                                        </div>
                                        <div
                                            class="w-auto h-auto text-right"
                                            v-if="index !== 0"
                                        >
                                            <div
                                                class="cursor-pointer text-sm border border-red-500 rounded-md text-white bg-red-500 p-3 ml-2 text-center hover:bg-red-400"
                                                @click="
                                                    removeLineFromRepeatableField(
                                                        field,
                                                        index
                                                    )
                                                "
                                            >
                                                <i class="fas fa-trash-alt"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p
                                        class="text-sm text-red-600 mt-1 mb-1"
                                        v-if="
                                            newData.errors[
                                                'data.' +
                                                    field.name +
                                                    '.' +
                                                    index +
                                                    '.value'
                                            ]
                                        "
                                    >
                                        {{
                                            newData.errors[
                                                "data." +
                                                    field.name +
                                                    "." +
                                                    index +
                                                    ".value"
                                            ][0]
                                        }}
                                    </p>
                                </div>
                                <div
                                    class="cursor-pointer text-xs rounded-md text-white bg-indigo-500 p-1 w-32 text-center mt-1 hover:bg-indigo-400"
                                    @click="addNewLineToRepeatableField(field)"
                                >
                                    + Add a new line
                                </div>
                            </div>
                            <div v-else>
                                <input
                                    type="number"
                                    step="any"
                                    v-model="newData.data[field.name]"
                                    v-forminput
                                />
                            </div>
                        </div>
                        <div
                            class="w-full xl:w-1/4"
                            v-if="field.type == 'enumeration'"
                        >
                            <v-select
                                :multiple="field.options.multiple"
                                :options="field.options.enumeration"
                                :selectable="
                                    (selected) =>
                                        newData.data[field.name] !== undefined
                                            ? !newData.data[
                                                  field.name
                                              ].includes(selected)
                                            : []
                                "
                                class="v-select"
                                placeholder="Select"
                                v-model="newData.data[field.name]"
                            ></v-select>
                        </div>
                        <div v-if="field.type == 'boolean'">
                            <label
                                :for="'toggle-' + field.name"
                                class="flex w-1 items-center cursor-pointer"
                            >
                                <div class="relative">
                                    <input
                                        type="checkbox"
                                        :id="'toggle-' + field.name"
                                        class="sr-only"
                                        v-model="newData.data[field.name]"
                                    />
                                    <div
                                        class="block bg-gray-600 w-14 h-8 rounded-full"
                                    ></div>
                                    <div
                                        class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition"
                                    ></div>
                                </div>
                            </label>
                        </div>
                        <div
                            v-if="field.type == 'color'"
                            class="w-2/3 xl:w-1/4"
                        >
                            <div v-if="field.options.repeatable">
                                <div
                                    v-for="(input, index) in newData.data[
                                        field.name
                                    ]"
                                    :key="index"
                                >
                                    <div class="flex space-between">
                                        <div
                                            class="relative flex w-full flex-wrap items-stretch mb-1"
                                        >
                                            <colorpicker
                                                v-model="input.value"
                                            />
                                        </div>
                                        <div
                                            class="w-auto h-auto text-right"
                                            v-if="index !== 0"
                                        >
                                            <div
                                                class="cursor-pointer text-sm border border-red-500 rounded-md text-white bg-red-500 px-3 py-3 pb-2 ml-2 text-center hover:bg-red-400"
                                                @click="
                                                    removeLineFromRepeatableField(
                                                        field,
                                                        index
                                                    )
                                                "
                                            >
                                                <i class="fas fa-trash-alt"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p
                                        class="text-sm text-red-600 mt-1 mb-1"
                                        v-if="
                                            newData.errors[
                                                'data.' +
                                                    field.name +
                                                    '.' +
                                                    index +
                                                    '.value'
                                            ]
                                        "
                                    >
                                        {{
                                            newData.errors[
                                                "data." +
                                                    field.name +
                                                    "." +
                                                    index +
                                                    ".value"
                                            ][0]
                                        }}
                                    </p>
                                </div>
                                <div
                                    class="cursor-pointer text-xs rounded-md text-white bg-indigo-500 p-1 w-32 text-center mt-1 hover:bg-indigo-400"
                                    @click="addNewLineToRepeatableField(field)"
                                >
                                    + Add a new line
                                </div>
                            </div>
                            <div v-else>
                                <colorpicker
                                    v-model="newData.data[field.name]"
                                />
                            </div>
                        </div>
                        <div
                            v-if="field.type == 'date'"
                            class="w-full xl:w-1/4"
                        >
                            <div v-if="field.options.repeatable">
                                <div
                                    v-for="(input, index) in newData.data[
                                        field.name
                                    ]"
                                    :key="index"
                                >
                                    <div class="flex space-between">
                                        <div
                                            class="relative flex w-full flex-wrap items-stretch mb-1"
                                        >
                                            <v-date-picker
                                                v-model="input.value"
                                                :mode="
                                                    field.options.timepicker
                                                        ? 'dateTime'
                                                        : 'date'
                                                "
                                            >
                                                <template
                                                    v-slot="{
                                                        inputValue,
                                                        togglePopover,
                                                    }"
                                                >
                                                    <div
                                                        class="mt-1 flex rounded-md shadow-sm"
                                                    >
                                                        <span
                                                            class="inline-flex items-center px-3 rounded-l-sm border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm cursor-pointer"
                                                            @click="
                                                                togglePopover
                                                            "
                                                            ><i
                                                                class="fa fa-calendar-alt"
                                                            ></i
                                                        ></span>
                                                        <input
                                                            type="text"
                                                            v-forminput
                                                            :value="inputValue"
                                                            @click="togglePopover"
                                                        />
                                                    </div>
                                                </template>
                                            </v-date-picker>
                                        </div>
                                        <div
                                            class="w-auto h-auto text-right"
                                            v-if="index !== 0"
                                        >
                                            <div
                                                class="cursor-pointer text-sm border border-red-500 rounded-md text-white bg-red-500 p-3 ml-2 text-center hover:bg-red-400"
                                                @click="
                                                    removeLineFromRepeatableField(
                                                        field,
                                                        index
                                                    )
                                                "
                                            >
                                                <i class="fas fa-trash-alt"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p
                                        class="text-sm text-red-600 mt-1 mb-1"
                                        v-if="
                                            newData.errors[
                                                'data.' +
                                                    field.name +
                                                    '.' +
                                                    index +
                                                    '.value'
                                            ]
                                        "
                                    >
                                        {{
                                            newData.errors[
                                                "data." +
                                                    field.name +
                                                    "." +
                                                    index +
                                                    ".value"
                                            ][0]
                                        }}
                                    </p>
                                </div>
                                <div
                                    class="cursor-pointer text-xs rounded-md text-white bg-indigo-500 p-1 w-32 text-center mt-1 hover:bg-indigo-400"
                                    @click="addNewLineToRepeatableField(field)"
                                >
                                    + Add a new line
                                </div>
                            </div>
                            <div v-else>
                                <v-date-picker
                                    v-model="newData.data[field.name]"
                                    :mode="
                                        field.options.timepicker
                                            ? 'dateTime'
                                            : 'date'
                                    "
                                >
                                    <template
                                        v-slot="{ inputValue, togglePopover }"
                                    >
                                        <div
                                            class="mt-1 flex rounded-md shadow-sm"
                                        >
                                            <span
                                                class="inline-flex items-center px-3 rounded-l-sm border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm cursor-pointer"
                                                @click="togglePopover"
                                                ><i
                                                    class="fa fa-calendar-alt"
                                                ></i
                                            ></span>
                                            <input
                                                type="text"
                                                v-forminput
                                                :value="inputValue"
                                                @click="togglePopover"
                                            />
                                        </div>
                                    </template>
                                </v-date-picker>
                            </div>
                        </div>
                        <div
                            v-if="field.type == 'time'"
                            class="w-1/2 xl:w-1/12"
                        >
                            <div v-if="field.options.repeatable">
                                <div
                                    v-for="(input, index) in newData.data[
                                        field.name
                                    ]"
                                    :key="index"
                                >
                                    <div class="flex space-between">
                                        <div
                                            class="relative flex w-full flex-wrap items-stretch mb-1"
                                        >
                                            <input
                                                type="time"
                                                v-model="input.value"
                                                v-forminput
                                            />
                                        </div>
                                        <div
                                            class="w-auto h-auto text-right"
                                            v-if="index !== 0"
                                        >
                                            <div
                                                class="cursor-pointer text-sm border border-red-500 rounded-md text-white bg-red-500 p-3 ml-2 text-center hover:bg-red-400"
                                                @click="
                                                    removeLineFromRepeatableField(
                                                        field,
                                                        index
                                                    )
                                                "
                                            >
                                                <i class="fas fa-trash-alt"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p
                                        class="text-sm text-red-600 mt-1 mb-1"
                                        v-if="
                                            newData.errors[
                                                'data.' +
                                                    field.name +
                                                    '.' +
                                                    index +
                                                    '.value'
                                            ]
                                        "
                                    >
                                        {{
                                            newData.errors[
                                                "data." +
                                                    field.name +
                                                    "." +
                                                    index +
                                                    ".value"
                                            ][0]
                                        }}
                                    </p>
                                </div>
                                <div
                                    class="cursor-pointer text-xs rounded-md text-white bg-indigo-500 p-1 w-32 text-center mt-1 hover:bg-indigo-400"
                                    @click="addNewLineToRepeatableField(field)"
                                >
                                    + Add a new line
                                </div>
                            </div>
                            <div v-else>
                                <input
                                    type="time"
                                    v-model="newData.data[field.name]"
                                    v-forminput
                                />
                            </div>
                        </div>
                        <div v-if="field.type == 'media'" class="w-full">
                            <div
                                v-show="!processing"
                                @click="$refs['fileInput_' + field.name].click()"
                                @dragover.prevent
                                @drop.prevent="handleFileDrop($event, field.name, field.options.media.type)"
                                class="w-full border-2 border-gray-300 rounded-md border-dashed hover:border-indigo-300 group cursor-pointer"
                            >
                                <div class="mt-1 px-6 pt-5 pb-6 text-sm text-gray-400 group-hover:text-indigo-500">
                                    <svg class="mx-auto h-12 w-12" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <span class="cursor-pointer" v-if="field.options.media.type == '1'">Select a file or drag and drop</span>
                                    <span class="cursor-pointer" v-else-if="field.options.media.type == '2'">Select files or drag and drop</span>
                                    <p class="text-xs text-gray-500" v-if="upload_max_filesize !== null">
                                        jpeg, jpg, png, gif, webp
                                        <span v-if="field.options.media.type == '2'">| max: 4 files</span>
                                        | Up to {{ $filters.prettyBytes(upload_max_filesize, 1024) }}
                                    </p>
                                </div>
                            </div>
                            <input
                                type="file"
                                :ref="'fileInput_' + field.name"
                                class="hidden"
                                accept="image/png,image/gif,image/jpeg,image/jpg"
                                :multiple="field.options.media.type == '2'"
                                @change="handleFormFileSelect($event, field.name, field.options.media.type)"
                            />

                            <div
                                v-if="
                                    files[field.name] !== undefined &&
                                    files[field.name].length != 0
                                "
                                class="grid grid-cols-4 gap-4 mt-2"
                            >
                                <div
                                    v-for="file in files[field.name]"
                                    :key="file.id"
                                    class="relative"
                                >
                                    <img
                                        class="w-full h-32 object-cover"
                                        v-if="file.thumb"
                                        :src="file.thumb"
                                    />
                                    <div
                                        class="w-full h-32 object-cover border border-gray-200 flex items-center text-center"
                                        v-if="!file.thumb"
                                    >
                                        <div class="w-full">
                                            <i
                                                v-if="
                                                    file.type ==
                                                    'application/pdf'
                                                "
                                                class="far fa-file-pdf text-5xl text-red-500"
                                            ></i>
                                            <i
                                                v-else-if="
                                                    file.type == 'video/avi' ||
                                                    file.type == 'video/mp4' ||
                                                    file.type ==
                                                        'video/quicktime' ||
                                                    file.type == 'video/webm'
                                                "
                                                class="far fa-file-video text-5xl text-blue-500"
                                            ></i>
                                            <i
                                                v-else-if="
                                                    file.type == 'audio/wav' ||
                                                    file.type == 'audio/ogg' ||
                                                    file.type == 'audio/mpeg'
                                                "
                                                class="far fa-file-audio text-5xl text-yellow-500"
                                            ></i>
                                            <i
                                                v-else-if="
                                                    file.type ==
                                                        'application/vnd.ms-excel' ||
                                                    file.type ==
                                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                                                "
                                                class="far fa-file-excel text-5xl text-green-500"
                                            ></i>
                                            <i
                                                v-else-if="
                                                    file.type ==
                                                        'application/msword' ||
                                                    file.type ==
                                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                                                "
                                                class="far fa-file-word text-5xl text-blue-500"
                                            ></i>
                                            <i
                                                v-else-if="
                                                    file.type ==
                                                    'application/zip'
                                                "
                                                class="far fa-file-archive text-5xl text-yellow-300"
                                            ></i>
                                            <i
                                                v-else
                                                class="far fa-file text-5xl text-gray-400"
                                            ></i>
                                        </div>
                                    </div>

                                    <div class="w-full mt-1">
                                        <div class="text-xs float-left" :title="file.name">
                                            {{ $filters.truncate(file.name, 12) }}
                                            <div v-if="file.size !== null">
                                                {{ $filters.prettyBytes(file.size) }}
                                            </div>
                                        </div>
                                        <div class="text-sm float-right">
                                            <i
                                                v-if="!file.success"
                                                class="fa fa-times-circle text-red-400 cursor-pointer hover:text-red-600"
                                                @click.prevent="
                                                    removeFile(file, field.name)
                                                "
                                            ></i>
                                        </div>
                                    </div>
                                    <div
                                        class="absolute inset-0 bg-white bg-opacity-90 w-full h-32 p-4 flex items-center text-center border border-gray-200"
                                        v-if="
                                            file.active ||
                                            file.error ||
                                            file.success
                                        "
                                    >
                                        <div class="relative pt-1 w-full">
                                            <div
                                                class="flex mb-2 items-center justify-between"
                                            >
                                                <div>
                                                    <span
                                                        class="text-xs font-semibold inline-block py-1 rounded-md"
                                                        :class="{
                                                            'text-green-500':
                                                                file.success,
                                                            'text-red-500':
                                                                file.error,
                                                            'text-blue-500':
                                                                file.active,
                                                        }"
                                                    >
                                                        <span
                                                            v-if="file.error"
                                                            >{{
                                                                file.error
                                                            }}</span
                                                        >
                                                        <span
                                                            v-else-if="
                                                                file.success
                                                            "
                                                            >done</span
                                                        >
                                                        <span
                                                            v-else-if="
                                                                file.active
                                                            "
                                                            >uploading</span
                                                        >
                                                    </span>
                                                </div>
                                                <div class="text-right">
                                                    <span
                                                        class="text-xs font-semibold inline-block"
                                                        :class="{
                                                            'text-green-500':
                                                                file.success,
                                                            'text-red-500':
                                                                file.error,
                                                            'text-blue-500':
                                                                file.active,
                                                        }"
                                                    >
                                                        {{ file.progress }}%
                                                    </span>
                                                </div>
                                            </div>
                                            <div
                                                class="overflow-hidden h-2 mb-4 text-xs flex rounded"
                                                :class="{
                                                    'bg-green-200':
                                                        file.success,
                                                    'bg-red-200': file.error,
                                                    'bg-blue-200': file.active,
                                                }"
                                            >
                                                <div
                                                    :style="{
                                                        width:
                                                            file.progress + '%',
                                                    }"
                                                    class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center"
                                                    :class="{
                                                        'bg-green-500':
                                                            file.success,
                                                        'bg-red-500':
                                                            file.error,
                                                        'bg-blue-500':
                                                            file.active,
                                                    }"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="clear-both"></div>

                        <small class="text-gray-500">
                            {{ field.description }}
                        </small>
                    </div>
                    <p
                        class="text-sm text-red-600 mt-1"
                        v-if="newData.errors['data.' + field.name]"
                    >
                        {{ newData.errors["data." + field.name][0] }}
                    </p>
                </div>
            </form>

            <!-- Honeypot field: hidden from humans, bots tend to fill it in -->
            <input
                type="text"
                name="website"
                v-model="honeypot"
                class="hidden"
                tabindex="-1"
                autocomplete="off"
                aria-hidden="true"
            />

            <div
                v-if="turnstileSiteKey"
                id="cf-turnstile"
                class="cf-turnstile mt-5"
            ></div>

            <div>
                <ui-button
                    color="indigo-500"
                    class="mt-5 w-32"
                    @click="submitForm"
                    :disabled="processing"
                >
                    <i v-if="processing" class="fas fa-spinner fa-spin"></i>
                    {{ form.submit_btn_text }}
                </ui-button>

                <span
                    class="text-sm text-red-600 mt-1 ml-2"
                    v-if="Object.keys(newData.errors).length !== 0"
                >
                    <span v-if="Object.keys(newData.errors).length === 1">
                        {{ Object.keys(newData.errors).length }} field have
                        invalid value, please correct it before saving.
                    </span>
                    <span v-else>
                        {{ Object.keys(newData.errors).length }} fields have
                        invalid values, please correct them before submitting.
                    </span>
                </span>
            </div>
        </div>
        <div v-else class="border p-4">
            Your form has been submitted successfully.<br />
            Thank you for submitting the form!
        </div>
    </div>
</template>

<script>
import TinyEditor from "./TinyEditor.vue";

import UiButton from "./Button.vue";

export default {
    props: ["uuid"],

    components: {
        UiButton,
        TinyEditor,
    },

    data() {
        return {
            form: {},
            newData: {
                data: {},
                errors: {},
            },
            processing: false,
            submitSuccess: false,

            files: {},
            upload_max_filesize: null,

            // Bot protection
            honeypot: '',
            turnstileSiteKey: null,
            turnstileToken: null,
            turnstileLoaded: false,
        };
    },

    methods: {
        getForm() {
            axios.post("/forms/" + this.uuid).then((response) => {
                this.form = response.data.form;
                this.form.fields = JSON.parse(this.form.fields) || [];
                this.upload_max_filesize = response.data.upload_max_filesize;
                this.turnstileSiteKey = response.data.turnstile_site_key || null;

                (this.form.fields || []).forEach((field) => {
                    if (field.options.repeatable) {
                        this.newData.data[field.name] = [
                            {
                                value: null,
                            },
                        ];
                    }
                });

                if (this.turnstileSiteKey) {
                    this.$nextTick(() => this.loadTurnstile());
                }
            });
        },

        loadTurnstile() {
            if (window.turnstile) {
                this.renderTurnstile();
                return;
            }
            if (this.turnstileLoaded) return;
            this.turnstileLoaded = true;

            const script = document.createElement("script");
            script.src =
                "https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit";
            script.async = true;
            script.defer = true;
            script.onload = () => this.renderTurnstile();
            document.head.appendChild(script);
        },

        renderTurnstile() {
            if (!window.turnstile || !this.turnstileSiteKey) return;
            const element = document.getElementById("cf-turnstile");
            if (!element) return;

            window.turnstile.render(element, {
                sitekey: this.turnstileSiteKey,
                callback: (token) => {
                    this.turnstileToken = token;
                },
                "expired-callback": () => {
                    this.turnstileToken = null;
                },
            });
        },

        addNewLineToRepeatableField(field) {
            this.newData.data[field.name].push({ value: null });
        },
        removeLineFromRepeatableField(field, index) {
            this.newData.data[field.name].splice(index, 1);
        },

        handleFormFileSelect(event, fieldName, mediaType) {
            const fileList = event.target.files;
            if (!fileList || fileList.length === 0) return;
            const maxFiles = mediaType == '2' ? 4 : 1;
            const filesToAdd = Array.from(fileList).slice(0, maxFiles);

            if (!this.files[fieldName]) {
                this.files[fieldName] = [];
            }

            filesToAdd.forEach(file => {
                const fileObj = {
                    id: Math.random().toString(36).substr(2, 9),
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    file: file,
                    thumb: null,
                    active: false,
                    success: false,
                    error: null,
                    progress: 0,
                    response: null,
                };

                if (file.type.substr(0, 6) === 'image/') {
                    let URL = window.URL || window.webkitURL;
                    fileObj.thumb = URL.createObjectURL(file);
                }

                this.files[fieldName].push(fileObj);
            });

            // Start uploading
            this.uploadFiles(fieldName);
            event.target.value = '';
        },

        handleFileDrop(event, fieldName, mediaType) {
            const fileList = event.dataTransfer.files;
            if (!fileList || fileList.length === 0) return;
            const maxFiles = mediaType == '2' ? 4 : 1;
            const filesToAdd = Array.from(fileList).slice(0, maxFiles);

            if (!this.files[fieldName]) {
                this.files[fieldName] = [];
            }

            filesToAdd.forEach(file => {
                const fileObj = {
                    id: Math.random().toString(36).substr(2, 9),
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    file: file,
                    thumb: null,
                    active: false,
                    success: false,
                    error: null,
                    progress: 0,
                    response: null,
                };

                if (file.type.substr(0, 6) === 'image/') {
                    let URL = window.URL || window.webkitURL;
                    fileObj.thumb = URL.createObjectURL(file);
                }

                this.files[fieldName].push(fileObj);
            });

            this.uploadFiles(fieldName);
        },

        async uploadFiles(fieldName) {
            const filesToUpload = this.files[fieldName] || [];
            for (const fileObj of filesToUpload) {
                if (fileObj.response) continue; // already uploaded
                fileObj.active = true;
                try {
                    const formData = new FormData();
                    formData.append('file', fileObj.file);
                    const response = await axios.post('/forms/' + this.uuid + '/upload', formData, {
                        headers: { 'Content-Type': 'multipart/form-data' },
                        onUploadProgress: (e) => {
                            fileObj.progress = Math.round((e.loaded * 100) / e.total);
                        },
                    });
                    fileObj.response = response.data;
                    fileObj.success = true;
                    fileObj.active = false;
                } catch (error) {
                    fileObj.error = error.response ? error.response.data.message || 'Upload failed' : 'Upload failed';
                    fileObj.active = false;
                }
            }
        },

        removeFile(file, field_name) {
            this.files[field_name] = this.files[field_name].filter(
                (f) => f !== file
            );
        },

        submitForm() {
            this.processing = true;

            // Check if any files are still uploading
            let hasActiveUploads = false;
            (this.form.fields || []).forEach((field) => {
                if (field.type === 'media' && this.files[field.name]) {
                    this.files[field.name].forEach((f) => {
                        if (f.active) hasActiveUploads = true;
                    });
                }
            });

            if (hasActiveUploads) {
                // Wait and retry
                setTimeout(() => this.submitForm(), 1000);
                return;
            }

            // Require a Turnstile token when the challenge is enabled
            if (this.turnstileSiteKey && !this.turnstileToken) {
                this.newData.errors = {
                    ...this.newData.errors,
                    turnstile: [
                        "Please complete the security check before submitting.",
                    ],
                };
                this.processing = false;
                return;
            }

            // Collect uploaded file IDs
            let uploadedFiles = [];
            (this.form.fields || []).forEach((field) => {
                if (field.type === 'media') {
                    if (this.files[field.name]) {
                        this.files[field.name].forEach((file) => {
                            if (file.response) {
                                uploadedFiles.push(file.response.id);
                            }
                        });
                        this.newData.data[field.name] = uploadedFiles;
                        uploadedFiles = [];
                    }
                }
            });

            axios
                .post('/forms/submit/' + this.form.uuid, {
                    ...this.newData,
                    website: this.honeypot || '',
                    'cf-turnstile-response': this.turnstileToken,
                })
                .then((response) => {
                    this.newData = {
                        data: {},
                        errors: {},
                    };
                    this.submitSuccess = true;
                    this.processing = false;
                })
                .catch((error) => {
                    this.processing = false;
                    if (error.response.status == 422) {
                        this.newData.errors = error.response.data.errors;
                    }
                });
        },
    },

    mounted() {
        this.getForm();
    },
};
</script>
