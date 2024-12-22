<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t('players.new.title') }}
            </h1>
            <p>
                {{ t('players.new.description') }}
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

        <v-section class="overflow-x-auto" :noFooter="true">
            <template #header>
                <h2 class="relative">
                    {{ t('players.new.title') }}

                    <select class="inline-block absolute top-1/2 right-0 transform -translate-y-1/2 px-2 py-1 bg-gray-200 dark:bg-gray-600 border" v-model="sorting" @change="sortList()">
                        <option value="percentage">{{ t('players.new.danny_percentage') }}</option>
                        <option value="server_id">{{ t('global.server_id') }}</option>
                        <option value="playtime">{{ t('players.form.playtime') }}</option>
                        <option value="prediction">{{ t('players.new.prediction') }}</option>
                    </select>
                </h2>
            </template>

            <template>
                <table class="w-full">
                    <tr class="font-semibold text-left mobile:hidden">
                        <th class="p-3 pl-8"></th>
                        <th class="p-3">{{ t('global.server_id') }}</th>
                        <th class="p-3">{{ t('players.form.name') }}</th>
                        <th class="p-3">{{ t('players.form.playtime') }}</th>
                        <th class="p-3">{{ t('players.new.danny_percentage') }}</th>
                        <th class="p-3">{{ t('players.new.character') }}</th>
                        <th class="w-24 p-3 pr-8"></th>
                    </tr>
                    <tr class="border-t border-gray-300 dark:border-gray-500 hover:bg-gray-200 dark:hover:bg-gray-600" v-for="player in playerList" :key="player.licenseIdentifier">
                        <td class="p-3 pl-8 mobile:block">
                            <i :class="`${icon.icon} ${icon.color} ml-1`" :title="t('players.new.data.' + icon.key)" v-for="icon in player.data"></i>
                        </td>
                        <td class="p-3 mobile:block" :title="t('global.server_timeout')">
                            <span class="font-semibold" v-if="player.serverId">
                                {{ player.serverId }}
                            </span>
                            <span class="font-semibold" v-else>
                                {{ t('global.status.offline') }}
                            </span>
                        </td>
                        <td class="p-3 mobile:block">
                            <CountryFlag :country="flagFromTZ(player.variables.timezone)" :title="player.variables.timezone" class="rounded-sm" v-if="player.variables && player.variables.timezone" />

                            {{ player.playerName }}
                        </td>
                        <td class="p-3 mobile:block">{{ formatSecondDiff(player.playTime) }}</td>
                        <td class="p-3 mobile:block">
                            <span v-if="player.character && player.character.danny !== false" :style="dannyColor(player.character.danny)">
                                {{ (player.character.danny * 100).toFixed(1) }}% Default Danny
                            </span>
                            <span v-else>
                                {{ t('players.new.no_character') }}
                            </span>

                            <span class="block text-xs italic" v-html="player.prediction"></span>
                        </td>
                        <td class="p-3 mobile:block">
                            <pre class="whitespace-pre-wrap text-xs max-w-xl break-words" v-html="player.info"></pre>
                        </td>
                        <td class="p-3 pr-8 mobile:block">
                            <inertia-link class="block px-4 py-2 font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="'/players/' + player.licenseIdentifier">
                                <i class="fas fa-chevron-right"></i>
                            </inertia-link>
                        </td>
                    </tr>
                    <tr v-if="players.length === 0" class="border-t border-gray-300 dark:border-gray-500">
                        <td class="px-8 py-3 text-center mobile:block" colspan="100%">
                            {{ t('players.none') }}
                        </td>
                    </tr>
                </table>

                <p class="mt-3 text-xs italic">{{ t("players.new.prediction_info") }}</p>
            </template>

        </v-section>
    </div>
</template>

<script>
import CountryFlag from 'vue-country-flag';
import Layout from './../../Layouts/App';
import VSection from './../../Components/Section';
import Badge from './../../Components/Badge';
import Pagination from './../../Components/Pagination';

