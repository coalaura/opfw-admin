<template>
    <portal to="modals" v-if="show">

        <!-- Backdrop -->
        <div class="absolute inset-0 flex items-start justify-center overflow-hidden" style="z-index: 9999; background-color: rgba(0, 0, 0, .85);" tabindex="-1" role="dialog" @mousedown.self="hide">

            <!-- Container -->
            <div :class="{ 'w-full': !small }" class="max-w-3xl my-20 max-h-modal-max flex flex-col bg-white rounded-md shadow dark:bg-dark-secondary dark:text-white" role="document" v-bind="$attrs">

                <!-- Content part -->
                <div class="px-10 py-4 flex flex-col overflow-hidden">

                    <!-- Header -->
                    <header class="max-w-full prose text-center pt-4 mb-6">
                        <slot name="header" />
                    </header>

                    <!-- Main -->
                    <main class="overflow-y-auto">
                        <slot />
                    </main>

                </div>

                <!-- Actions -->
                <footer class="flex items-center justify-end px-10 py-4 space-x-3">
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
    },
    methods: {
        /**
         * Hides the modal.
         */
        hide() {
            this.$emit('update:show', false);
        }
    },
}
</script>
