<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                <i class="mr-3 fas fa-unlock-alt" :title="perm.restriction(perm.PERM_ADVANCED)"></i>

                {{ t('search_logs.title') }}
            </h1>
            <p>
                {{ t('search_logs.description') }}
            </p>
        </portal>

        <!-- Querying -->
        <v-section :noFooter="true">
            <template #header>
                <h2>
                    {{ t('logs.filter') }}
                </h2>
            </template>

            <template>
                <form @submit.prevent>
                    <div class="flex flex-wrap mb-4">
                        <!-- Identifier -->
                        <div class="w-1/2 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2" for="identifier">
                                {{ t('logs.identifier') }} <sup class="text-muted dark:text-dark-muted">*</sup>
                            </label>
                            <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="identifier" placeholder="license:2ced2cabd90f1208e7e056485d4704c7e1284196" v-model="filters.identifier" :title="previewQuery(filters.identifier)">
                        </div>
                        <!-- Details -->
                        <div class="w-1/2 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2" for="details">
                                {{ t('search_logs.searches') }} <sup class="text-muted dark:text-dark-muted">*</sup>
                            </label>
                            <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="details" placeholder="Oxy runs kekw" v-model="filters.details" :title="previewQuery(filters.details)">
                        </div>
                        <!-- After Date -->
                        <div class="w-1/4 px-3 pr-1 mobile:w-full mobile:mb-3">
                            <label class="block mb-3 mt-3" for="after-date">
                                {{ t('logs.after-date') }}
                            </label>
                            <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="after-date" type="date" placeholder="">
                        </div>
                        <!-- After Time -->
                        <div class="w-1/4 px-3 pl-1 mobile:w-full mobile:mb-3">
                            <label class="block mb-3 mt-3" for="after-time">
                                {{ t('logs.after-time') }}
                            </label>
                            <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="after-time" type="time" placeholder="">
                        </div>
                        <!-- Before Date -->
                        <div class="w-1/4 px-3 pr-1 mobile:w-full mobile:mb-3">
                            <label class="block mb-3 mt-3" for="before-date">
                                {{ t('logs.before-date') }}
                            </label>
                            <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="before-date" type="date" placeholder="">
                        </div>
                        <!-- Before Time -->
                        <div class="w-1/4 px-3 pl-1 mobile:w-full mobile:mb-3">
                            <label class="block mb-3 mt-3" for="before-time">
                                {{ t('logs.before-time') }}
                            </label>
                            <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="before-time" type="time" placeholder="">
                        </div>
                    </div>
                    <!-- Description -->
                    <div class="w-full px-3 mt-3">
                        <small class="text-muted dark:text-dark-muted mt-1 leading-4 block" v-html="t('global.search.custom')"></small>
                    </div>
                    <!-- Search button -->
                    <div class="w-full px-3 mt-3">
                        <button class="px-5 py-2 font-semibold text-white bg-success dark:bg-dark-success rounded hover:shadow-lg" @click="refresh">
                            <span v-if="!isLoading">
                                <i class="fas fa-search"></i>
                                {{ t('logs.search') }}
                            </span>
                            <span v-else>
                                <i class="fas fa-cog animate-spin"></i>
                                {{ t('global.loading') }}
                            </span>
                        </button>
                    </div>
                </form>
            </template>
        </v-section>

        <!-- Table -->
        <v-section class="overflow-x-auto">
            <template #header>
                <h2>
                    {{ t('logs.logs') }}
                </h2>
            </template>

            <template>
                <table class="w-full">
                    <tr class="font-semibold text-left mobile:hidden">
                        <th class="p-3 pl-8">{{ t('logs.player') }}</th>
                        <th class="p-3">{{ t('logs.action') }}</th>
                        <th class="p-3">{{ t('logs.details') }}</th>
                        <th class="p-3">{{ t('logs.identifier') }}</th>
                        <th class="p-3">{{ t('logs.server_id') }}</th>
                        <th class="p-3">{{ t('logs.page') }}</th>
                        <th class="p-3 pr-8">
                            {{ t('logs.timestamp') }}
                        </th>
                    </tr>
                    <tr class="border-t border-gray-300 dark:border-gray-500 hover:bg-gray-200 dark:hover:bg-gray-600" v-for="(log, index) in logs" :key="log.id">
                        <td class="p-3 pl-8 mobile:block">
                            <inertia-link class="block px-4 py-2 font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="'/players/' + log.license_identifier">
                                {{ playerName(log.license_identifier) }}
                            </inertia-link>
                        </td>
                        <td class="p-3 mobile:block" :class="{ 'text-muted dark:text-dark-muted': !log.action }">
                            {{ log.action || '-' }}
                        </td>
                        <td class="p-3 mobile:block" :class="{ 'text-muted dark:text-dark-muted': !log.details }">
                            {{ log.details || '-' }}
                        </td>
                        <td class="p-3 mobile:block" :class="{ 'text-muted dark:text-dark-muted': !log.identifier }">
                            {{ log.identifier || '-' }}
                        </td>
                        <td class="p-3 mobile:block" :class="{ 'text-muted dark:text-dark-muted': !log.server }">
                            {{ log.server || '-' }}
                        </td>
                        <td class="p-3 mobile:block" :class="{ 'text-muted dark:text-dark-muted': !log.page }">
                            {{ log.page || '-' }}
                        </td>
                        <td class="p-3 pr-8 mobile:block whitespace-nowrap">{{ log.timestamp * 1000 | formatTime(true) }}</td>
                    </tr>
                    <tr v-if="logs.length === 0" class="border-t border-gray-300 dark:border-gray-500">
                        <td class="py-3 px-8 text-center" colspan="100%">
                            {{ t('logs.no_logs') }}
                        </td>
                    </tr>
                </table>
            </template>

            <template #footer>
                <div class="flex items-center justify-between mt-6 mb-1">

                    <!-- Navigation -->
                    <div class="flex flex-wrap">
                        <inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="links.prev" v-if="page >= 2">
                            <i class="mr-1 fas fa-arrow-left"></i>
                            {{ t("pagination.previous") }}
                        </inertia-link>
                        <inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" v-if="logs.length === 20" :href="links.next">
                            {{ t("pagination.next") }}
                            <i class="ml-1 fas fa-arrow-right"></i>
                        </inertia-link>
                    </div>

                    <!-- Meta -->
                    <div class="font-semibold">
                        {{ t("pagination.page", page) }}
                    </div>

                </div>
            </template>
        </v-section>

    </div>
