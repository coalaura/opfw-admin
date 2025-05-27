<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t('casino.logs') }}
            </h1>
            <p>
                {{ t('casino.description') }}
            </p>
        </portal>

        <portal to="actions">
            <button class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" type="button" @click="refresh">
                <span v-if="!isLoading">
                    <i class="fa fa-redo-alt mr-1"></i>
                    {{ t('global.refresh') }}
                </span>
                <span v-else>
                    <i class="fas fa-spinner animate-spin mr-1"></i>
                    {{ t('global.loading') }}
                </span>
            </button>
        </portal>

        <!-- Querying -->
        <v-section :noFooter="true">
            <template #header>
                <h2>
                    {{ t('casino.filter') }}
                </h2>
            </template>

            <template>
                <form @submit.prevent>
                    <div class="flex flex-wrap mb-4">
                        <!-- Identifier -->
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2" for="identifier">
                                {{ t('casino.identifier') }}
                            </label>
                            <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="identifier" placeholder="license:2ced2cabd90f1208e7e056485d4704c7e1284196" v-model="filters.identifier">
                        </div>
                        <!-- Character ID -->
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2" for="character">
                                {{ t('casino.character_id') }}
                            </label>
                            <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="character" placeholder="12345" v-model="filters.character">
                        </div>
                        <!-- Game -->
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2">
                                {{ t('casino.game') }}
                            </label>
                            <select class="w-full px-4 py-3 bg-gray-200 dark:bg-gray-600 border rounded" id="game" v-model="filters.game">
                                <option value="">{{ t('global.all') }}</option>
                                <option value="blackjack">{{ t('casino.games.blackjack') }}</option>
                                <option value="slots">{{ t('casino.games.slots') }}</option>
                                <option value="tracks">{{ t('casino.games.tracks') }}</option>
                            </select>
                        </div>
                        <!-- Game -->
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2">
                                {{ t('casino.result') }}
                            </label>
                            <select class="w-full px-4 py-3 bg-gray-200 dark:bg-gray-600 border rounded" id="result" v-model="filters.result">
                                <option value="">{{ t('global.all') }}</option>
                                <option value="win">{{ t('casino.results.win') }}</option>
                                <option value="loss">{{ t('casino.results.loss') }}</option>
                                <option value="draw">{{ t('casino.results.draw') }}</option>
                            </select>
                        </div>
                    </div>
                    <!-- Search button -->
                    <div class="w-full px-3 mt-3">
                        <button class="px-5 py-2 font-semibold text-white bg-success dark:bg-dark-success rounded hover:shadow-lg" @click="refresh">
                            <span v-if="!isLoading">
                                <i class="fas fa-search"></i>
                                {{ t('casino.search') }}
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
                    {{ t('casino.logs') }}
                </h2>
                <p class="text-muted dark:text-dark-muted text-xs">
                    {{ t('global.results', time) }}
                </p>
            </template>

            <template>
                <table class="w-full">
                    <tr class="font-semibold text-left mobile:hidden">
                        <th class="p-3 pl-8">{{ t('casino.player') }}</th>
                        <th class="p-3">{{ t('casino.character_id') }}</th>
                        <th class="p-3">{{ t('casino.game') }}</th>
                        <th class="p-3">{{ t('casino.bet') }}</th>
                        <th class="p-3">{{ t('casino.money_return') }}</th>
                        <th class="p-3">{{ t('casino.details') }}</th>
                        <th class="p-3 pr-8">{{ t('casino.timestamp') }}</th>
                    </tr>
                    <tr class="border-t border-gray-300 dark:border-gray-500 hover:bg-gray-200 dark:hover:bg-gray-600" v-for="(log, index) in logs" :key="log.id">
                        <td class="p-3 pl-8 mobile:block">
                            <inertia-link class="block px-4 py-2 font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="'/players/' + log.license_identifier">
                                {{ playerName(log.license_identifier) }}
                            </inertia-link>
                        </td>
                        <td class="p-3 mobile:block">
                            {{ log.character_id }}
                        </td>
                        <td class="p-3 mobile:block">{{ log.game }}</td>
                        <td class="p-3 mobile:block">{{ numberFormat(log.bet_placed, 0, true) }}</td>
                        <td class="p-3 mobile:block">{{ numberFormat(log.money_won, 0, true) }}</td>
                        <td class="p-3 mobile:block">
                            {{ log.details }}
                        </td>
                        <td class="p-3 pr-8 mobile:block whitespace-nowrap">{{ log.timestamp | formatTime(true) }}</td>
                    </tr>
                    <tr v-if="logs.length === 0" class="border-t border-gray-300 dark:border-gray-500">
                        <td class="px-8 py-3 text-center" colspan="100%">
                            {{ t('casino.no_logs') }}
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
                        <inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" v-if="logs.length === 15" :href="links.next">
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

export default {
    layout: Layout,
    components: {
        Pagination,
        VSection,
    },
    props: {
        logs: {
            type: Array,
            required: true,
        },
        filters: {
            identifier: String,
            character: Number,
            game: String,
            result: String,
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
        },
        time: {
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
        refresh: async function () {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;

            try {
                await this.$inertia.replace('/casino', {
                    data: this.filters,
                    preserveState: true,
                    preserveScroll: true,
                    only: ['logs', 'playerMap', 'time', 'links', 'page'],
                });
            } catch (e) {
            }

            this.isLoading = false;
        },
        playerName(licenseIdentifier) {
            return licenseIdentifier in this.playerMap ? this.playerMap[licenseIdentifier] : licenseIdentifier;
        }
    },
};
</script>
