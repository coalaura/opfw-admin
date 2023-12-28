<template>
    <div>

        <portal to="title">
            <div class="mb-4">
                <h1 class="dark:text-white">
                    {{ t("home.title") }}
                </h1>

                <p class="text-xs italic" v-html="playerCount" v-if="playerCount"></p>
                <p class="text-xs italic" v-else>{{ t('home.no_player_count') }}</p>

                <p>
                    {{ t("home.welcome", $page.auth.player.safePlayerName) }}
                </p>
            </div>
        </portal>

        <div class="flex -mt-6 justify-between max-w-screen-lg gap-3 mobile:flex-wrap">
            <div class="absolute top-2 right-2 flex">
                <!-- View crafting recipes -->
                <button class="py-1 px-2 ml-2 font-semibold text-white rounded bg-primary dark:bg-dark-primary block" @click="showCrafting()" v-if="this.perm.check(this.perm.PERM_CRAFTING)" :title="t('home.view_crafting')">
                    <i class="fas fa-pencil-ruler"></i>
                </button>

                <!-- Announcement -->
                <button class="py-1 px-2 ml-2 font-semibold text-white rounded bg-danger dark:bg-dark-danger block" @click="isServerAnnouncement = true" v-if="this.perm.check(this.perm.PERM_ANNOUNCEMENT)" :title="t('home.server_announcement')">
                    <i class="fas fa-scroll"></i>
                </button>
            </div>

            <div class="p-4 max-w-xl pl-6 italic border-l-4 border-gray-300 inline-block bg-gray-100 shadow-lg dark:border-gray-500 dark:bg-gray-700 dark:text-gray-100 mobile:w-full mobile:mb-3">
                <span class="mb-1 block" v-html="quote.quote"></span>
                <span class="text-xs">
                    - {{ quote.author }}
                </span>
            </div>
        </div>

        <modal :show.sync="showingCrafting">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('home.crafting') }}
                </h1>
            </template>

            <template #default>
                <p v-if="isLoading" class="py-2 text-center">{{ t("global.loading") }}</p>

                <template v-else>
                    <div class="mb-3" v-for="station in craftingRecipes" :key="station.id">
                        <p class="font-semibold mb-1">Station {{ station.id }}</p>
                        <pre class="text-xs whitespace-pre-wrap py-2 px-3 bg-gray-200 dark:bg-gray-800 rounded-sm">{{ station.data }}</pre>
                    </div>
                </template>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="showingCrafting = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

        <div class="mt-6">
            <img :src="daysWithout" class="max-w-full h-60 shadow-md border border-gray-400 dark:border-gray-600" />
        </div>

        <div class="mt-14">
            <h3 class="mb-2 dark:text-white">
                {{ t('home.bans') }}
            </h3>
            <table class="w-full table-fixed max-w-screen-lg">
                <tr class="font-semibold text-left mobile:hidden">
                    <th class="px-6 py-4">{{ t('home.ban.license') }}</th>
                    <th class="px-6 py-4">{{ t('home.ban.reason') }}</th>
                    <th class="px-6 py-4">{{ t('home.ban.length') }}</th>
                    <th class="px-6 py-4">{{ t('home.ban.time') }}</th>
                </tr>
                <tr class="hover:bg-gray-100 dark:hover:bg-gray-600 mobile:border-b-4" v-for="ban in bans">
                    <td class="px-6 py-3 border-t mobile:block">
                        <inertia-link class="block px-4 py-2 font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="'/players/' + ban.identifier">
                            {{ playerName(ban.identifier) }}
                        </inertia-link>
                    </td>
                    <td class="px-6 py-3 border-t mobile:block truncate" :title="ban.reason" v-if="ban.reason">
                        {{ ban.reason }}
                    </td>
                    <td class="px-6 py-3 border-t mobile:block italic" v-else>
                        {{ t('global.none') }}
                    </td>
                    <td class="px-6 py-3 border-t mobile:block whitespace-nowrap">{{ banTime(ban) }}</td>
                    <td class="px-6 py-3 border-t mobile:block whitespace-nowrap">{{ ban.timestamp | formatTime }}</td>
                </tr>
                <tr v-if="bans.length === 0">
                    <td class="px-6 py-6 text-center border-t" colspan="4">
                        {{ t('home.no_bans') }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="mt-5">
            <h3 class="dark:text-white">
                {{ t('home.staff') }}
            </h3>
            <div class="flex flex-wrap -mx-3">
                <inertia-link class="block px-4 py-2 font-semibold text-center text-white bg-gray-500 dark:bg-gray-600 rounded m-3" v-for="player in staff" :key="player.id" :href="'/players/' + player.licenseIdentifier">
                    <i class="fas fa-bed mr-1 cursor-help text-purple-300" v-if="!characters.includes(player.licenseIdentifier)" :title="t('home.no_character')"></i>

                    <i class="fas fa-toggle-on mr-1 text-green-300 cursor-help" v-if="player.staffToggled" :title="t('home.staff_toggled')"></i>
                    <i class="fas fa-toggle-off mr-1 text-red-300 cursor-help" v-else :title="t('home.staff_toggled_off')"></i>

                    <i class="fas fa-eye-slash mr-1 text-red-300 cursor-help" v-if="player.staffHidden" :title="t('home.staff_hidden')"></i>
                    <i class="fas fa-eye mr-1 text-green-300 cursor-help" v-else :title="t('home.staff_not_hidden')"></i>

                    {{ player.playerName }}
                </inertia-link>
            </div>
            <p class="italic" v-if="staff.length === 0">
                {{ t('global.none') }}
            </p>
            <h3 class="dark:text-white mt-4">
                {{ t('home.locations') }}
            </h3>
            <p class="text-sm mb-1 text-muted dark:text-dark-muted">
                {{ t('home.location_description') }}
            </p>
            <div class="flex flex-wrap justify-between max-w-screen-lg w-full -mx-3">
                <a href="#" class="w-tp text-indigo-600 dark:text-indigo-300 hover:text-yellow-500 dark:hover:text-yellow-300 px-3 py-0.5 text-sm" @click="copyCoords($event)" :data-coords="coords.x + ' ' + coords.y + ' ' + coords.z" v-for="(coords, name) in generalLocations" :key="name">{{ name }}</a>
            </div>
            <h3 class="dark:text-white mt-3">
                {{ t('home.staff_locations') }}
            </h3>
            <p class="text-sm mb-1 text-muted dark:text-dark-muted">
                {{ t('home.staff_description') }}
            </p>
            <div class="flex flex-wrap justify-between max-w-screen-lg w-full -mx-3">
                <a href="#" class="w-tp-staff text-indigo-600 dark:text-indigo-300 hover:text-yellow-500 dark:hover:text-yellow-300 px-3 py-0.5 text-sm" @click="copyCoords($event)" :data-coords="coords.x + ' ' + coords.y + ' ' + coords.z" v-for="(coords, name) in staffLocations" :key="name">{{ name }}</a>
            </div>
        </div>

        <div class="fixed bg-black bg-opacity-70 top-0 left-0 right-0 bottom-0 z-2k" v-if="isServerAnnouncement">
            <div class="shadow-xl absolute bg-gray-100 dark:bg-gray-600 text-black dark:text-white left-2/4 top-2/4 -translate-x-2/4 -translate-y-2/4 transform p-6 rounded w-alert">
                <h3 class="mb-2">
                    {{ t('home.server_announcement') }}
                </h3>

                <div class="w-full mt-4 flex justify-between">
                    <label class="mr-4 block w-1/4 text-center pt-2 font-bold">
                        {{ t('home.announcement_message') }}
                    </label>
                    <input type="text" class="px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600 w-3/4 ml-1" v-model="announcementMessage" />
                </div>

                <!-- Buttons -->
                <div class="flex justify-end mt-5">
                    <button class="px-5 py-2 rounded bg-success dark:bg-dark-success mr-2" @click="postAnnouncement()">
                        {{ t('home.send_announcement') }}
                    </button>
                    <button class="px-5 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-500 dark:bg-gray-500" @click="isServerAnnouncement = false">
                        {{ t('global.close') }}
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import Layout from './../Layouts/App';
import Modal from './../Components/Modal';

const TPLocations = require('../data/tp_locations.json');

export default {
    layout: Layout,
    components: {
        Modal
    },
    data() {
        const generalLocations = Object.keys(TPLocations.general).sort().reduce(
            (obj, key) => {
                obj[key] = TPLocations.general[key];
                return obj;
            },
            {}
        );
        const staffLocations = Object.keys(TPLocations.staff).sort().reduce(
            (obj, key) => {
                obj[key] = TPLocations.staff[key];
                return obj;
            },
            {}
        );

        return {
            playerCount: false,
            totalPlayers: 1,
            joinedPlayers: 1,
            queuePlayers: 1,
            serverCount: 1,
            generalLocations: generalLocations,
            staffLocations: staffLocations,

            isLoading: false,
            isServerAnnouncement: false,
            announcementMessage: '',

            refreshInterval: false,

            showingCrafting: false,
            craftingRecipes: false
        };
    },
    methods: {
        async postAnnouncement() {
            const message = this.announcementMessage.trim();

            if (this.isLoading || !message) return;

            if (!confirm(this.t('home.confirm_announcement'))) {
                return;
            }

            this.announcementMessage = '';
            this.isServerAnnouncement = false;
            this.isLoading = true;

            // Send request.
            await this.$inertia.post('/announcement', {
                message: message,
            });

            this.isLoading = false;
        },
        async showCrafting() {
            if (this.isLoading) return;

            this.isLoading = true;
            this.showingCrafting = true;

            if (!this.craftingRecipes) {
                this.craftingRecipes = [];

                const data = await axios.get('/api/crafting');

                const rgx = /- - - Station (\d+) - - -\s+(.+?)(?=\s+- - -|$)/gs,
                    text = data.data;

                for (const match of text.matchAll(rgx)) {
                    const station = match[1],
                        recipes = match[2];

                    this.craftingRecipes.push({
                        id: station,
                        data: recipes
                    });
                }
            }

            this.isLoading = false;
        },
        localizePlayerCount() {
            return this.t('home.player_count', this.joinedPlayers, this.totalPlayers, this.queuePlayers);
        },
        copyCoords(e) {
            e.preventDefault();

            const coords = '/tp_coords ' + $(e.target).data('coords');
            this.copyToClipboard(coords);

            $(e.target).addClass('dark:!text-green-400 !text-green-600');
            setTimeout(function () {
                $(e.target).removeClass('dark:!text-green-400 !text-green-600');
            }, 750);
        },
        refresh: async function () {
            const players = await this.requestGenerated("/count.json");

            if (typeof players === "number") {
                this.playerCount = this.t('home.player_count', players);
            } else {
                this.playerCount = false;
            }
        },
        banTime(ban) {
            return ban.expireAt ? this.$options.filters.humanizeSeconds(this.$moment(ban.expireAt).unix() - this.$moment(ban.timestamp).unix()) : this.t('players.ban.forever_edit');
        },
        playerName(licenseIdentifier) {
            return licenseIdentifier in this.playerMap ? this.playerMap[licenseIdentifier] : licenseIdentifier;
        }
    },
    mounted() {
        clearInterval(this.refreshInterval);

        // Delay loading of extra data since it blocks other resources from loading
        setTimeout(() => {
            this.refresh();

            this.refreshInterval = setInterval(() => {
                this.refresh();
            }, 30 * 1000);
        }, 500);
    },
    beforeDestroy() {
        clearInterval(this.refreshInterval);
    },
    props: {
        quote: {
            type: Object,
            required: true,
        },
        bans: {
            type: Array,
            required: true,
        },
        staff: {
            type: Array,
            required: true,
        },
        characters: {
            type: Array,
            required: true,
        },
        playerMap: {
            type: Object,
            required: true,
        },
        daysWithout: {
            type: String,
            required: true,
        },
    }
}
</script>
