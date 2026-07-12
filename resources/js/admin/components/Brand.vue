<template>
    <router-link 
        :to="{name: 'dashboard'}" 
        :exact-active-class="'bg-none'" 
        class="w-full flex justify-center py-2"
    >
        <img :src="logoUrl" alt="Logo" :class="imgclass" />
    </router-link>
</template>

<script>
    export default {
        props: [
            'imgclass',
            'size', // sm | md | lg
            'mode', // light | dark
        ],
        data() {
            return {
                appUrl: document.querySelector('meta[name="APP_URL"]').content,
            }
        },

        computed: {
            logoUrl() {
                const appUrl = document.querySelector('meta[name="APP_URL"]').content;
                const SizeMap = {
                    sm: '32x32',
                    md: '64x64',
                    lg: '256x256'
                };
                const ModeMap = {
                    light: 'light',
                    dark: 'dark'
                };
                const nameExt = `${SizeMap[this.size] || SizeMap.md}-${ModeMap[this.mode] || ModeMap.dark}`;
                return `${appUrl}/images/logo-${nameExt}.svg`;
            },
        },
    }
</script>