</template>

<script>
import Layout from './../../Layouts/App.vue';
import VSection from './../../Components/Section.vue';
import Pagination from './../../Components/Pagination.vue';
import Modal from './../../Components/Modal.vue';

export default {
    layout: Layout,
    components: {
        Pagination,
        Modal,
        VSection
    },
    props: {
        logs: {
            type: Array,
            required: true,
        },
        filters: {
            identifier: String,
            details: String,
            before: Number,
            after: Number,
        },
        playerMap: {
            type: Object,
            required: true,
        },
        links: {
            type: Object,
            required: true,
        },
        page: {
            type: Number,
            required: true,
        }
    },
    data() {
        return {
            isLoading: false
        };
    },
    methods: {
        stamp(time) {
            return dayjs.utc(time).unix();
        },
        refresh: async function () {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;
            try {
                const beforeDate = $('#before-date').val();
                const beforeTime = $('#before-time').val();
                const afterDate = $('#after-date').val();
                const afterTime = $('#after-time').val();

                if (beforeDate && beforeTime) {
                    this.filters.before = Math.round((new Date(`${beforeDate} ${beforeTime}`)).getTime() / 1000);

                    if (Number.isNaN(this.filters.before)) {
                        this.filters.before = null;
                    }
                }

                if (afterDate && afterTime) {
                    this.filters.after = Math.round((new Date(`${afterDate} ${afterTime}`)).getTime() / 1000);

                    if (Number.isNaN(this.filters.after)) {
                        this.filters.after = null;
                    }
                }

                await this.$inertia.replace('/searches', {
                    data: this.filters,
                    preserveState: true,
                    preserveScroll: true,
                    only: ['logs', 'playerMap', 'links', 'page'],
                });
            } catch (e) {
            }

            this.isLoading = false;
        },
        playerName(licenseIdentifier) {
            return licenseIdentifier in this.playerMap ? this.playerMap[licenseIdentifier] : licenseIdentifier;
        }
    },
    mounted() {
        if (this.filters.before) {
            const d = new Date(this.filters.before * 1000);

            $('#before-date').val(`${d.getFullYear()}-${(`${d.getMonth() + 1}`).padStart(2, '0')}-${(`${d.getDate()}`).padStart(2, '0')}`);
            $('#before-time').val(`${d.getHours()}:${d.getMinutes()}`);
        }
        if (this.filters.after) {
            const d = new Date(this.filters.after * 1000);

            $('#after-date').val(`${d.getFullYear()}-${(`${d.getMonth() + 1}`).padStart(2, '0')}-${(`${d.getDate()}`).padStart(2, '0')}`);
            $('#after-time').val(`${d.getHours()}:${d.getMinutes()}`);
        }
    }
};
</script>
