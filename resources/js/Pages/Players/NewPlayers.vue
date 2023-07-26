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
            <button class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400"
                    type="button" @click="refresh">
                <i class="mr-1 fa fa-refresh"></i>
                {{ t('global.refresh') }}
            </button>
        </portal>

        <v-section class="overflow-x-auto" :noFooter="true">
            <template #header>
                <h2 class="relative">
                    {{ t('players.new.title') }}

                    <div class="absolute top-1/2 right-0 transform -translate-y-1/2 h-7 w-48 rounded-sm bg-rose-800 dark:bg-rose-400 shadow-sm" v-if="isLoadingDictionaries">
                        <div class="h-full rounded-sm bg-rose-900 dark:bg-rose-500" :class="{'bg-green-900 dark:bg-green-500' : progress === 100}" :style="'width: ' + progress + '%'"></div>
                        <div class="absolute top-1/2 left-0 w-full text-center transform -translate-y-1/2 text-xs monospace">{{ t('players.new.loading', progress) }}</div>
                    </div>

                    <select class="inline-block absolute top-1/2 right-0 transform -translate-y-1/2 px-2 py-1 bg-gray-200 dark:bg-gray-600 border" v-model="sorting" @change="sortList()" v-else>
                        <option value="percentage">{{ t('players.new.danny_percentage') }}</option>
                        <option value="server_id">{{ t('global.server_id') }}</option>
                        <option value="playtime">{{ t('players.form.playtime') }}</option>
                    </select>
                </h2>
            </template>

            <template>
                <table class="w-full whitespace-no-wrap">
                    <tr class="font-semibold text-left mobile:hidden">
                        <th class="px-6 py-4"></th>
                        <th class="px-6 py-4">{{ t('global.server_id') }}</th>
                        <th class="px-6 py-4">{{ t('players.form.name') }}</th>
                        <th class="px-6 py-4">{{ t('players.form.playtime') }}</th>
                        <th class="px-6 py-4">{{ t('players.new.danny_percentage') }}</th>
                        <th class="px-6 py-4">{{ t('players.new.character') }}</th>
                        <th class="w-24 px-6 py-4"></th>
                    </tr>
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-600 mobile:border-b-4" v-for="player in playerList"
                        :key="player.licenseIdentifier">
                        <td class="px-6 py-3 border-t mobile:block">
                            <i :class="`${icon.icon} ${icon.color} ml-1`" :title="t('players.new.data.' + icon.key)" v-for="icon in player.data"></i>
                        </td>
                        <td class="px-6 py-3 border-t mobile:block" :title="t('global.server_timeout')">
                            <span class="font-semibold" v-if="player.serverId">
                                {{ player.serverId }}
                            </span>
                            <span class="font-semibold" v-else>
                                {{ t('global.status.offline') }}
                            </span>
                        </td>
                        <td class="px-6 py-3 border-t mobile:block">{{ player.playerName }}</td>
                        <td class="px-6 py-3 border-t mobile:block">{{ formatSecondDiff(player.playTime) }}</td>
                        <td class="px-6 py-3 border-t mobile:block">
                            <span v-if="player.character && player.character.danny !== false">
                                {{ (player.character.danny * 100).toFixed(1) }}% Default Danny
                            </span>
                            <span v-else>
                                {{ t('players.new.no_character') }}
                            </span>

                            <span class="block text-xs italic" v-html="player.prediction"></span>
                        </td>
                        <td class="px-6 py-3 border-t mobile:block">
                            <pre class="whitespace-pre-wrap text-xs max-w-xl break-words" v-html="player.info"></pre>
                        </td>
                        <td class="px-6 py-3 border-t mobile:block">
                            <inertia-link
                                class="block px-4 py-2 font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400"
                                :href="'/players/' + player.licenseIdentifier">
                                <i class="fas fa-chevron-right"></i>
                            </inertia-link>
                        </td>
                    </tr>
                    <tr v-if="players.length === 0">
                        <td class="px-6 py-6 text-center border-t mobile:block" colspan="100%">
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

            isLoadingDictionaries: false,
            progress: 0,

            sorting: 'playtime',

            playerList: this.getPlayerList()
        };
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
        sortList() {
            this.playerList = this.getPlayerList();
        },
        escapeHTML(text) {
            return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        },
        getPlayerList() {
            const list = this.players
                .filter(player => player.character)
                .map(player => {
                    let backstory = this.escapeHTML(player.character.backstory);

                    if (backstory.length > 300) backstory = backstory.substr(0, 300) + "...";

                    const highlight = this.highlightText(backstory, player.character.danny);

                    if (highlight) {
                        backstory = highlight.text;

                        player.prediction = `<span class="text-${highlight.color}-700 dark:text-${highlight.color}-300" title="${highlight.reason}">${this.t("players.new.prediction_label", highlight.prediction)}</span>`;
                    } else {
                        player.prediction = `<span class="text-blue-700 dark:text-blue-300">${this.t("players.new.prediction_loading")}</span>`;
                    }

                    player.info = `<b>${player.character.name}</b><br>${backstory}`;

                    player.data = this.getCharacterData(player);

                    return player;
                });

            const sortBy = this.sorting || 'playtime';

            list.sort((a, b) => {
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
                }

                return 0;
            });

            return list;
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
                    data: this.filters,
                    preserveState: true,
                    preserveScroll: true,
                    only: ['players'],
                });
            } catch (e) { }

            this.playerList = this.getPlayerList();

            this.isLoading = false;
        }
    },
    async mounted() {
        this.isLoadingDictionaries = true;

        await this.loadDictionaries(percentage => {
            this.progress = percentage;
        });

        this.playerList = this.getPlayerList();

        setTimeout(() => {
            this.progress = 100;
            this.isLoadingDictionaries = false;
        }, 1000);
    },
}
</script>
