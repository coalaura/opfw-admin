<template>
    <div class="bg-gray-100 p-6 rounded shadow-lg max-w-full dark:bg-gray-600 relative mt-5">
        <h2 class="text-lg flex gap-2">
            <div class="flex items-center" v-if="tag">
                <span class="bg-lime-400 dark:bg-lime-700 py-0.5 px-2 text-xs rounded-sm shadow-sm" v-if="tag === 'money'">{{ t("statistics.tag_money") }}</span>
                <span class="bg-teal-400 dark:bg-teal-700 py-0.5 px-2 text-xs rounded-sm shadow-sm" v-else-if="tag === 'amount'">{{ t("statistics.tag_amount") }}</span>
            </div>

            <span>
                {{ t('statistics.' + source) }}
                <sup v-if="totalCount > 0">
                    {{ numberFormat(totalAmount, false, currency) }}
                    <span v-if="currency">- x{{ numberFormat(totalCount, false, false) }}</span>
                </sup>
            </span>
        </h2>

        <p class="text-sm italic mb-3">{{ t('statistics.' + source + '_details') }}</p>

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
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.amount') }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.difference') }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.average') }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.trend') }}</th>
                    </tr>

                    <tr class="border-t border-gray-500" v-if="collapsed">
                        <td class="px-2 py-0.5">...</td>
                        <td class="px-2 py-0.5" v-if="currency">...</td>
                        <td class="px-2 py-0.5">...</td>
                        <td class="px-2 py-0.5">...</td>
                        <td class="px-2 py-0.5">...</td>
                        <td class="px-2 py-0.5">...</td>
                    </tr>

                    <tr class="border-t border-gray-500" v-else-if="loading">
                        <td class="px-2 py-0.5 text-center" :colspan="currency ? 6 : 5">
                            <i class="fas fa-spinner animate-spin"></i>
                        </td>
                    </tr>

                    <tr class="border-t border-gray-500" v-else-if="!data">
                        <td class="px-2 py-0.5 text-center text-red-500 font-semibold" :colspan="currency ? 6 : 5">
                            {{ t("statistics.failed_load") }}
                        </td>
                    </tr>

                    <tr v-for="(entry, index) in data" :key="index" class="border-t border-gray-500" v-else>
                        <td class="italic text-gray-700 dark:text-gray-300 px-2 py-0.5">{{ entry.date }}</td>
                        <td class="px-2 py-0.5" v-if="currency">{{ numberFormat(entry.count, false, false) }}x</td>
                        <td class="px-2 py-0.5">{{ numberFormat(entry.amount, false, currency) }}</td>
                        <td class="px-2 py-0.5" v-html="previous(index, 'amount')"></td>
                        <td class="px-2 py-0.5">{{ entry.count > 0 ? numberFormat(entry.amount / entry.count, false, currency) : '-' }}</td>
                        <td class="px-2 py-0.5 font-semibold" v-html="trend(index, 'amount')"></td>
                    </tr>
                </table>
            </div>

            <div v-if="!collapsed" class="w-full overflow-hidden" ref="wrapper">
                <canvas ref="chart" width="0" height="0"></canvas>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'StatisticsTable',
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

            if (!this.collapsed) {
                if (this.data) {
                    this.renderChart();
                } else {
                    this.loadData();
                }
            }
        },
        async loadData() {
            if (this.loading || this.requested) return;

            this.loading = true;
            this.requested = true;

            try {
                const response = await axios.get('/statistics/' + this.source),
                    data = response.data;

                if (data.status) {
                    this.data = data.data.data;
                    this.time = data.data.time;

                    this.renderChart();
                }
            } catch (e) { }

            this.loading = false;
        },
        calculateCeiling(data) {
            const max = Math.max(...data);

            if (max === 0) return 0;

            const log = Math.pow(10, Math.floor(Math.log10(max)));

            return Math.ceil(max / log) * log;
        },
        calculateFloor(data) {
            const min = Math.min(...data);

            if (min >= 0) return 0;

            const log = Math.pow(10, Math.floor(Math.log10(Math.abs(min))));

            return Math.floor(Math.abs(min) / log) * log * -1;
        },
        async renderChart() {
            await this.$nextTick();

            if (this.collapsed) return;

            const canvas = this.$refs.chart,
                wrapper = this.$refs.wrapper,
                table = this.$refs.table,
                ctx = canvas.getContext('2d');

            canvas.width = Math.min(table.offsetWidth, wrapper.offsetWidth);
            canvas.height = wrapper.offsetHeight;

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const data = this.data.map(entry => entry.amount);

            data.reverse();

            const width = canvas.width - 2,
                height = canvas.height - 2,
                step = width / (data.length - 1),
                ceiling = this.calculateCeiling(data),
                floor = this.calculateFloor(data);

            // Map floor<->ceiling to 0<->1
            function map(value) {
                return (value - floor) / (ceiling - floor);
            }

            function y(value) {
                if (ceiling === 0 && floor === 0) return 1 + height;

                return 1 + (height - (map(value) * height));
            }

            // Draw the line graph
            ctx.beginPath();
            ctx.moveTo(1, y(data[0]));

            for (let i = 1; i < data.length; i++) {
                const x = 1 + i * step,
                    yi = y(data[i]);

                ctx.lineTo(x, yi);
            }

            ctx.lineWidth = 2;
            ctx.strokeStyle = this.themeColor('gray-400');

            ctx.stroke();
            ctx.closePath();

            // Draw the x axis lines
            ctx.beginPath();

            for (let i = 0; i <= data.length; i++) {
                const x = 1 + i * step;

                ctx.moveTo(x, 0);
                ctx.lineTo(x, canvas.height);
            }

            ctx.lineWidth = 1;
            ctx.strokeStyle = this.themeColor('gray-400', 0.2);

            ctx.stroke();
            ctx.closePath();

            // Draw the y axis line
            const y0 = y(0);

            ctx.beginPath();

            ctx.moveTo(0, y0);
            ctx.lineTo(canvas.width, y0);

            ctx.lineWidth = 1;
            ctx.strokeStyle = this.themeColor('gray-400', 0.4);

            ctx.stroke();
            ctx.closePath();
        },
        getDiffToPrevious(index, key) {
            if (index >= this.data.length - 1) return null;

            const before = this.data[index + 1][key],
                after = this.data[index][key];

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
