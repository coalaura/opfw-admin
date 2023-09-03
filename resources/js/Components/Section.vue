<template>
    <div class="p-8 mb-10 rounded-lg shadow mobile:p-4 mobile:py-2 relative" :class="bright ? 'bg-gray-300 dark:bg-gray-600 scale-105' : 'bg-secondary dark:bg-dark-secondary'">

        <!-- Header -->
        <header :class="{ 'mb-8': !collapsed }" v-if="!noHeader">
            <slot name="header" />
        </header>

        <!-- Body -->
        <div :class="!noFooter ? 'mb-8' : ''" v-if="!collapsed">
            <slot />
        </div>

        <!-- Footer -->
        <footer v-if="!noFooter && !collapsed">
            <slot name="footer" />
        </footer>

    </div>
</template>

<script>
export default {
    name: 'Section',
    props: {
        noFooter: {
            type: Boolean,
            default: false
        },
        noHeader: {
            type: Boolean,
            default: false
        },
        bright: {
            type: Boolean,
            default: false
        },
        collapsed: {
            type: Boolean,
            default: false
        }
    }
}
</script>
