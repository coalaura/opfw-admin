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
                <h2 class="text-lg mb-1">
                    {{ t("statistics.search") }}
                </h2>

                <input class="block w-full px-4 py-2 bg-gray-200 border rounded dark:bg-gray-600" v-model="search" type="text" placeholder="Casino Rev..." />
            </div>

            <div class="w-full border-t border-gray-500 mb-4"></div>

            <div class="bg-gray-100 p-6 rounded shadow-lg max-w-full dark:bg-gray-600 relative mb-4">
                <div class="flex">
                    <h2 class="text-lg flex gap-2" @click="loadEconomyStatistics()" :class="{'cursor-pointer': !isLoading && !economy}">
                        {{ t('statistics.economy') }}
                    </h2>
                </div>

                <p class="text-sm italic mb-3">{{ t('statistics.economy_details') }}</p>

                <span class="absolute bottom-1 right-2 text-xs" v-if="economy">{{ numberFormat(economy.time, false, false) }}ms</span>

                <button @click="loadEconomyStatistics()" class="icon-button text-white bg-green-600" v-if="!isLoading && !economy">
                    <i class="fas fa-plus"></i>
                </button>

                <div class="overflow-y-auto max-h-statistics inline-block pr-2 flex-shrink-0" ref="table">
                    <table class="whitespace-nowrap">
                        <tr class="sticky top-0 bg-gray-300 dark:bg-gray-700 no-alpha">
                            <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.type') }}</th>
                            <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.count') }}</th>
                            <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.amount') }}</th>
                        </tr>

                        <tr class="border-t border-gray-500" v-if="!isLoading && !economy">
                            <td class="px-2 py-0.5">...</td>
                            <td class="px-2 py-0.5">...</td>
                            <td class="px-2 py-0.5">...</td>
                        </tr>

                        <tr class="border-t border-gray-500" v-else-if="isLoading">
                            <td class="px-2 py-0.5 text-center" colspan="3">
                                <i class="fas fa-spinner animate-spin"></i>
                            </td>
                        </tr>

                        <tr class="border-t border-gray-500" v-else-if="!economy || !economy.data">
                            <td class="px-2 py-0.5 text-center text-red-500 font-semibold" colspan="3">
                                {{ t("statistics.failed_load") }}
                            </td>
                        </tr>

                        <tr v-for="entry in economy.data" :key="index" class="border-t border-gray-500" v-else>
                            <td class="italic text-gray-700 dark:text-gray-300 px-2 py-0.5">{{ entry.details }}</td>
                            <td class="px-2 py-0.5">{{ numberFormat(entry.count, false, false) }}x</td>
                            <td class="px-2 py-0.5">
                                <span v-if="entry.amount > 0" class="text-green-700 dark:text-green-300">+{{ numberFormat(entry.amount, false, true) }}</span>
                                <span v-else class="text-red-700 dark:text-red-300">{{ numberFormat(entry.amount, false, true) }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="w-full border-t border-gray-500 mb-4"></div>

            <StatisticsTable source="airlifts" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="bills" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="casino" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="daily_refresh" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="daily_tasks" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="deaths" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="drugs" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="dumpsters" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="edm" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="gem" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="impounds" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="joins" tag="amount" :currency="false" :search="search" />
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

export default {
    layout: Layout,
    components: {
        StatisticsTable,
    },
    data() {
        return {
            isLoading: false,
            search: "",

            economy: false
        }
    },
    methods: {
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
