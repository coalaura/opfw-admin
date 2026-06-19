<template>
    <span
        class="inline-flex items-center justify-center w-14 px-2 py-1 text-xs font-semibold text-white rounded cursor-help"
        :class="bgClass"
        :title="titleText"
    >
        {{ labelText }}
    </span>
</template>

<script>
export default {
    name: 'StatusTag',
    props: {
        status: {
            type: Object,
            default: null,
        },
        loading: {
            type: Boolean,
            default: false,
        },
    },
    computed: {
        isOnline() {
            return !this.loading && this.status && !!this.status.source;
        },
        isCharSelected() {
            return this.isOnline && this.status.character !== null && this.status.character !== undefined && this.status.character !== '';
        },
        labelText() {
            if (this.loading) {
                return '...';
            }
            if (this.isOnline) {
                return this.status.source;
            }
            return this.t('global.status.offline');
        },
        bgClass() {
            if (this.loading) {
                return 'bg-gray-500';
            }
            if (!this.isOnline) {
                return 'bg-gray-600 dark:bg-gray-500';
            }
            if (this.isCharSelected) {
                return 'bg-green-600 dark:bg-green-500';
            }
            return 'bg-yellow-600 dark:bg-yellow-500';
        },
        titleText() {
            if (this.loading) {
                return this.t('global.loading');
            }
            if (!this.isOnline) {
                return this.t('global.status.offline');
            }
            if (this.isCharSelected) {
                const charId = this.status.character;
                return `Online - Server ID: ${this.status.source} - Active Character ID: ${charId}`;
            }
            return `Online - Server ID: ${this.status.source} - In Character Selection / AFK`;
        },
    },
};
</script>
