<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t('players.title') }}
            </h1>
            <p>
                {{ t('players.description') }}
            </p>
        </portal>

        <!-- Search -->
        <v-section :noFooter="true">
            <template #header>
                <h2>
                    {{ t('players.search') }}
                </h2>
            </template>

            <template>
                <form @submit.prevent>
                    <div class="absolute top-2 right-2 flex gap-2">
                        <select class="block w-32 px-2 py-1 bg-gray-200 dark:bg-gray-600 border rounded text-sm" id="sort" name="sort" v-model="filters.sort" :title="t('global.sort_by')">
                            <option value="id">{{ t('global.sort.id') }}</option>
                            <option value="name">{{ t('global.sort.name') }}</option>
                            <option value="playtime">{{ t('global.sort.playtime') }}</option>
                            <option value="last">{{ t('global.sort.last') }}</option>
                        </select>

                        <select class="block w-20 px-2 py-1 bg-gray-200 dark:bg-gray-600 border rounded text-sm" id="order" name="order" v-model="filters.order" :title="t('global.sort_order')">
                            <option value="">ASC</option>
                            <option value="desc">DESC</option>
                        </select>
                    </div>

                    <div class="flex flex-wrap mb-4">
                        <div class="w-1/3 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2" for="name">
                                {{ t('players.name') }} <sup class="text-muted dark:text-dark-muted">*</sup>
                            </label>
                            <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="name" name="name" placeholder="Marius Truckster" v-model="filters.name">
                        </div>
                        <div class="w-1/3 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2" for="license">
                                {{ t('players.license') }} <sup class="text-muted dark:text-dark-muted">*</sup>
                            </label>
                            <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="license" name="license" placeholder="license:2ced2cabd90f1208e7e056485d4704c7e1284196" v-model="filters.license">
                        </div>
                        <div class="w-1/3 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2" for="server_id">
                                {{ t('players.server_id') }}
                            </label>
                            <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="server_id" name="server" type="number" min="0" max="9999" placeholder="123" v-model="filters.server">
                        </div>
                        <div class="w-1/3 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2 mt-3" for="identifier">
                                {{ t('players.identifier') }}
                                <sup v-if="detectIdentifierType(filters.identifier)">{{ detectIdentifierType(filters.identifier) }}</sup>
                            </label>
                            <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="identifier" name="identifier" placeholder="669523636423622686" v-model="filters.identifier">
                        </div>
                        <div class="w-1/3 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2 mt-3" for="streamer_exception">
                                {{ t('players.streamer_exception') }}
                            </label>
                            <select class="block w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="streamer_exception" name="streamer_exception" v-model="filters.streamer_exception">
                                <option value="">{{ t('global.any') }}</option>
                                <option :value="true">{{ t('global.yes') }}</option>
                            </select>
                        </div>
                        <div class="w-1/3 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2 mt-3" for="enablable">
                                {{ t('players.enablable') }}
                            </label>
                            <select class="block w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="enablable" name="enablable" v-model="filters.enablable">
                                <option value="">{{ t('global.all') }}</option>
                                <option :value="command" v-for="command in enablableKeys">/{{ command }}</option>
                            </select>
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
                                {{ t('players.search_btn') }}
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

        <v-section class="overflow-x-auto">
            <template #header>
                <h2>
                    {{ t('players.title') }}
                </h2>
                <p class="text-muted dark:text-dark-muted text-xs">
                    {{ t('global.results', time) }}
                </p>
            </template>

            <template>
                <table class="w-full">
                    <tr class="font-semibold text-left mobile:hidden">
                        <th class="p-3 pl-8">{{ t('global.server_id') }}</th>
                        <th class="p-3">{{ t('players.form.identifier') }}</th>
                        <th class="p-3">{{ t('players.form.name') }}</th>
                        <th class="p-3">{{ t('players.form.playtime') }}</th>
                        <th class="w-64 p-3">{{ t('players.form.banned') }}?</th>
                        <th class="w-24 p-3 pr-8"></th>
                    </tr>
                    <tr class="border-t border-gray-300 dark:border-gray-500 hover:bg-gray-200 dark:hover:bg-gray-600" v-for="player in players" v-bind:key="player.id">
                        <td class="p-3 pl-8 mobile:block">
                            <span class="font-semibold" v-if="statusLoading">
                                {{ t('global.loading') }}
                            </span>
                            <span class="font-semibold" v-else-if="status[player.licenseIdentifier]">
                                {{ status[player.licenseIdentifier].source }}
                            </span>
                            <span class="font-semibold" v-else>
                                {{ t('global.status.offline') }}
                            </span>
                        </td>
                        <td class="p-3 mobile:block">{{ player.licenseIdentifier }}</td>
                        <td class="p-3 mobile:block">
                            {{ player.playerName }}
                            <i class="fas fa-user-ninja ml-1 text-red-500 dark:text-red-400" :title="t('players.show.suspicious_spoof')" v-if="player.suspicious"></i>
                        </td>
                        <td class="p-3 mobile:block" :title="formatSeconds(player.playTime, 'YMdhm')">{{ player.playTime | humanizeSeconds }}</td>
                        <td class="p-3 text-center mobile:block">
                            <span class="block px-4 py-2 text-white rounded bg-red-500 dark:bg-red-600" v-if="player.isBanned">
                                {{ t('global.banned') }}
                            </span>
                            <span class="block px-4 py-2 text-white bg-green-500 rounded dark:bg-green-600" v-else>
                                {{ t('global.not_banned') }}
                            </span>
                        </td>
                        <td class="p-3 pr-8 mobile:block">
                            <inertia-link class="block px-4 py-2 font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" v-bind:href="'/players/' + player.licenseIdentifier">
                                <i class="fas fa-chevron-right"></i>
                            </inertia-link>
                        </td>
                    </tr>
                    <tr v-if="players.length === 0" class="border-t border-gray-300 dark:border-gray-500">
                        <td class="px-8 py-3 text-center mobile:block" colspan="100%">
                            {{ t('players.none') }}
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
                        <inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" v-if="players.length === 20" :href="links.next">
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
import Badge from './../../Components/Badge.vue';
import Pagination from './../../Components/Pagination.vue';

