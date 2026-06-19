<template>
    <div>
        <portal to="title">
            <h1 class="dark:text-white">
                <i class="mr-3 fab fa-twitch" :title="perm.restriction(perm.PERM_BAN_EXCEPTION)"></i>
                {{ t('ban_exceptions.title') }}
            </h1>
            <p>
                {{ t('ban_exceptions.description') }}
            </p>
        </portal>

        <v-section :noFooter="true">
            <template #header>
                <h2>
                    {{ t('ban_exceptions.search') }}
                </h2>
            </template>

            <template>
                <form @submit.prevent="refresh">
                    <div class="flex flex-wrap items-end mb-4">
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-4 font-semibold" for="license">
                                {{ t('ban_exceptions.license_filter') }}
                            </label>
                            <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="license" name="license" :placeholder="t('ban_exceptions.license_placeholder')" v-model="form.license" />
                        </div>

                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-4 font-semibold" for="name">
                                {{ t('ban_exceptions.name_filter') }}
                            </label>
                            <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="name" name="name" :placeholder="t('ban_exceptions.name_placeholder')" v-model="form.name" />
                        </div>

                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-4 font-semibold" for="twitch">
                                {{ t('ban_exceptions.twitch_filter') }}
                            </label>
                            <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="twitch" name="twitch" :placeholder="t('ban_exceptions.twitch_placeholder')" v-model="form.twitch" />
                        </div>

                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-4 font-semibold invisible">
                                {{ t('ban_exceptions.search_btn') }}
                            </label>
                            <button class="w-full px-5 py-2 font-semibold text-white bg-success dark:bg-dark-success rounded hover:shadow-lg" type="submit">
                                <span v-if="!isLoading">
                                    <i class="fas fa-search"></i>
                                    {{ t('ban_exceptions.search_btn') }}
                                </span>
                                <span v-else>
                                    <i class="fas fa-cog animate-spin"></i>
                                    {{ t('global.loading') }}
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </template>
        </v-section>

        <v-section class="overflow-x-auto">
            <template #header>
                <h2>
                    {{ t('global.result') }}
                </h2>
                <p class="text-muted dark:text-dark-muted text-xs">
                    {{ t('ban_exceptions.results', numberFormat(total, 0, false)) }}
                </p>
            </template>

            <template>
                <table class="w-full">
                    <tr class="font-semibold text-left mobile:hidden">
                        <th class="px-6 py-4">{{ t('ban_exceptions.player') }}</th>
                        <th class="px-6 py-4">{{ t('ban_exceptions.twitch') }}</th>
                        <th class="px-6 py-4">{{ t('ban_exceptions.current_ban') }}</th>
                        <th class="px-6 py-4">{{ t('ban_exceptions.creator') }}</th>
                        <th class="w-24 px-6 py-4"></th>
                    </tr>

                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-600 mobile:border-b-4" v-for="exception in exceptions" :key="exception.licenseIdentifier">
                        <td class="px-6 py-3 border-t mobile:block">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-14 px-2 py-1 text-xs font-semibold text-white rounded" :class="statusClass(exception.licenseIdentifier)">
                                    {{ statusLabel(exception.licenseIdentifier) }}
                                </span>

                                <inertia-link class="font-semibold text-indigo-700 dark:text-indigo-300 hover:underline" :href="'/players/' + exception.licenseIdentifier">
                                    {{ exception.playerName }}
                                </inertia-link>
                            </div>
                        </td>

                        <td class="px-6 py-3 border-t mobile:block">
                            <a class="inline-flex items-center font-semibold text-purple-700 dark:text-purple-300 hover:underline" :href="'https://twitch.tv/' + exception.twitch" target="_blank" rel="noopener noreferrer">
                                <img class="w-8 h-8 rounded-full mr-2 border border-purple-200 dark:border-purple-500 object-cover" :src="'/twitch/' + exception.twitch" :alt="exception.twitch" />
                                {{ exception.twitch }}
                            </a>
                        </td>

                        <td class="px-6 py-3 border-t mobile:block">
                            <span class="font-mono text-xs" :title="exception.currentBanReason" v-if="exception.currentBanReason">
                                {{ exception.currentBanReason }}
                            </span>
                            <span class="text-muted dark:text-dark-muted italic" v-else>
                                {{ t('ban_exceptions.no_active_ban') }}
                            </span>
                        </td>

                        <td class="px-6 py-3 border-t mobile:block">
                            {{ exception.currentBanCreator || t('global.system') }}
                        </td>

                        <td class="px-6 py-3 border-t mobile:block">
                            <inertia-link class="block px-4 py-2 font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="'/players/' + exception.licenseIdentifier">
                                <i class="fas fa-chevron-right"></i>
                            </inertia-link>
                        </td>
                    </tr>

                    <tr v-if="exceptions.length === 0">
                        <td class="px-6 py-6 text-center border-t mobile:block" colspan="100%">
                            {{ t('ban_exceptions.none') }}
                        </td>
                    </tr>
                </table>
            </template>

            <template #footer>
                <div class="flex items-center justify-between mt-6 mb-1">
                    <div class="flex flex-wrap">
                        <inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="links.prev" v-if="page >= 2">
                            <i class="mr-1 fas fa-arrow-left"></i>
                            {{ t('pagination.previous') }}
                        </inertia-link>
                        <inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="links.next" v-if="hasMore">
                            {{ t('pagination.next') }}
                            <i class="ml-1 fas fa-arrow-right"></i>
                        </inertia-link>
                    </div>

                    <div class="font-semibold">
                        {{ t('pagination.page', page) }}
                    </div>
                </div>
            </template>
        </v-section>
    </div>
