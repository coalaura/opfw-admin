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
            <StatisticsTable :data="casino" :title="t('statistics.casino')" :details="t('statistics.casino_details')" />
            <StatisticsTable :data="pdm" :title="t('statistics.pdm')" :details="t('statistics.pdm_details')" />
            <StatisticsTable :data="edm" :title="t('statistics.edm')" :details="t('statistics.edm_details')" />
            <StatisticsTable :data="gem" :title="t('statistics.gem')" :details="t('statistics.gem_details')" />
            <StatisticsTable :data="pawn" :title="t('statistics.pawn')" :details="t('statistics.pawn_details')" />
            <StatisticsTable :data="drugs" :title="t('statistics.drugs')" :details="t('statistics.drugs_details')" />
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
        casino: {
            type: Array,
            required: true,
        },
        pdm: {
            type: Array,
            required: true,
        },
        edm: {
            type: Array,
            required: true,
        },
        gem: {
            type: Array,
            required: true,
        },
        pawn: {
            type: Array,
            required: true,
        },
        drugs: {
            type: Array,
            required: true,
        },
    }
}
</script>
