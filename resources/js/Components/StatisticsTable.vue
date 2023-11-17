<template>
    <div class="bg-gray-100 p-6 rounded shadow-lg max-w-full dark:bg-gray-600 relative mt-5">
        <h2 class="text-lg">
            {{ title }}
            <sup v-if="totalCount > 0">{{ numberFormat(totalAmount, false, true) }} - x{{ numberFormat(totalCount, false, false) }}</sup>
        </h2>
        <p class="text-sm italic mb-3">{{ details }}</p>

        <span class="absolute bottom-1 right-2 text-xs">{{ numberFormat(time, false, false) }}ms</span>

        <button @click="toggleCollapsed" class="icon-button text-white bg-red-600" :class="{ '!bg-green-600': collapsed }">
            <i class="fas fa-plus" v-if="collapsed"></i>
            <i class="fas fa-minus" v-else></i>
        </button>

        <div class="flex gap-6">
            <div class="overflow-y-auto max-h-statistics inline-block pr-2 flex-shrink-0" ref="table">
                <table class="whitespace-nowrap">
                    <tr class="sticky top-0 bg-gray-300 dark:bg-gray-700 no-alpha">
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.date') }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.count') }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.amount') }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.difference') }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.average') }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.trend') }}</th>
                    </tr>

                    <tr class="border-t border-gray-500" v-if="collapsed">
                        <td class="px-2 py-0.5">...</td>
                        <td class="px-2 py-0.5">...</td>
                        <td class="px-2 py-0.5">...</td>
                        <td class="px-2 py-0.5">...</td>
                        <td class="px-2 py-0.5">...</td>
                        <td class="px-2 py-0.5">...</td>
                    </tr>

                    <tr v-for="(entry, index) in data" :key="index" class="border-t border-gray-500" v-else>
                        <td class="italic text-gray-700 dark:text-gray-300 px-2 py-0.5">{{ entry.date }}</td>
                        <td class="px-2 py-0.5">{{ numberFormat(entry.count, false, false) }}x</td>
                        <td class="px-2 py-0.5">{{ numberFormat(entry.amount, false, true) }}</td>
                        <td class="px-2 py-0.5" v-html="previous(index, 'amount')"></td>
                        <td class="px-2 py-0.5">{{ entry.count > 0 ? numberFormat(entry.amount / entry.count, false, true) : '-' }}</td>
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
        title: {
            type: String,
            required: true,
        },
        details: {
            type: String,
            required: true,
        },
        source: {
            type: Object,
            required: true,
        }
    },
    computed: {
        totalAmount() {
            return this.data.reduce((a, b) => a + b.amount, 0);
        },
        totalCount() {
            return this.data.reduce((a, b) => a + b.count, 0);
        }
    },
    data() {
        return {
            collapsed: true,

            data: this.source.data,
            time: Math.round(this.source.time)
        };
    },
    methods: {
        toggleCollapsed() {
            this.collapsed = !this.collapsed;

            this.$nextTick(() => {
                if (this.collapsed) return;

                this.renderChart();
            });
        },
        calculateCeiling(data) {
            const max = Math.max(...data),
                log = Math.pow(10, Math.floor(Math.log10(max)));

            return Math.ceil(max / log) * log;
        },
        renderChart() {
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
                step = width / data.length,
                ceiling = this.calculateCeiling(data);

            ctx.beginPath();

            ctx.moveTo(1, 1 + (1 - (data[0] / ceiling)) * height);

            for (let i = 0; i < data.length; i++) {
                const amount = data[i];

                const x = 1 + (i+1) * step,
                    y = 1 + (1 - (amount / ceiling)) * height;

                ctx.lineTo(x, y);
            }

            ctx.lineTo(1 + width, 1 + (1 - (data[data.length] / ceiling)) * height);

            ctx.lineWidth = 2;
            ctx.strokeStyle = this.themeColor('gray-400');

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

            const format = this.numberFormat(diff, false, true);

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
