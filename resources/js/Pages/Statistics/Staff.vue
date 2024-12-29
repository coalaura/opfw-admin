<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t("staff_statistics.title") }}
            </h1>
            <p>
                {{ t("staff_statistics.description") }}
            </p>
        </portal>

        <portal to="actions">
            <button class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" type="button" @click="refresh">
                <span v-if="!isLoading">
                    <i class="fa fa-redo-alt mr-1"></i>
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
                        <th class="font-bold px-4 py-1.5 text-left">&nbsp;</th>
                        <th class="font-bold px-4 py-1.5 text-left">
                            <i class="fas fa-coins"></i>
                            XP
                        </th>
                        <th class="font-bold px-4 py-1.5 text-left">{{ t('staff_statistics.player') }}</th>
                        <th class="font-bold px-4 py-1.5 text-left">{{ t('staff_statistics.claimed_reports') }}</th>
                        <th class="font-bold px-4 py-1.5 text-left">{{ t('staff_statistics.staff_pm_sent') }}</th>
                        <th class="font-bold px-4 py-1.5 text-left italic">{{ t('global.soon_tm') }}</th>
                        <th class="font-bold px-4 py-1.5 text-left italic">{{ t('global.soon_tm') }}</th>
                        <th class="font-bold px-4 py-1.5 text-left italic">{{ t('global.soon_tm') }}</th>
                        <th class="font-bold px-4 py-1.5 text-left italic">{{ t('global.soon_tm') }}</th>
                    </tr>

                    <tr v-for="(player, index) in players" :key="player.license" class="odd:bg-gray-200 dark:odd:bg-gray-500/40" :class="getPlayerClassNames(index, player)">
                        <td class="px-4 py-1.5 text-center" colspan="9" v-if="isLoading">
                            <i class="fas fa-spinner animate-spin"></i>
                        </td>

                        <template v-else>
                            <td class="italic px-4 py-1.5">
                                {{ index + 1 }}.
                                <i class="fas fa-award" v-if="index < 3"></i>
                            </td>
                            <td class="italic px-4 py-1.5">{{ numberFormat(player.xp, 2, false) }}</td>

                            <td class="italic px-4 py-1.5">
                                <a :href="`/players/${player.license}`" target="_blank" :title="player.name">
                                    {{ truncate(player.name, 25) }}

                                    <span class="font-semibold" v-if="status[player.license]">
                                        [{{ status[player.license].source }}]
                                    </span>
                                </a>
                            </td>

                            <td class="px-4 py-1.5 font-medium" :title="formatLast(player.reportsClaimed.time)">
                                {{ numberFormat(player.reportsClaimed.value, 1, false) }}
                            </td>

                            <td class="px-4 py-1.5 font-medium" :title="formatLast(player.staffPmSent.time)">
                                {{ numberFormat(player.staffPmSent.value, 1, false) }}
                            </td>

                            <th class="font-bold px-4 py-1.5 text-left italic">-</th>
                            <th class="font-bold px-4 py-1.5 text-left italic">-</th>
                            <th class="font-bold px-4 py-1.5 text-left italic">-</th>
                            <th class="font-bold px-4 py-1.5 text-left italic">-</th>
                        </template>
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
        players: Object,
    },
    data() {
        return {
            isLoading: false,

            status: {}
        }
    },
    methods: {
        async refresh() {
            if (this.isLoading) return;

            this.isLoading = true;

            try {
                await this.$inertia.replace('/staff', {
                    data: this.filters,
                    preserveState: true,
                    preserveScroll: true,
                    only: ['players'],
                });

                this.updateStatus();
            } catch (e) { }

            this.isLoading = false;
        },
        async updateStatus() {
            const identifiers = Object.keys(this.players);

            if (identifiers) {
                this.status = await this.requestData(`/online/${identifiers}`);
            } else {
                this.status = {};
            }
        },
        formatLast(pTimestamp) {
            if (!pTimestamp) return this.t('staff_statistics.last_time_unknown');

            const date = this.$moment(pTimestamp * 1000),
                ago = date.fromNow(),
                formatted = date.format('dddd, MMMM Do YYYY, h:mm:ss A');

            return this.t('staff_statistics.last_time', ago, formatted);
        },
        getPlayerClassNames(index, player) {
            const classNames = [];

            if (player.license === this.$page.auth.player.licenseIdentifier) {
                classNames.push('border-2 border-gray-400');
            }

            if (index === 0) {
                classNames.push('metallic-gold font-semibold');
            } else if (index === 1) {
                classNames.push('metallic-silver font-semibold');
            } else if (index === 2) {
                classNames.push('metallic-bronze font-semibold');
            } else if (player.xp < 0) {
                classNames.push('text-red-700 dark:text-red-300');
            }

            return classNames.join(' ');
        }
    },
    mounted() {
        this.updateStatus();
    }
}
</script>
