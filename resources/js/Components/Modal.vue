<template>
    <portal to="modals" v-if="show">
        <!-- Backdrop -->
        <div class="fixed inset-0 flex items-start justify-center overflow-y-auto px-3 modal sm:px-4" style="z-index: 9998; background-color: rgba(0, 0, 0, .85);" tabindex="-1" role="dialog" @mousedown.self="hide">
            <!-- Slot inside raw mode -->
            <slot v-if="raw" />

            <!-- Container -->
            <div v-else :class="className" class="max-w-3xl my-3 max-h-[calc(100%-1.5rem)] relative flex flex-col bg-white rounded-md shadow dark:bg-dark-secondary dark:text-white sm:my-20 sm:max-h-modal-max mobile:w-full" role="document" v-bind="$attrs">
                <!-- Content part -->
                <div class="flex flex-1 min-h-0 flex-col overflow-hidden px-4 py-3 sm:px-10 sm:py-4">

                    <!-- Header -->
                    <header v-if="$slots.header" class="max-w-full prose text-center pt-2 mb-4 !block flex-shrink-0 sm:pt-4 sm:mb-6">
                        <slot name="header" />
                    </header>

                    <!-- Main -->
                    <main class="min-h-0 overflow-y-auto">
                        <slot />
                    </main>

                </div>

                <!-- Actions -->
                <footer v-if="$slots.actions" class="flex flex-shrink-0 flex-wrap items-center justify-end px-4 py-3 gap-3 sm:px-10 sm:py-4">
                    <slot name="actions" />
                </footer>
            </div>
        </div>
    </portal>
</template>

<script>
export default {
    name: 'Modal',
    inheritAttrs: false,
    props: {
        show: Boolean,
        small: Boolean,
        extraClass: String,
        raw: Boolean,
    },
    computed: {
        className() {
            return [
                this.extraClass,
                !this.small && "w-full"
            ].filter(Boolean).join(" ");
        }
    },
    methods: {
        /**
         * Hides the modal.
         */
        hide() {
            this.$emit('update:show', false);
        },

        handleKeypress(event) {
            if (event.key !== "Escape") {
                return;
            }

            this.hide();
        }
    },
    created() {
        window.addEventListener("keydown", this.handleKeypress);
    },
    destroyed() {
        window.removeEventListener("keydown", this.handleKeypress);
    },
}
</script>
