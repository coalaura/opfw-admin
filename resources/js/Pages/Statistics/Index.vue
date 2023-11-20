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
            <StatisticsTable :source="casino" :title="t('statistics.casino')" :details="t('statistics.casino_details')" tag="money" />
            <StatisticsTable :source="edm" :title="t('statistics.edm')" :details="t('statistics.edm_details')" tag="money" />
            <StatisticsTable :source="pdm" :title="t('statistics.pdm')" :details="t('statistics.pdm_details')" tag="money" />
            <StatisticsTable :source="gem" :title="t('statistics.gem')" :details="t('statistics.gem_details')" tag="money" />
            <StatisticsTable :source="pawn" :title="t('statistics.pawn')" :details="t('statistics.pawn_details')" tag="money" />
            <StatisticsTable :source="drugs" :title="t('statistics.drugs')" :details="t('statistics.drugs_details')" tag="money" />
            <StatisticsTable :source="store" :title="t('statistics.store')" :details="t('statistics.store_details')" tag="money" />
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
            type: Object,
            required: true,
        },
        pdm: {
            type: Object,
            required: true,
        },
        edm: {
            type: Object,
            required: true,
        },
        gem: {
            type: Object,
            required: true,
        },
        pawn: {
            type: Object,
            required: true,
        },
        drugs: {
            type: Object,
            required: true,
        },
        store: {
            type: Object,
            required: true,
        },
    }
}
</script>