export default {
    layout: Layout,
    components: {
        VSection,
        Badge,
        Pagination,
    },
    props: {
        players: {
            type: Array,
            required: true,
        },
        filters: {
            sort: String,
            order: String,

            name: String,
            license: String,
            discord: String,
            server: Number,
            identifier: String,
            streamer_exception: String,
            enablable: String,
        },
        time: {
            type: Number,
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
        enablable: {
            type: Array,
            required: true,
        },
    },
    data() {
        return {
            isLoading: false,

            statusLoading: false,
            status: {}
        };
    },
    mounted() {
        this.updateStatus();

        this.filters.streamer_exception = this.filters.streamer_exception ?? "";
    },
    watch: {
        players() {
            this.updateStatus();
        },
    },
    computed: {
        enablableKeys() {
            return Object.keys(this.enablable).toSorted();
        },
    },
    methods: {
        refresh: async function () {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;

            try {
                await this.$inertia.replace('/players', {
                    data: this.filters,
                    preserveState: true,
                    preserveScroll: true,
                    only: ['players', 'time', 'links', 'page', 'filters'],
                });
            } catch (e) { }

            this.isLoading = false;
        },
        async updateStatus() {
            if (this.statusLoading) return;

            this.statusLoading = true;

            const identifiers = this.players.map(player => player.licenseIdentifier).join(',')

            if (identifiers) {
                this.status = await this.requestData(`/online/${identifiers}`);
            } else {
                this.status = {};
            }

            this.statusLoading = false;
        }
    }
}
</script>
