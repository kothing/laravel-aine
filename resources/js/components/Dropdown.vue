<template>
    <div class="relative" ref="dropdown">
        <div class="trigger" @click="open = ! open">
            <slot name="trigger"></slot>
        </div>

        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95">
            <div v-show="open"
                class="absolute z-50 rounded-md shadow-lg"
                :class="[widthClass, alignmentClasses]"
                style="display: none;"
                @click="close"
            >
                <div class="rounded-md shadow-xs border border-gray-200" :class="contentClasses">
                    <slot name="content"></slot>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
    export default {
        props: {
            align: {
                default: 'right'
            },
            width: {
                default: '48'
            },
            contentClasses: {
                default: () => ['bg-white']
            },
            closeable: {
                default: true
            }
        },

        data() {
            return {
                open: false
            }
        },

        methods: {
            close(){
                if(this.closeable)
                    this.open = !this.open
            }
        },

        created() {
            this._closeOnEscape = (e) => {
                if (this.open && e.keyCode === 27) {
                    this.open = false
                }
            }
            document.addEventListener('keydown', this._closeOnEscape)

            this._documentClick = (e) => {
                try {
                    let el = this.$refs.dropdown;
                    let target = e.target;
                    if (el !== target && !el.contains(target)) {
                        this.open = false
                    }
                } catch (error) {}
            };
            document.addEventListener("click", this._documentClick);
        },

        unmounted() {
            document.removeEventListener('keydown', this._closeOnEscape)
            document.removeEventListener("click", this._documentClick)
        },

        computed: {
            widthClass() {
                return {
                    '48': 'w-48',
                    '60': 'w-60',
                    '64': 'w-64',
                    '72': 'w-72',
                    '80': 'w-80',
                    '96': 'w-96',
                }[this.width.toString()]
            },

            alignmentClasses() {
                if (this.align == 'left') {
                    return 'origin-top-left left-0'
                } else if (this.align == 'right') {
                    return 'origin-top-right right-0'
                } else {
                    return 'origin-top'
                }
            },
        }
    }
</script>