const colors = {
    pink: 'text-pink-700 dark:text-pink-300',
    blue: 'text-blue-700 dark:text-blue-300',
    rose: 'text-rose-700 dark:text-rose-300',
    red: 'text-red-700 dark:text-red-300',
    green: 'text-green-700 dark:text-green-300',
    yellow: 'text-yellow-700 dark:text-yellow-300',
};

const dataIcons = {
    dead: ['fas fa-skull-crossbones', 'pink'],
    trunk: ['fas fa-truck-loading', 'blue'],
    in_shell: ['fas fa-egg', 'blue'],
    invisible: ['fas fa-eye-slash', 'rose'],
    invincible: ['fas fa-fist-raised', 'red'],
    frozen: ['fas fa-ice-cream', 'red'],
    spawned: ['fas fa-smile', 'green'],
    no_collisions: ['fas fa-wind', 'yellow'],
    no_gameplay_cam: ['fas fa-camera-retro', 'yellow'],
};

export default {
    layout: Layout,
    components: {
        VSection,
        Badge,
        Pagination,
        CountryFlag
    },
    props: {
        players: {
            type: Array,
            required: true,
        }
    },
    data() {
        return {
            isLoading: false,
            sorting: 'playtime'
        };
    },
    computed: {
        playerList() {
            const sortBy = this.sorting || 'playtime';

            return this.players.filter(player => player.character)
                .map(player => {
                    const backstory = this.truncate(this.escapeHTML(player.character.backstory), 100),
                        highlighted = this.highlightText(backstory, player.character.danny);

                    player.info = `<b>${player.character.name}</b><br>${highlighted.text}`;

                    player.prediction = `<span class="text-${highlighted.color}-700 dark:text-${highlighted.color}-300" title="${highlighted.reason}">${this.t("players.new.prediction_label", highlighted.prediction)}</span>`;
                    player.sortPrediction = highlighted.prediction === "positive" ? 1 : (highlighted.prediction === "neutral" ? 2 : 3);

                    player.data = this.getCharacterData(player);

                    return player;
                }).sort((a, b) => {
                    if (sortBy === 'percentage') {
                        const dannyA = a.character.danny ?? 0;
                        const dannyB = b.character.danny ?? 0;

                        return dannyB - dannyA;
                    } else if (sortBy === 'server_id') {
                        const idA = a.serverId ?? 0;
                        const idB = b.serverId ?? 0;

                        return idB - idA;
                    } else if (sortBy === 'playtime') {
                        const timeA = a.playTime ?? 0;
                        const timeB = b.playTime ?? 0;

                        return timeA - timeB;
                    } else if (sortBy === 'prediction') {
                        const predA = a.sortPrediction ?? 0;
                        const predB = b.sortPrediction ?? 0;

                        return predB - predA;
                    }

                    return 0;
                });
        }
    },
    methods: {
        getCharacterData(player) {
            let data = player?.character?.data;

            if (!data) return [];

            data.sort();

            let remove = [];

            if (data.includes('dead')) remove.push('invincible');
            if (data.includes('trunk')) remove.push('invisible', 'invincible', 'no_gameplay_cam');

            data = data.filter(key => !remove.includes(key));

            return data.map(key => {
                const icon = dataIcons[key];

                return icon ? {
                    key,
                    icon: icon[0],
                    color: colors[icon[1]]
                } : false;
            }).filter(Boolean);
        },
        escapeHTML(text) {
            return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        },
        dannyColor(danny) {
            const h = (1 - danny) * 120;

            return {
                color: this.isDarkMode() ? `hsl(${h}, 90%, 65%)` : `hsl(${h}, 75%, 55%)`
            };
        },
        formatSecondDiff(sec) {
            return this.$moment.duration(sec, 'seconds').format('d[d] h[h] m[m] s[s]');
        },
        refresh: async function () {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;

            try {
                await this.$inertia.replace('/new_players', {
                    preserveState: true,
                    preserveScroll: true,
                });
            } catch (e) { }

            this.isLoading = false;
        },
        wait(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },
    },
}
</script>
