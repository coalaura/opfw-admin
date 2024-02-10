<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t("points.title") }}
            </h1>
            <p>
                {{ t("points.description") }}
            </p>
        </portal>

        <template>
            <div class="bg-gray-100 p-6 rounded shadow-lg max-w-full dark:bg-gray-600">
                <table class="whitespace-nowrap w-full">
                    <tr class="bg-gray-300 dark:bg-gray-700 no-alpha">
                        <th class="font-semibold px-4 py-1.5 text-left">{{ t('points.player') }}</th>
                        <th class="font-semibold px-4 py-1.5 text-left">{{ t('points.week_0') }}</th>
                        <th class="font-semibold px-4 py-1.5 text-left">{{ t('points.week_1') }}</th>
                        <th class="font-semibold px-4 py-1.5 text-left">{{ t('points.week_ago', 2) }}</th>
                        <th class="font-semibold px-4 py-1.5 text-left">{{ t('points.week_ago', 3) }}</th>
                        <th class="font-semibold px-4 py-1.5 text-left">{{ t('points.week_ago', 4) }}</th>
                        <th class="font-semibold px-4 py-1.5 text-left">{{ t('points.week_ago', 5) }}</th>
                        <th class="font-semibold px-4 py-1.5 text-left">{{ t('points.week_ago', 6) }}</th>
                        <th class="font-semibold px-4 py-1.5 text-left">{{ t('points.week_ago', 7) }}</th>
                    </tr>

                    <tr v-if="isLoading">
                        <td class="px-4 py-1.5 text-center" colspan="9">
                            <i class="fas fa-spinner animate-spin"></i>
                        </td>
                    </tr>

                    <tr v-for="(player, license) in points" :key="license" class="odd:bg-gray-200 dark:odd:bg-gray-500" :class="{'border-2 border-gray-400': license === $page.auth.player.licenseIdentifier}">
                        <td class="italic px-4 py-1.5" :class="getColorForPoints(license, false)">{{ player.name }}</td>
                        <td class="px-4 py-1.5" :class="getColorForPoints(license, amount)" v-for="(amount, week) in player.points" :key="week">{{ amount.toFixed(2) }}</td>
                    </tr>
                </table>
            </div>
        </template>

    </div>
</template>

<script>
import Layout from './../../Layouts/App';

export default {
    layout: Layout,
    props: {
        points: Array,
    },
    data() {
        return {
            isLoading: false
        }
    },
    methods: {
        async refresh() {
            if (this.isLoading) return;

            this.isLoading = true;

            try {
                await this.$inertia.replace('/points', {
                    data: this.filters,
                    preserveState: true,
                    preserveScroll: true,
                    only: ['points'],
                });
            } catch (e) { }

            this.isLoading = false;
        },
        getColorForPoints(license, points) {
            if (typeof points === 'number') {
                if (points < 100) {
                    return 'text-red-600 dark:text-red-400';
                } else if (points > 500) {
                    return 'text-lime-600 dark:text-lime-400';
                }
            }

            return false;
        }
    },
}
</script>
