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

        <portal to="actions">
            <button class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" type="button" @click="refresh">
                <span v-if="!isLoading">
                    <i class="fa fa-refresh mr-1"></i>
                    {{ t('global.refresh') }}
                </span>
                <span v-else>
                    <i class="fas fa-spinner animate-spin mr-1"></i>
                    {{ t('global.loading') }}
                </span>
            </button>
        </portal>

        <template>
            <div class="bg-gray-100 p-6 rounded shadow-lg max-w-full dark:bg-gray-600">
                <table class="whitespace-nowrap w-full">
                    <tr class="bg-gray-400 dark:bg-gray-800 no-alpha">
                        <th class="font-semibold px-4 py-1.5 text-left">{{ t('points.player') }}</th>
                        <th class="font-semibold px-4 py-1.5 text-left">{{ t('points.week_0') }}</th>
                        <th class="font-semibold px-4 py-1.5 text-left">{{ getWeekName(1) }}</th>
                        <th class="font-semibold px-4 py-1.5 text-left">{{ getWeekName(2) }}</th>
                        <th class="font-semibold px-4 py-1.5 text-left">{{ getWeekName(3) }}</th>
                        <th class="font-semibold px-4 py-1.5 text-left">{{ getWeekName(4) }}</th>
                        <th class="font-semibold px-4 py-1.5 text-left">{{ getWeekName(5) }}</th>
                        <th class="font-semibold px-4 py-1.5 text-left">{{ getWeekName(6) }}</th>
                        <th class="font-semibold px-4 py-1.5 text-left">{{ getWeekName(7) }}</th>
                    </tr>

                    <tr v-if="isLoading">
                        <td class="px-4 py-1.5 text-center" colspan="9">
                            <i class="fas fa-spinner animate-spin"></i>
                        </td>
                    </tr>

                    <tr v-for="(player, license) in points" :key="license" class="odd:bg-gray-200 dark:odd:bg-gray-500" :class="{'border-2 border-gray-400': license === $page.auth.player.licenseIdentifier}">
                        <td class="italic px-4 py-1.5">{{ player.name }}</td>
                        <td class="px-4 py-1.5" :class="getColorForPoints(amount)" v-for="(amount, week) in player.points" :key="week">{{ numberFormat(amount, 1, false) }}</td>
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
        points: Object,
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
        getColorForPoints(points) {
            if (typeof points === 'number') {
                if (points < 100) {
                    return 'text-red-600 dark:text-red-400';
                } else if (points > 500) {
                    return 'text-lime-600 dark:text-lime-400';
                }
            }

            return false;
        },
        getWeekName(week) {
            return this.$moment.utc().subtract(week, 'weeks').day("Monday").format('Do MMM');
        }
    },
}
</script>
