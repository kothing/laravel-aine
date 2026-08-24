<template>
    <div class="admin__project-nav p-4 bg-white">
        <h4 class="mb-2 p-2 font-bold text-lg border-b border-gray-100">{{ __('Settings') }}</h4>
        <ul>
            <li class="mb-2">
                <router-link
                    v-if="typeof project.id !== 'undefined'"
                    :to="{
                        name: 'projects.settings',
                        params: { project_id: project.id },
                    }"
                    :exact-active-class="'bg-blue-100 text-blue-700 font-semibold'"
                    class="block w-full p-2 cursor-pointer hover:bg-gray-100 rounded"
                >
                    {{ __('Project') }}
                </router-link>
            </li>
            <li class="mb-2">
                <router-link
                    v-if="typeof project.id !== 'undefined' && checkRole(['super_admin'])"
                    :to="{
                        name: 'projects.settings.users',
                        params: { project_id: project.id },
                    }"
                    :active-class="'bg-blue-50 text-blue-700'"
                    :exact-active-class="'bg-blue-100 text-blue-700 font-semibold'"
                    class="block w-full p-2 cursor-pointer hover:bg-gray-100 rounded"
                >
                    {{ __('Users & Roles') }}
                </router-link>
            </li>
            <li class="mb-2">
                <router-link
                    v-if="typeof project.id !== 'undefined' && checkRole(['super_admin'])"
                    :to="{
                        name: 'projects.settings.api',
                        params: { project_id: project.id },
                    }"
                    :active-class="'bg-blue-50 text-blue-700'"
                    :exact-active-class="'bg-blue-100 text-blue-700 font-semibold'"
                    class="block w-full p-2 cursor-pointer hover:bg-gray-100 rounded"
                >
                    {{ __('API Access') }}
                </router-link>
            </li>
            <li class="mb-2">
                <router-link
                    v-if="typeof project.id !== 'undefined' && checkRole(['super_admin'])"
                    :to="{
                        name: 'projects.settings.webhooks',
                        params: { project_id: project.id }
                    }"
                    :active-class="'bg-blue-50 text-blue-700'"
                    :exact-active-class="'bg-blue-100 text-blue-700 font-semibold'"
                    class="block w-full p-2 cursor-pointer hover:bg-gray-100 rounded"
                >
                    {{ __('Webhooks') }}
                </router-link>
            </li>
            <li class="mb-2">
                <router-link
                    v-if="typeof project.id !== 'undefined' && checkRole(['super_admin'])"
                    :to="{
                        name: 'projects.settings.language',
                        params: { project_id: project.id },
                    }"
                    :active-class="'bg-blue-100 text-blue-700 font-semibold'"
                    class="block w-full p-2 cursor-pointer hover:bg-gray-100 rounded"
                >
                    {{ __('Language') }}
                </router-link>
            </li>
        </ul>
    </div>
</template>

<script>
import checkRole from "../../../../utils/checkrole";

export default {
  props: ["project"],

  methods: {
    checkRole,
  },
};
</script>
