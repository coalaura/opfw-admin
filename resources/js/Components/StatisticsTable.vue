<template>
    <div class="bg-gray-100 p-6 rounded shadow-lg max-w-full dark:bg-gray-600 relative mt-5">
        <h2 class="text-lg">
            {{ title }}
            <sup v-if="totalCount > 0">{{ numberFormat(totalAmount, false, true) }} - {{ numberFormat(totalCount, false, true) }}</sup>
        </h2>
        <p class="text-sm italic mb-3">{{ details }}</p>

        <button @click="collapsed = !collapsed" class="icon-button text-white bg-red-600" :class="{'!bg-green-600': collapsed}">
            <i class="fas fa-plus" v-if="collapsed"></i>
            <i class="fas fa-minus" v-else></i>
        </button>

        <div class="overflow-y-auto max-h-statistics inline-block pr-2">
            <table>
                <tr class="sticky top-0 bg-gray-300 dark:bg-gray-700 no-alpha">
                    <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.date') }}</th>
                    <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.count') }}</th>
                    <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.amount') }}</th>
                    <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.difference') }}</th>
                    <th class="font-semibold px-2 py-0.5 text-left">{{ t('statistics.average') }}</th>
                </tr>

                <tr class="border-t border-gray-500" v-if="collapsed">
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
                </tr>
            </table>
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
        data: {
            type: Array,
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
        };
    },
    methods: {
        previous(index, key) {
            if (index === 0) return '-';

            const before = this.data[index - 1][key],
                after = this.data[index][key];

            const diff = after - before,
                format = this.numberFormat(diff, false, true);

            if (diff < 0) return `<i class="text-red-700 dark:text-red-300">${format}</i>`;
            else if (diff > 0) return `<span class="text-green-700 dark:text-green-300">+${format}</span>`;

            return '-';
        }
    }
}
</script>
