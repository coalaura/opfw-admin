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
            <StatisticsTable :data="pdm" :title="t('statistics.pdm')" :details="t('statistics.pdm_details')" />
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
    props: {
        pdm: {
            type: Array,
            required: true,
        }
    }
}
</script>
