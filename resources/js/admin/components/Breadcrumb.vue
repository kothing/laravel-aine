<template>
    <nav class="admin__breadcrumb flex items-center text-sm text-gray-600">
        <template v-for="(item, index) in items">
            <span v-if="index > 0" :key="`sep-${index}`" class="mx-1 text-gray-400">/</span>
            <router-link
                v-if="item.url"
                :key="`link-${index}`"
                :to="item.url"
                class="px-1 hover:text-gray-800 inline-flex items-center"
            >
                <i v-if="item.icon" :class="item.icon" class="text-sm text-gray-500 mr-1"></i>
                <span>{{ item.name }}</span>
            </router-link>
            <span v-else :key="`text-${index}`" class="px-1 text-gray-800 font-medium inline-flex items-center">
                <i v-if="item.icon" :class="item.icon" class="text-sm text-gray-500 mr-1"></i>
                <span>{{ item.name }}</span>
            </span>
        </template>
    </nav>
</template>

<script>
export default {
    name: 'Breadcrumb',
    props: {
        items: { type: Array, default: () => [] },
    },
    computed: {
        // 兜底空数组：即使父组件传入 null/undefined 也不报错
        safeItems() {
            return Array.isArray(this.items) ? this.items : [];
        },
    },
}
</script>
