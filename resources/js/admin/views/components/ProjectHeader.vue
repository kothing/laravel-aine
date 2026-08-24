<template>
    <div class="admin__project-header w-full border-b bg-white an__sticky top-0 z-1">
        <div class="px-4 py-2">
            <div class="text-xl font-bold">
                {{ __('Project:') }} {{ project.name }}
            </div>
            <div class="text-sm">{{ project.description || "Project description" }}</div>
        </div>
    </div>
</template>

<script>

export default {
    props: ["project"],
};
</script>
