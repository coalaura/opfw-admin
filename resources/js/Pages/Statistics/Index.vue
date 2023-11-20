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

            <StatisticsTable source="casino" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="edm" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="pdm" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="tuner" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="gem" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="pawn" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="drugs" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="store" tag="money" :currency="true" :search="search" />
            <StatisticsTable source="paycheck" tag="money" :currency="true" :search="search" />

            <StatisticsTable source="robberies" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="joins" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="ooc" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="reports" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="impounds" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="robbed_peds" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="daily_tasks" tag="amount" :currency="false" :search="search" />
            <StatisticsTable source="deaths" tag="amount" :currency="false" :search="search" />
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
            search: "",
        }
    }
}
</script>
