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
                <div class="flex">
                    <h2 class="text-lg flex gap-2" @click="loadEconomyStatistics()" :class="{ 'cursor-pointer': !economyLoading && !economy}">
                        {{ t('statistics.economy_stats') }}
                    </h2>
                </div>

                <p class="text-sm italic mb-3">{{ t('statistics.economy_stats_details') }}</p>

                <button @click="loadEconomyStatistics()" class="icon-button text-white bg-green-600" v-if="!economyLoading && !economy">
                    <i class="fas fa-plus"></i>
                </button>

                <div class="flex gap-6">
                    <div class="overflow-y-auto max-h-statistics inline-block pr-2 flex-shrink-0">
                        <table class="whitespace-nowrap">
                            <tr class="sticky top-0 bg-gray-300 dark:bg-gray-700 no-alpha">
                                <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.date') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.cash') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.bank') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.stocks') }}</th>
                                <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.savings') }}</th>
                            </tr>

                            <tr class="border-t border-gray-500" v-if="!economy">
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                                <td class="px-2 py-0.5">...</td>
                            </tr>

                            <tr class="border-t border-gray-500" v-else-if="economyLoading">
                                <td class="px-2 py-0.5 text-center" colspan="5">
                                    <i class="fas fa-spinner animate-spin"></i>
                                </td>
                            </tr>

                            <tr v-for="(entry, index) in economy.data" :key="index" class="border-t border-gray-500" v-else>
                                <td class="italic text-gray-700 dark:text-gray-300 px-2 py-0.5">{{ entry.date }}</td>

                                <td class="px-2 py-0.5">{{ numberFormat(entry.cash, false, true) }}</td>
                                <td class="px-2 py-0.5">{{ numberFormat(entry.bank, false, true) }}</td>
                                <td class="px-2 py-0.5">{{ numberFormat(entry.stocks, false, true) }}</td>
                                <td class="px-2 py-0.5">{{ numberFormat(entry.savings, false, true) }}</td>
                            </tr>
                        </table>
                    </div>

                    <div v-if="!economyLoading && economy" class="w-full overflow-hidden">
                        <LineChart :chartData="economy.graph"></LineChart>
                    </div>
                </div>
            </div>

            <div class="bg-gray-100 p-6 rounded shadow-lg max-w-full dark:bg-gray-600 relative mb-4">
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
                    <LineChart :chartData="moneyLogData" v-if="moneyLogData"></LineChart>

                    <div class="absolute top-0 left-0 right-0 bottom-0 backdrop-blur-md" v-if="moneyLogLoading">
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                            <i class="fas fa-spinner animate-spin text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full border-t-2 border-dashed border-gray-500 my-6"></div>

            <div class="bg-gray-100 p-6 rounded shadow-lg max-w-full dark:bg-gray-600 relative mb-4">
                <h2 class="text-lg mb-1">
                    {{ t("statistics.search") }}
                </h2>

                <input class="block w-full px-4 py-2 bg-gray-200 border rounded dark:bg-gray-600" v-model="search" type="text" placeholder="Casino Rev..." />
            </div>

            <StatisticsTable source="airlifts" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="bills" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="bus_revenue" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="casino" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="daily_refresh" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="daily_tasks" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="deaths" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="drugs" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="dumpsters" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="edm" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="found_items" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="gem" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="guns" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="impounds" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="joins" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="ls_customs" tag="money" :currency="true" :search="search" />
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
            <StatisticsTable source="special_imports" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="store" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="tuner" tag="money" :currency="true" :search="search" />
        </template>

    </div>
</template>

<script>
import Layout from './../../Layouts/App';
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
            economy: false,

            moneyLogType: "",
            moneyLogTypes: [],
            moneyLogStyles: [],
            moneyLogAbort: false,
            moneyLogLoading: false,
            moneyLogData: false
        }
    },
    methods: {
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
                const response = await axios.post('/statistics/money', {
                    types: this.moneyLogTypes
                }, {
                    signal: this.moneyLogAbort.signal
                }),
                    data = response.data;

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
            if (this.isLoading || this.economy) return;

            this.isLoading = true;

            try {
                const response = await axios.get('/statistics/economy'),
                    data = response.data;

                if (data.status) {
                    this.economy = data.data;
                }
            } catch (e) {
                // Signalize we failed to load the data
                this.economy = {};
            }

            this.isLoading = false;
        },
    },
}
</script>
