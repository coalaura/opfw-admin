<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t("statistics.title") }}
            </h1>
            <p>
                {{ t("statistics.description") }}
            </p>
        </portal>

        <template>
            <div class="bg-gray-100 p-6 rounded shadow-lg max-w-full dark:bg-gray-600 relative mb-4">
                <input class="block w-full px-4 py-2 bg-gray-200 border rounded dark:bg-gray-600" v-model="search" type="text" placeholder="Casino Rev..." :title="t('statistics.search')" />
            </div>

            <div class="w-full border-t-2 border-dashed border-gray-500 my-6 hide-double"></div>

            <div class="bg-gray-100 p-6 rounded shadow-lg max-w-full dark:bg-gray-600 relative mb-4" v-if="shouldShowTable(t('statistics.economy_stats'))">
                <div class="flex">
                    <h2 class="text-lg flex gap-2" @click="loadEconomyStatistics()" :class="{ 'cursor-pointer': !economyLoading && !economy }">
                        {{ t('statistics.economy_stats') }}
                    </h2>
                </div>

                <p class="text-sm italic mb-3">
                    {{ t('statistics.economy_stats_details') }}

                    <span v-if="economy && economy.data.length" v-html="overallEconomyMovement()"></span>
                </p>

                <button @click="loadEconomyStatistics()" class="icon-button text-white bg-green-600" v-if="!economyLoading && !economy">
                    <i class="fas fa-plus"></i>
                </button>

                <div class="flex gap-6 overflow-y-auto">
                    <div v-if="!economyTableShow" class="bg-gray-300 dark:bg-gray-700 no-alpha border-2 border-gray-500 py-1.5 px-2">
                        <i class="fas fa-expand-alt cursor-pointer" @click="toggleEconomyTable()"></i>
                    </div>
                    <div class="max-h-statistics-long inline-block pr-2 flex-shrink-0 w-max" v-else>
                        <table class="whitespace-nowrap">
                            <tr class="sticky top-0 bg-gray-300 dark:bg-gray-700 no-alpha">
                                <th class="font-semibold px-2 py-0.5 text-left">
                                    <div class="flex gap-3 justify-between items-center">
                                        {{ t('statistics.date') }}

                                        <i class="fas fa-compress-alt cursor-pointer" @click="toggleEconomyTable()" v-if="!economyLoading && economy"></i>
                                    </div>
                                </th>
                                <th class="font-semibold px-2 py-0.5 text-left" :style="datasetColor(economy, 0)">{{ t('statistics.cash') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left" :style="datasetColor(economy, 1)">{{ t('statistics.bank') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left" :style="datasetColor(economy, 2)">{{ t('statistics.stocks') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left" :style="datasetColor(economy, 3)">{{ t('statistics.savings') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left" :style="datasetColor(economy, 4)">{{ t('statistics.shared') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left" :style="datasetColor(economy, 5)">{{ t('statistics.bonds') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left" :style="datasetColor(economy, 6)">{{ t('statistics.total') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left">&nbsp;</th>
                                <th class="font-semibold px-2 py-0.5 text-left" :style="datasetColor(economy, 7)">{{ t('statistics.richest') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left" :style="datasetColor(economy, 8)">{{ t('statistics.poorest') }}</th>
                            </tr>

                            <tr class="border-t border-gray-500" v-if="!economyLoading && !economy">
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                            </tr>

                            <tr class="border-t border-gray-500" v-else-if="economyLoading">
                                <td class="px-2 py-0.5 text-center" colspan="11">
                                    <i class="fas fa-spinner animate-spin"></i>
                                </td>
                            </tr>

                            <tr class="border-t border-gray-500" v-else-if="economy.data.length === 0">
                                <td class="px-2 py-0.5 text-center italic" colspan="11">
                                    {{ t('statistics.no_economy_recorded') }}
                                </td>
                            </tr>

                            <tr v-for="(entry, index) in economy.data" :key="index" class="border-t border-gray-500" v-else>
                                <td class="italic text-gray-700 dark:text-gray-300 px-2 py-0.5">{{ entry.date }}</td>

                                <td class="px-2 py-0.5"  :style="datasetColor(economy, 0)">{{ numberFormat(entry.cash, false, true) }}</td>
                                <td class="px-2 py-0.5"  :style="datasetColor(economy, 1)">{{ numberFormat(entry.bank, false, true) }}</td>
                                <td class="px-2 py-0.5"  :style="datasetColor(economy, 2)">{{ numberFormat(entry.stocks, false, true) }}</td>
                                <td class="px-2 py-0.5"  :style="datasetColor(economy, 3)">{{ numberFormat(entry.savings, false, true) }}</td>
                                <td class="px-2 py-0.5"  :style="datasetColor(economy, 4)">{{ numberFormat(entry.shared, false, true) }}</td>
                                <td class="px-2 py-0.5"  :style="datasetColor(economy, 5)">{{ numberFormat(entry.bonds, false, true) }}</td>
                                <td class="px-2 py-0.5"  :style="datasetColor(economy, 6)">{{ numberFormat(entry.total, false, true) }}</td>
                                <td class="px-2 py-0.5">
                                    <span class="text-red-700 dark:text-red-300" v-if="index < economy.data.length - 1 && economy.data[index + 1].total > entry.total">-{{ ((economy.data[index + 1].total - entry.total) / economy.data[index + 1].total * 100).toFixed(3) }}%</span>
                                    <span class="text-green-700 dark:text-green-300" v-else-if="index < economy.data.length - 1">+{{ ((entry.total - economy.data[index + 1].total) / economy.data[index + 1].total * 100).toFixed(3) }}%</span>
                                </td>

                                <td class="px-2 py-0.5"  :style="datasetColor(economy, 7)">{{ numberFormat(entry.richest, false, true) }}</td>
                                <td class="px-2 py-0.5"  :style="datasetColor(economy, 8)">{{ numberFormat(entry.poorest, false, true) }}</td>
                            </tr>
                        </table>
                    </div>

                    <div v-if="!economyLoading && economy && economy.graph" class="w-full max-h-statistics-long min-w-chart overflow-hidden">
                        <LineChart :chartData="economy.graph" class="h-full" :currency="true" :reRender="economyReRender"></LineChart>
                    </div>
                </div>
            </div>

            <div class="bg-gray-100 p-6 rounded shadow-lg max-w-full dark:bg-gray-600 relative mb-4" v-if="shouldShowTable(t('statistics.player_stats'))">
                <div class="flex">
                    <h2 class="text-lg flex gap-2" @click="loadPlayerStatistics()" :class="{ 'cursor-pointer': !playersLoading && !players }">
                        {{ t('statistics.player_stats') }}
                    </h2>
                </div>

                <p class="text-sm italic mb-3">
                    {{ t('statistics.player_stats_details') }}
                </p>

                <button @click="loadPlayerStatistics()" class="icon-button text-white bg-green-600" v-if="!playersLoading && !players">
                    <i class="fas fa-plus"></i>
                </button>

                <div class="flex gap-6 overflow-y-auto">
                    <div v-if="!playersTableShow" class="bg-gray-300 dark:bg-gray-700 no-alpha border-2 border-gray-500 py-1.5 px-2">
                        <i class="fas fa-expand-alt cursor-pointer" @click="togglePlayersTable()"></i>
                    </div>
                    <div class="max-h-statistics-long inline-block pr-2 flex-shrink-0 w-max" v-else>
                        <table class="whitespace-nowrap">
                            <tr class="sticky top-0 bg-gray-300 dark:bg-gray-700 no-alpha">
                                <th class="font-semibold px-2 py-0.5 text-left">
                                    <div class="flex gap-3 justify-between items-center">
                                        {{ t('statistics.date') }}

                                        <i class="fas fa-compress-alt cursor-pointer" @click="togglePlayersTable()" v-if="!playersLoading && players"></i>
                                    </div>
                                </th>
                                <th class="font-semibold px-2 py-0.5 text-left" :style="datasetColor(players, 0)">{{ t('statistics.total_joins') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left" :style="datasetColor(players, 1)">{{ t('statistics.max_users') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left" :style="datasetColor(players, 2)">{{ t('statistics.max_queue') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left" :style="datasetColor(players, 3)">{{ t('statistics.unique') }}</th>
                            </tr>

                            <tr class="border-t border-gray-500" v-if="!playersLoading && !players">
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                            </tr>

                            <tr class="border-t border-gray-500" v-else-if="playersLoading">
                                <td class="px-2 py-0.5 text-center" colspan="5">
                                    <i class="fas fa-spinner animate-spin"></i>
                                </td>
                            </tr>

                            <tr class="border-t border-gray-500" v-else-if="players.data.length === 0">
                                <td class="px-2 py-0.5 text-center italic" colspan="5">
                                    {{ t('statistics.no_players_recorded') }}
                                </td>
                            </tr>

                            <tr v-for="(entry, index) in players.data" :key="index" class="border-t border-gray-500" v-else>
                                <td class="italic text-gray-700 dark:text-gray-300 px-2 py-0.5">{{ entry.date }}</td>

                                <td class="px-2 py-0.5" :style="datasetColor(players, 0)">{{ numberFormat(entry.total_joins, false, false) }}</td>
                                <td class="px-2 py-0.5" :style="datasetColor(players, 1)">{{ numberFormat(entry.max_users, false, false) }}</td>
                                <td class="px-2 py-0.5" :style="datasetColor(players, 2)">{{ numberFormat(entry.max_queue, false, false) }}</td>
                                <td class="px-2 py-0.5" :style="datasetColor(players, 3)">{{ numberFormat(entry.unique, false, false) }}</td>
                            </tr>
                        </table>
                    </div>

                    <div v-if="!playersLoading && players && players.graph" class="w-full max-h-statistics-long overflow-hidden">
                        <LineChart :chartData="players.graph" class="h-full" :reRender="playersReRender"></LineChart>
                    </div>
                </div>
            </div>

            <div class="bg-gray-100 p-6 rounded shadow-lg max-w-full dark:bg-gray-600 relative mb-4" v-if="shouldShowTable(t('statistics.fps_stats'))">
                <div class="flex">
                    <h2 class="text-lg flex gap-2" @click="loadFPSStatistics()" :class="{ 'cursor-pointer': !fpsLoading && !fps }">
                        {{ t('statistics.fps_stats') }}
                    </h2>
                </div>

                <p class="text-sm italic mb-3">
                    {{ t('statistics.fps_stats_details') }}
                </p>

                <button @click="loadFPSStatistics()" class="icon-button text-white bg-green-600" v-if="!fpsLoading && !fps">
                    <i class="fas fa-plus"></i>
                </button>

                <div class="flex gap-6 overflow-y-auto">
                    <div v-if="!fpsTableShow" class="bg-gray-300 dark:bg-gray-700 no-alpha border-2 border-gray-500 py-1.5 px-2">
                        <i class="fas fa-expand-alt cursor-pointer" @click="toggleFpsTable()"></i>
                    </div>
                    <div class="max-h-statistics-long inline-block pr-2 flex-shrink-0 w-max" v-else>
                        <table class="whitespace-nowrap">
                            <tr class="sticky top-0 bg-gray-300 dark:bg-gray-700 no-alpha">
                                <th class="font-semibold px-2 py-0.5 text-left">
                                    <div class="flex gap-3 justify-between items-center">
                                        {{ t('statistics.date') }}

                                        <i class="fas fa-compress-alt cursor-pointer" @click="toggleFpsTable()" v-if="!fpsLoading && fps"></i>
                                    </div>
                                </th>
                                <th class="font-semibold px-2 py-0.5 text-left" :style="datasetColor(fps, 0)">{{ t('statistics.minimum_fps') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left" :style="datasetColor(fps, 1)">{{ t('statistics.maximum_fps') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left" :style="datasetColor(fps, 2)">{{ t('statistics.average_fps') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left" :style="datasetColor(fps, 3)">{{ t('statistics.average_1_percent') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left" :style="datasetColor(fps, 3)">{{ t('statistics.lag_spikes') }}</th>
                            </tr>

                            <tr class="border-t border-gray-500" v-if="!fpsLoading && !fps">
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                            </tr>

                            <tr class="border-t border-gray-500" v-else-if="fpsLoading">
                                <td class="px-2 py-0.5 text-center" colspan="6">
                                    <i class="fas fa-spinner animate-spin"></i>
                                </td>
                            </tr>

                            <tr class="border-t border-gray-500" v-else-if="fps.data.length === 0">
                                <td class="px-2 py-0.5 text-center italic" colspan="6">
                                    {{ t('statistics.no_players_recorded') }}
                                </td>
                            </tr>

                            <tr v-for="(entry, index) in fps.data" :key="index" class="border-t border-gray-500" v-else>
                                <td class="italic text-gray-700 dark:text-gray-300 px-2 py-0.5">{{ entry.date }}</td>

                                <td class="px-2 py-0.5" :style="datasetColor(fps, 0)">{{ numberFormat(entry.minimum, false, false) }}</td>
                                <td class="px-2 py-0.5" :style="datasetColor(fps, 1)">{{ numberFormat(entry.maximum, false, false) }}</td>
                                <td class="px-2 py-0.5" :style="datasetColor(fps, 2)">{{ numberFormat(entry.average, false, false) }}</td>
                                <td class="px-2 py-0.5" :style="datasetColor(fps, 3)">{{ numberFormat(entry.average_1_percent, false, false) }}</td>
                                <td class="px-2 py-0.5" :style="datasetColor(fps, 4)">{{ numberFormat(entry.lag_spikes, false, false) }}</td>
                            </tr>
                        </table>
                    </div>

                    <div v-if="!fpsLoading && fps && fps.graph" class="w-full max-h-statistics-long overflow-hidden">
                        <LineChart :chartData="fps.graph" class="h-full" :reRender="fpsReRender"></LineChart>
                    </div>
                </div>
            </div>

            <div class="bg-gray-100 p-6 rounded shadow-lg max-w-full dark:bg-gray-600 relative mb-4" v-if="shouldShowTable(t('statistics.money_logs'))">
                <div class="flex">
                    <h2 class="text-lg flex gap-2">
                        {{ t('statistics.money_logs') }}
                    </h2>
                </div>

                <p class="text-sm italic mb-3">{{ t('statistics.money_logs_details') }}</p>

                <div class="flex gap-x-3 gap-y-1 flex-wrap items-center mb-3">
                    <input class="block w-52 px-2 py-0.5 border-0 border-b border-gray-500 bg-gray-300 dark:bg-gray-700" v-model="moneyLogType" type="text" placeholder="hourly-sal..." @keyup.enter="addMoneyLogType()" />

                    <div v-for="(typ, idx) in moneyLogTypes" :key="typ" :style="moneyLogStyles[idx]" class="flex gap-2 items-center border-gray-500 bg-gray-300 dark:bg-gray-700 rounded-sm px-2 py-0.5">
                        <span>{{ typ }}</span>

                        <i class="fas fa-times cursor-pointer" @click="removeMoneyLogType(typ)"></i>
                    </div>
                </div>

                <div class="relative min-h-base" v-if="moneyLogData || moneyLogLoading">
                    <LineChart :chartData="moneyLogData" :currency="true" v-if="moneyLogData"></LineChart>

                    <div class="absolute top-0 left-0 right-0 bottom-0 backdrop-blur-md" v-if="moneyLogLoading">
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                            <i class="fas fa-spinner animate-spin text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full border-t-2 border-dashed border-gray-500 my-6 hide-double"></div>

            <StatisticsTable source="airlifts" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="atm_withdraw_fee" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="bills" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="blackjack" tag="percentage" :percentage="true" :search="search" />
            <StatisticsTable source="bus_revenue" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="casino" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="crashes_hourly" tag="amount" :amounts="['amount', 'amount2']" :locales="['timeouts_val', 'crashes_val']" :currency="false" :search="search" />
            <StatisticsTable source="crashes_daily" tag="amount" :amounts="['amount', 'amount2']" :locales="['timeouts_val', 'crashes_val']" :currency="false" :search="search" />
            <StatisticsTable source="daily_refresh" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="daily_tasks" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="deaths" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="drugs" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="dumpsters" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="edm" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="found_items" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="found_items_count" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="gem" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="guns" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="impounds" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="joins" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="ls_customs" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="lucky_wheel" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="material_vendor" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="mining_explosions" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="ooc" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="pawn" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="paycheck" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="pdm" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="reports" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="robbed_peds" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="robberies" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="scratch_tickets" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="shots_fired" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="special_imports" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="store" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="tuner" tag="money" :currency="true" :search="search" />
        </template>

    </div>
</template>

<script>
import Layout from './../../Layouts/App.vue';
import StatisticsTable from '../../Components/StatisticsTable.vue';
import LineChart from '../../Components/Charts/LineChart.vue';

export default {
    layout: Layout,
    components: {
        StatisticsTable,
        LineChart,
    },
    data() {
        return {
            isLoading: false,
            search: "",

            economyLoading: false,
            economyTableShow: true,
            economyReRender: 0,
            economy: false,

            playersLoading: false,
            playersTableShow: true,
            playersReRender: 0,
            players: false,

            fpsLoading: false,
            fpsTableShow: true,
            fpsReRender: 0,
            fps: false,

            moneyLogType: "",
            moneyLogTypes: [],
            moneyLogStyles: [],
            moneyLogAbort: false,
            moneyLogLoading: false,
            moneyLogData: false
        }
    },
    watch: {
        search() {
            const search = this.search?.trim();

            if (search) {
                localStorage.setItem('statistics_search', search);
            } else {
                localStorage.removeItem('statistics_search');
            }
        }
    },
    mounted() {
        this.search = localStorage.getItem('statistics_search') || "";
    },
    methods: {
        shouldShowTable(label) {
            if (!this.search) return true;

            const title = label.toLowerCase();
            const search = this.search.toLowerCase().trim();

            return !search || title.includes(search);
        },
        toggleEconomyTable() {
            this.economyTableShow = !this.economyTableShow;

            this.economyReRender++;
        },
        togglePlayersTable() {
            this.playersTableShow = !this.playersTableShow;

            this.playersReRender++;
        },
        toggleFpsTable() {
            this.fpsTableShow = !this.fpsTableShow;

            this.fpsReRender++;
        },
        addMoneyLogType() {
            const type = this.moneyLogType;

            if (this.moneyLogTypes.includes(type)) {
                return;
            }

            this.moneyLogTypes.push(type);
            this.moneyLogStyles.push("background: rgba(128, 128, 128, 0.3); border: 1px solid rgba(128, 128, 128, 1.0);");

            this.moneyLogType = "";

            this.loadMoneyLogs();
        },
        removeMoneyLogType(type) {
            const index = this.moneyLogTypes.indexOf(type);

            if (index > -1) {
                this.moneyLogTypes.splice(index, 1);
                this.moneyLogStyles.splice(index, 1);

                this.loadMoneyLogs();
            }
        },
        async loadMoneyLogs() {
            if (this.moneyLogAbort) {
                this.moneyLogAbort.abort();
            }

            this.moneyLogLoading = true;
            this.moneyLogAbort = new AbortController();

            try {
                const data = await _post('/statistics/money', {
                    _signal: this.moneyLogAbort.signal,

                    types: this.moneyLogTypes
                });

                if (data.status) {
                    this.moneyLogData = data.data.chart;

                    this.moneyLogTypes = data.data.types;

                    for (let i = 0; i < this.moneyLogTypes.length; i++) {
                        const dataset = this.moneyLogData.datasets[i];

                        this.moneyLogStyles[i] = `background: ${dataset.backgroundColor}; border: 1px solid ${dataset.borderColor}`;
                    }
                } else {
                    this.moneyLogData = false;
                }

                this.moneyLogLoading = false;
            } catch (e) {
                this.moneyLogData = false;

                if (e.message !== "canceled") {
                    this.moneyLogLoading = false;
                }
            }
        },
        async loadEconomyStatistics() {
            if (this.economyLoading || this.economy) return;

            this.economyLoading = true;

            try {
                const data = await _get('/statistics/economy');

                if (data.status) {
                    this.economy = data.data;
                }
            } catch (e) {
                // Signalize we failed to load the data
                this.economy = {
                    data: []
                };
            }

            this.economyLoading = false;
        },
        async loadPlayerStatistics() {
            if (this.playersLoading || this.players) return;

            this.playersLoading = true;

            try {
                const data = await _get('/statistics/players');

                if (data.status) {
                    this.players = data.data;
                }
            } catch (e) {
                // Signalize we failed to load the data
                this.players = {
                    data: []
                };
            }

            this.playersLoading = false;
        },
        async loadFPSStatistics() {
            if (this.fpsLoading || this.fps) return;

            this.fpsLoading = true;

            try {
                const data = await _get('/statistics/fps');

                if (data.status) {
                    this.fps = data.data;
                }
            } catch (e) {
                // Signalize we failed to load the data
                this.fps = {
                    data: []
                };
            }

            this.fpsLoading = false;
        },
        overallEconomyMovement() {
            if (!this.economy || !this.economy.data.length) return;

            const first = this.economy.data[this.economy.data.length - 1];
            const last = this.economy.data[0];

            if (first.total > last.total) {
                return `<span class="text-red-700 dark:text-red-300" title="Movement since ${first.date}">-${((first.total - last.total) / last.total * 100).toFixed(3)}%</span>`;
            }

            return `<span class="text-green-700 dark:text-green-300" title="Movement since ${first.date}">+${((last.total - first.total) / first.total * 100).toFixed(3)}%</span>`
        },
        datasetColor(data, index) {
            if (!data || !data.data || !data.data.length) return false;

            const datasets = data.graph.datasets || [];

            if (!datasets || index >= datasets.length) return false;

            return {
                backgroundColor: datasets[index].backgroundColor
            };
        }
    },
}
</script>
