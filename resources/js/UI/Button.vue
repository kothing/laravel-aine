<template>
    <button :type="type" :class="bgColor" :disabled="disabled" class="items-center border border-transparent rounded-md text-sm text-white focus:outline-none transition ease-in-out duration-150">
        <slot></slot>
    </button>
</template>

<script>
    export default {
        props: {
            type: {
                type: String,
                default: 'submit',
            },
            color: {
                type: String,
                default: 'gray-800'
            },
            hover: {
                type: String,
                default: null
            },
            padding: {
                type: String,
                default: null
            },
            disabled: {
                type: Boolean,
                default: false
            }
        },
        computed: {
            bgColor(){
                let cls = '';
                if(this.disabled){
                    cls = 'bg-indigo-200 cursor-not-allowed';
                } else {
                    let bgcolor = 'bg-'+this.color;
                    let arr = this.color.split('-');
                    let hover = this.hover == null ? 'bg-'+arr[0]+'-'+(parseInt(arr[1]) + 200) : 'bg-'+this.hover;
                    let active = 'bg-'+arr[0]+'-'+(parseInt(arr[1]) + 200);
                    let focus = 'border-'+arr[0]+'-'+(parseInt(arr[1]) - 100);
                    let focusShadow = 'shadow-outline-'+arr[0];

                    cls = bgcolor+' hover:'+hover+' active:'+active;
                }

                if(this.padding === null) {
                    cls += ' p-2 ';
                } else {
                    cls += ' '+this.padding+' ';
                }
                
                return cls;
            }
        }
    }
</script>
