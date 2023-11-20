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
            <StatisticsTable source="casino" tag="money" :currency="true" />
            <StatisticsTable source="edm" tag="money" :currency="true" />
            <StatisticsTable source="pdm" tag="money" :currency="true" />
            <StatisticsTable source="gem" tag="money" :currency="true" />
            <StatisticsTable source="pawn" tag="money" :currency="true" />
            <StatisticsTable source="drugs" tag="money" :currency="true" />
            <StatisticsTable source="store" tag="money" :currency="true" />

            <StatisticsTable source="robberies" tag="amount" :currency="false" />
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
}
</script>
