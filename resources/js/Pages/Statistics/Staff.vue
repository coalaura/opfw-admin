<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white flex items-center gap-3">
                <img src="/images/social_credit.webp" class="!m-0 w-16 inline-block" v-if="modifier" />
                {{ t("staff_statistics.title" + modifier) }}
            </h1>
            <p>
                {{ t("staff_statistics.description" + modifier) }}
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
            <div class="bg-gray-100 p-6 rounded shadow-lg max-w-full dark:bg-gray-600" :class="{ 'china': modifier }">
                <div class="mb-3 pb-3 border-b border-gray-300 dark:border-gray-700 flex items-center gap-5">
                    <button class="block h-12 px-4 py-1 text-white bg-indigo-600 rounded dark:bg-indigo-400">{{ t('global.apply') }}</button>

                    <input class="block h-12 w-52 px-3 py-1 bg-gray-200 border dark:bg-gray-600" type="date" :max="filters.to" v-model="filters.from">
                    <i class="fas fa-arrows-alt-h text-lg"></i>
                    <input class="block h-12 w-52 px-3 py-1 bg-gray-200 border dark:bg-gray-600" type="date" :min="filters.from" v-model="filters.to">

                    <div class="h-12 w-px bg-gray-300 dark:bg-gray-700"></div>

                    <MultiSelector :items="keys" locale="staff_statistics" v-model="selectedKeys" layout="w-full h-12 flex flex-wrap items-center gap-3 overflow-y-auto" />

                    <div class="h-12 w-px bg-gray-300 dark:bg-gray-700"></div>

                    <button class="block h-12 px-4 py-1 text-white bg-indigo-600 rounded dark:bg-indigo-400" @click="download">{{ t('global.download') }}</button>
                </div>

                <div v-html="myLevel" class="mb-3 pb-3 border-b border-gray-300 dark:border-gray-700"></div>

                <table class="whitespace-nowrap w-full">
                    <tr class="bg-gray-400 dark:bg-gray-800 no-alpha">
                        <th class="font-bold px-4 py-1.5 text-left">&nbsp;</th>
                        <th class="font-bold px-4 py-1.5 text-left">
                            <i class="fas fa-coins"></i>
                            {{ t('staff_statistics.xp' + modifier) }}
                        </th>
                        <th class="font-bold px-4 py-1.5 text-left">{{ t('staff_statistics.player' + modifier) }}</th>

                        <th class="font-bold px-4 py-1.5 text-left" v-for="key in selectedKeys">{{ t(`staff_statistics.${key}`) }}</th>
                    </tr>

                    <tr v-for="(player, index) in players" :key="player.license" class="odd:bg-gray-200 dark:odd:bg-gray-500/40" :class="getPlayerClassNames(index, player)">
                        <td class="px-4 py-1.5 text-center" colspan="10" v-if="isLoading">
                            <i class="fas fa-spinner animate-spin"></i>
                        </td>

                        <template v-else>
                            <td class="italic px-4 py-1.5">
                                {{ index + 1 }}.
                                <i class="fas fa-award" v-if="index < 3"></i>
                            </td>
                            <td class="italic px-4 py-1.5" :title="numberFormat(player.xp, 2, false, 1)">
                                <span :title="level(player.xp, false, true)">{{ level(player.xp, true, false) }}</span>

                                {{ humanize(player.xp) }}
                            </td>

                            <td class="italic px-4 py-1.5">
                                <a :href="`/players/${player.license}`" target="_blank" :title="player.name">
                                    {{ truncate(player.name, 25) }}

                                    <span class="font-semibold" v-if="status[player.license]">
                                        [{{ status[player.license].source }}]
                                    </span>
                                </a>
                            </td>

                            <td class="px-4 py-1.5 font-medium" v-for="key in selectedKeys">
                                {{ numberFormat(player[key] || 0, 0) }}
                            </td>
                        </template>
                    </tr>
                </table>

                <p class="mt-3 italic">{{ t('staff_statistics.footer' + modifier) }}</p>
            </div>
        </template>

    </div>
</template>

<script>
import Layout from './../../Layouts/App';
import MultiSelector from '../../Components/MultiSelector';

export default {
    layout: Layout,
    components: {
        MultiSelector,
    },
    props: {
        players: Array,
        keys: Array,
        filters: {
            from: String,
            to: String,
        }
    },
    data() {
        return {
            isLoading: false,
            modifier: Math.round(Math.random() * 100) <= 4 ? '_chinese' : '',

            status: {},
            selectedKeys: [
                "kicked-player",
                "banned-player",
                "claimed-report",
                "sent-staff-pm",
                "sent-staff-chat",
            ].filter(key => this.keys.includes(key))
        }
    },
    computed: {
        myLevel() {
            const xp = this.players.find(player => player.license === this.$page.auth.player.licenseIdentifier)?.xp || 0;

            return this.level(xp);
        }
    },
    methods: {
        download() {
            const header = [
                this.t('staff_statistics.license'),
                this.t('staff_statistics.player'),
                this.t('staff_statistics.xp'),
            ];

            for (const key of this.selectedKeys) {
                header.push(this.t(`staff_statistics.${key}`));
            }

            const rows = [header];

            for (const player of this.players) {
                const row = [
                    player.license,
                    player.name,
                    this.numberFormat(player.xp, 2, false, 1),
                ];

                for (const key of this.selectedKeys) {
                    row.push(this.numberFormat(player[key] || 0, 0));
                }

                rows.push(row);
            }

            this.createSpreadsheet("staff", rows);
        },
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

            const date = this.$moment(pTimestamp * 1000);
            const ago = date.fromNow();
            const formatted = date.format('dddd, MMMM Do YYYY, h:mm:ss A');

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
        },
        humanize(number) {
            const formatter = new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            });

            const units = [
                ['K', 1000, 10e5],
                ['M', 10e5, 10e8],
                ['B', 10e8, 10e11],
                ['T', 10e11, 10e14],
                ['Q', 10e14, 10e17],
            ];

            for (const unit of units) {
                const [symbol, min, max] = unit;

                if (number >= min && number < max) {
                    return formatter.format(number / min) + symbol;
                }
            }

            return formatter.format(number);
        },
        level(xp, emoji = false, plain = false) {
            const levels = [
                [20, "🌱", "level_0", 0],
                [80, "🔮", "level_1", 10],
                [200, "💫", "level_2", 15],
                [500, "🛡️", "level_3", 30],
                [1200, "🌑", "level_4", 50],
                [2500, "✨", "level_5", 100],
                [5000, "🔥", "level_6", 150],
                [10000, "💥", "level_7", 250],
                [20000, "🌌", "level_8", 500],
                [50000, "💀", "level_9", 1500],
                [Infinity, "🌀", "level_10", 2500]
            ];

            let min = 0;

            for (const level of levels) {
                const [max, symbol, locale, xpPerLevel] = level;

                if (xp < max) {
                    if (emoji) {
                        return xpPerLevel > 0 ? symbol : "";
                    }

                    const name = xpPerLevel > 0 ? this.t("staff_statistics.level", Math.ceil((xp - min) / xpPerLevel)) : "",
                        label = this.t(`staff_statistics.${locale}`),
                        description = this.t(`staff_statistics.${locale}_description`);

                    if (plain) {
                        return `${symbol} ${name ? `${name} - ` : ""}${label}`;
                    }

                    return `<div class="text-lg font-semibold">${symbol} ${name ? `${name} - ` : ""}${label}</div><div class="italic text-sm">${description}</div>`;
                }

                min = max;
            }

            return "";
        }
    },
    mounted() {
        this.updateStatus();
    }
}
</script>
