<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t('screenshot.anti_cheat') }}
            </h1>
            <p>
                {{ t('screenshot.anti_cheat_description') }}
            </p>
        </portal>

        <portal to="actions">
            <button class="px-4 py-2 text-sm font-semibold text-white bg-rose-600 rounded dark:bg-rose-400 mr-3" type="button" @click="showingReasons = true">
                <i class="mr-1 fas fa-info"></i>
                {{ t('screenshot.anti_cheat_reasons') }}
            </button>

            <button class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" type="button" @click="refresh">
                <i class="mr-1 fa fa-refresh"></i>
                {{ t('global.refresh') }}
            </button>
        </portal>

        <!-- Table -->
        <v-section class="overflow-x-auto" :noHeader="true">
            <template>
                <table class="w-full whitespace-no-wrap">
                    <tr class="font-semibold text-left mobile:hidden">
                        <th class="p-3 pl-8 w-56">{{ t('screenshot.player') }}</th>
                        <th class="p-3 w-40">{{ t('screenshot.screenshot') }}</th>
                        <th class="p-3">{{ t('screenshot.note') }}</th>
                        <th class="p-3 w-32">{{ t('screenshot.ban_status') }}</th>
                        <th class="p-3 pr-8 w-56">{{ t('screenshot.created_at') }}</th>
                    </tr>
                    <tr class="border-t border-gray-300 dark:border-gray-500 hover:bg-gray-200 dark:hover:bg-gray-600" :class="{ 'new-entry': screenshot.new }" v-for="screenshot in list" :key="screenshot.url">
                        <template v-if="screenshot.isBan">
                            <td class="p-3 pl-8 text-center mobile:block">
                                <inertia-link class="block px-4 py-2 font-semibold text-center text-white bg-red-600 rounded dark:bg-red-400" :href="'/players/' + screenshot.license_identifier">
                                    {{ screenshot.player_name }}
                                </inertia-link>
                            </td>
                            <td class="p-3 mobile:block italic text-gray-600 dark:text-gray-400 text-sm" colspan="3">
                                Banned indefinitely for <span class="font-semibold">{{ screenshot.reason }}</span>
                            </td>
                            <td class="p-3 pr-8 mobile:block italic text-gray-600 dark:text-gray-400 w-56">
                                {{ screenshot.timestamp * 1000 | formatTime(true) }}
                            </td>
                        </template>
                        <template v-else>
                            <td class="p-3 pl-8 mobile:block w-56">
                                <inertia-link class="block px-4 py-2 font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="'/players/' + screenshot.license_identifier">
                                    {{ screenshot.player_name }}
                                </inertia-link>
                            </td>
                            <td class="p-3 mobile:block w-40">
                                <a :href="screenshot.url" target="_blank" class="text-indigo-600 dark:text-indigo-400">{{ t('screenshot.view', screenshot.url.split(".").pop()) }}</a>
                            </td>
                            <td class="p-3 mobile:block">
                                {{ screenshot.details || 'N/A' }}
                            </td>
                            <td class="p-3 mobile:block font-semibold w-32">
                                <span class="text-red-600 dark:text-red-400" v-if="screenshot.ban">
                                    {{ t('global.banned') }}
                                </span>
                                <span class="text-green-600 dark:text-green-400 whitespace-nowrap" v-else>
                                    {{ t('global.not_banned') }}
                                </span>
                            </td>
                            <td class="p-3 mobile:block w-56 italic text-gray-600 dark:text-gray-400" v-if="screenshot.timestamp">{{ screenshot.timestamp * 1000 | formatTime(true) }}</td>
                            <td class="p-3 pr-8 mobile:block w-56 italic text-gray-600 dark:text-gray-400" v-else>{{ t('global.unknown') }}</td>
                        </template>
                    </tr>
                    <tr v-if="screenshots.length === 0" class="border-t border-gray-300 dark:border-gray-500">
                        <td class="px-8 py-3 text-center" colspan="100%">
                            {{ t('screenshot.no_screenshots') }}
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
                        <inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" v-if="screenshots.length === 20" :href="links.next">
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

        <modal :show.sync="showingReasons">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('screenshot.anti_cheat_reasons') }}
                </h1>
            </template>

            <template #default>
                <template v-for="(contents, category) in reasons">
                    <h2 class="mt-5">{{ category }}</h2>

                    <table class="w-full whitespace-no-wrap">
                        <tr class="text-left hover:bg-gray-100 dark:hover:bg-gray-600" v-for="(value, key) in contents">
                            <td class="font-semibold py-2 px-1 border-t">{{ key }}</td>
                            <td class="py-2 px-1 italic border-t text-sm">{{ value }}</td>
                        </tr>
                    </table>
                </template>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="showingReasons = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

    </div>
</template>

<script>
import Layout from './../../Layouts/App';
import VSection from './../../Components/Section';
import Pagination from './../../Components/Pagination';
import Modal from './../../Components/Modal';

export default {
    layout: Layout,
    components: {
        Pagination,
        VSection,
        Modal,
    },
    props: {
        screenshots: {
            type: Array,
            required: true,
        },
        banMap: {
            type: Object,
            required: true,
        },
        links: {
            type: Object,
            required: true,
        },
        reasons: {
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
            isLoading: false,

            showingReasons: false,

            previousIds: false
        };
    },
    computed: {
        list() {
            return this.screenshots.map(screenshot => {
                screenshot.ban = this.getBanInfo(screenshot.license_identifier);
                screenshot.isBan = !screenshot.url.startsWith("http");

                screenshot.new = this.previousIds && !this.previousIds.includes(screenshot.id);

                return screenshot;
            });
        }
    },
    methods: {
        refresh: async function () {
            if (this.isLoading) {
                return;
            }

            this.previousIds = this.screenshots.map(screenshot => screenshot.id);

            this.isLoading = true;
            try {
                await this.$inertia.replace('/anti_cheat', {
                    preserveState: true,
                    preserveScroll: true,
                    only: ['screenshots', 'banMap', 'links', 'page'],
                });
            } catch (e) { }

            this.isLoading = false;
        },
        getBanInfo(licenseIdentifier, key) {
            const ban = licenseIdentifier in this.banMap ? this.banMap[licenseIdentifier] : null;

            if (key) {
                return ban && key in ban ? ban[key] : null;
            }

            return ban;
        }
    }
};
</script>
