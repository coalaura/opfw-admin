<template>
    <div class="bg-gray-100 p-6 rounded shadow-lg max-w-full dark:bg-gray-600 relative mt-4" v-if="shown">
        <div class="flex" :class="{ 'mb-3': label }">
            <h2 class="text-lg flex gap-2" @click="collapsed && toggleCollapsed()" :class="{ 'cursor-pointer': collapsed }">
                <div class="flex items-center" v-if="tag">
                    <span class="bg-lime-400 dark:bg-lime-700 py-0.5 px-2 text-xs rounded-sm shadow-sm" v-if="tag === 'money'">{{ t("statistics.tag_money") }}</span>
                    <span class="bg-teal-400 dark:bg-teal-700 py-0.5 px-2 text-xs rounded-sm shadow-sm" v-else-if="tag === 'amount'">{{ t("statistics.tag_amount") }}</span>
                </div>

                <span>
                    {{ title }}
                    <sup v-if="totalAmount > 0 || totalCount > 0">
                        {{ numberFormat(totalAmount, false, currency) }}
                        <span v-if="currency">- x{{ numberFormat(totalCount, false, false) }}</span>
                    </sup>
                </span>
            </h2>
        </div>

        <p class="text-sm italic mb-3" v-if="!label">{{ t((this.locale ? this.locale : 'statistics.') + source + '_details') }}</p>

        <span class="absolute bottom-1 right-2 text-xs" v-if="time">{{ numberFormat(time, false, false) }}ms</span>

        <button @click="toggleCollapsed" class="icon-button text-white bg-red-600" :class="{ '!bg-green-600': collapsed }">
            <i class="fas fa-plus" v-if="collapsed"></i>
            <i class="fas fa-minus" v-else></i>
        </button>

        <div class="flex gap-6">
            <div class="overflow-y-auto max-h-statistics inline-block pr-2 flex-shrink-0" ref="table">
                <table class="whitespace-nowrap">
                    <tr class="sticky top-0 bg-gray-300 dark:bg-gray-700 no-alpha">
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.date') }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left" v-if="currency">{{ t('statistics.count') }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left" v-for="(amount, index) in amounts">{{ t('statistics.' + locales[index]) }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.difference') }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.average') }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.trend') }}</th>
                    </tr>

                    <tr class="border-t border-gray-500" v-if="collapsed">
                        <td class="px-2 py-0.5">...</td>
                        <td class="px-2 py-0.5" v-if="currency">...</td>
                        <td class="px-2 py-0.5" v-for="_ in amounts">...</td>
                        <td class="px-2 py-0.5">...</td>
                        <td class="px-2 py-0.5">...</td>
                        <td class="px-2 py-0.5">...</td>
                    </tr>

                    <tr class="border-t border-gray-500" v-else-if="loading">
                        <td class="px-2 py-0.5 text-center" :colspan="(currency ? 5 : 4) + amounts.length">
                            <i class="fas fa-spinner animate-spin"></i>
                        </td>
                    </tr>

                    <tr class="border-t border-gray-500" v-else-if="!data">
                        <td class="px-2 py-0.5 text-center text-red-500 font-semibold" :colspan="(currency ? 5 : 4) + amounts.length">
                            {{ t("statistics.failed_load") }}
                        </td>
                    </tr>

                    <tr v-for="(entry, index) in data" :key="index" class="border-t border-gray-500" v-else>
                        <td class="italic text-gray-700 dark:text-gray-300 px-2 py-0.5">{{ entry.date }}</td>
                        <td class="px-2 py-0.5" v-if="currency">{{ numberFormat(entry.count, false, false) }}x</td>
                        <td class="px-2 py-0.5" v-for="amount in amounts">{{ numberFormat(entry[amount], false, currency) }}</td>
                        <td class="px-2 py-0.5" v-html="previous(index, 'amount')"></td>
                        <td class="px-2 py-0.5">{{ entry.count > 0 ? numberFormat(getEntryAmount(entry) / entry.count, false, currency) : '-' }}</td>
                        <td class="px-2 py-0.5 font-semibold" v-html="trend(index, 'amount')"></td>
                    </tr>
                </table>
            </div>

            <SimpleChart :data="data" v-if="!collapsed && data" :amounts="amounts" :labels="chartLabels" />
        </div>
    </div>
</template>

<script>
import SimpleChart from './Charts/SimpleChart.vue';

export default {
    name: 'StatisticsTable',
    components: {
        SimpleChart,
    },
    props: {
        source: {
            type: String,
            required: true,
        },
        tag: {
            type: String,
        },
        currency: {
            type: Boolean,
        },
        resolve: {
            type: Function,
        },
        locale: {
            type: String,
        },
        label: {
            type: String,
        },
        search: {
            type: String,
        },
        amounts: {
            type: Array,
            default: () => ['amount']
        },
        locales: {
            type: Array,
            default: () => ['amount']
        }
    },
    computed: {
        totalAmount() {
            if (!this.data) return "-";

            return this.data.reduce((a, b) => a + b.amount, 0);
        },
        totalCount() {
            if (!this.data) return "-";

            return this.data.reduce((a, b) => a + b.count, 0);
        },
        shown() {
            if (!this.search) return true;

            const title = this.title.toLowerCase(),
                search = this.search.toLowerCase().trim();

            return !search || title.includes(search);
        },
        title() {
            if (this.label) {
                return this.label;
            } else if (this.locale) {
                return this.t(this.locale + this.source);
            }

            return this.t('statistics.' + this.source);
        },
        chartLabels() {
            const labels = [];

            for (const locale of this.locales) {
                labels.push(this.t(`statistics.${locale}`));
            }

            return labels;
        }
    },
    data() {
        return {
            collapsed: true,

            loading: false,
            requested: false,

            data: false,
            time: false
        };
    },
    methods: {
        toggleCollapsed() {
            this.collapsed = !this.collapsed;

            if (!this.collapsed && !this.data) {
                this.loadData();
            }
        },
        getEntryAmount(entry) {
            let total = 0;

            for (const amount of this.amounts) {
                total += entry[amount] || 0;
            }

            return total;
        },
        async loadData() {
            if (this.loading || this.requested) return;

            this.loading = true;
            this.requested = true;

            try {
                const data = await (this.resolve ? this.resolve(this.source) : fetch(`/statistics/${this.source}`).then(response => response.json()));

                if (data?.status) {
                    this.data = data.data.data;
                    this.time = data.data.time;
                }
            } catch (e) { }

            this.loading = false;
        },
        getDiffToPrevious(index, key) {
            if (index >= this.data.length - 1) return null;

            let before, after;

            if (key === "amount") {
                before = this.getEntryAmount(this.data[index + 1]);
                after = this.getEntryAmount(this.data[index]);
            } else {
                before = this.data[index + 1][key];
                after = this.data[index][key];
            }

            return after - before;
        },
        previous(index, key) {
            const diff = this.getDiffToPrevious(index, key);

            if (diff === null) return '-';

            const format = this.numberFormat(diff, false, this.currency);

            if (diff < 0) return `<span class="text-red-700 dark:text-red-300">${format}</span>`;
            else if (diff > 0) return `<span class="text-green-700 dark:text-green-300">+${format}</span>`;

            return '-';
        },
        trend(index, key) {
            const diff = this.getDiffToPrevious(index, key);

            if (diff === null) return '-';

            if (diff < 0) return `<span class="text-red-700 dark:text-red-300">↘</span>`;
            else if (diff > 0) return `<span class="text-green-700 dark:text-green-300">↗</span>`;

            return `<span class="text-blue-700 dark:text-blue-300">=</span>`;
        },
    }
}
</script>