</template>

<script>
import Layout from './../../Layouts/App.vue';
import VSection from './../../Components/Section.vue';

export default {
    layout: Layout,
    components: {
        VSection,
    },
    props: {
        exceptions: {
            type: Array,
            required: true,
        },
        links: {
            type: Object,
            required: true,
        },
        page: {
            type: Number,
            required: true,
        },
        hasMore: {
            type: Boolean,
            required: true,
        },
        total: {
            type: Number,
            required: true,
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            isLoading: false,
            statusLoading: false,
            status: {},
            form: {
                license: this.filters.license || '',
                name: this.filters.name || '',
                twitch: this.filters.twitch || '',
            },
        };
    },
    mounted() {
        this.updateStatus();
    },
    watch: {
        exceptions() {
            this.updateStatus();
        },
    },
    methods: {
        statusLabel(license) {
            if (this.statusLoading) {
                return '...';
            }

            if (this.status[license]) {
                return this.status[license].source;
            }

            return this.t('global.status.offline');
        },
        statusClass(license) {
            if (this.statusLoading) {
                return 'bg-gray-500';
            }

            return this.status[license] ? 'bg-green-600 dark:bg-green-500' : 'bg-gray-600 dark:bg-gray-500';
        },
        async updateStatus() {
            if (this.statusLoading) {
                return;
            }

            this.statusLoading = true;

            const identifiers = this.exceptions.map(exception => exception.licenseIdentifier).join(',');

            if (identifiers) {
                this.status = await this.requestData(`/online/${identifiers}`) || {};
            } else {
                this.status = {};
            }

            this.statusLoading = false;
        },
        async refresh() {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;

            try {
                await this.$inertia.replace('/ban_exceptions', {
                    data: {
                        license: this.form.license || null,
                        name: this.form.name || null,
                        twitch: this.form.twitch || null,
                    },
                    preserveState: true,
                    preserveScroll: true,
                    only: ['exceptions', 'links', 'page', 'hasMore', 'total', 'filters'],
                });
            } catch (e) { }

            this.isLoading = false;
        },
    },
};
</script>
