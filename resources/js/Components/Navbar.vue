<template>
    <div class="flex shadow bg-cover bg-gray-900 relative z-10 navbar">
        <!-- Branding / Logo -->
        <div class="flex-shrink-0 px-8 py-3 text-center text-white mobile:hidden w-72 overflow-hidden">
            <inertia-link href="/" class="flex gap-2">
                <img :src="serverLogo ? serverLogo : '/images/op-logo.png'" class="block w-logo h-logo object-cover" :class="{ 'drop-shadow': banner }" />

                <h1 class="text-lg px-4 flex flex-col text-left justify-center overflow-hidden">
                    <span class="block leading-5 drop-shadow">OP-FW</span>
                    <span class="block text-xs leading-1 italic whitespace-nowrap overflow-ellipsis overflow-hidden drop-shadow">{{ serverName ? serverName : $page.auth.cluster }}</span>
                </h1>
            </inertia-link>
        </div>

        <!-- Nav -->
        <nav class="flex items-center justify-between w-full px-12 py-4 text-white">
            <!-- Left side -->
            <div class="flex items-center space-x-4">
                <!-- Toggle Dark mode -->
                <button class="px-4 py-1 focus:outline-none font-semibold text-white text-sm rounded border-2 border-gray-400 bg-gray-700 hover:bg-gray-600" :class="{ 'shadow': banner }" @click="toggleTheme" v-if="isDarkMode()">
                    <i class="fas fa-moon"></i>
                    {{ t("nav.dark") }}
                </button>
                <button class="px-4 py-1 focus:outline-none font-semibold text-black text-sm rounded border-2 border-gray-700 bg-gray-400 hover:bg-gray-300" :class="{ 'shadow': banner }" @click="toggleTheme" v-else>
                    <i class="fas fa-sun"></i>
                    {{ t("nav.light") }}
                </button>

                <span class="px-4 py-1 ml-3 font-semibold text-black text-sm not-italic border-2 border-yellow-700 bg-warning rounded dark:bg-dark-warning" :title="t('global.permission')" @click="showPermissions" :class="{ 'cursor-pointer': $page.auth.player.isSuperAdmin, 'shadow-sm': banner }">
                    <i class="fas fa-tools"></i>
                    <span v-if="$page.auth.player.isRoot">{{ t('global.root') }}</span>
                    <span v-else-if="$page.auth.player.isSuperAdmin">{{ t('global.super') }}</span>
                    <span v-else-if="$page.auth.player.isSeniorStaff">{{ t('global.senior_staff') }}</span>
                    <span v-else>{{ t('global.staff') }}</span>
                </span>

                <span class="px-4 py-1 ml-3 font-semibold text-black text-sm not-italic border-2 rounded" :class="{ 'shadow': banner, 'bg-gray-500 border-gray-700': serverStatusLoading, 'bg-green-500 border-green-700': !serverStatusLoading && serverUptime, 'bg-red-500 border-red-700': !serverStatusLoading && !serverUptime }" :title="!serverStatusLoading ? (!serverUptime ? t('global.server_offline') : t('global.server_online', serverUptimeDetail)) : ''">
                    <i class="fas fa-sync-alt" v-if="serverStatusLoading"></i>
                    <i class="fas fa-server" v-else></i>

                    <span v-if="serverUptime">{{ serverUptime }}</span>
                    <span v-else>{{ $page.auth.server }}</span>
                </span>

                <span class="px-4 py-1 ml-3 font-semibold text-black text-sm not-italic border-2 border-green-700 bg-success rounded dark:bg-dark-success cursor-pointer" :class="{ 'shadow': banner }" :title="t('nav.world_time_desc', timezones.length)" @click="showingWorldTime = true" v-if="timezones.length > 0">
                    <i class="fas fa-globe"></i>
                </span>

                <button class="px-4 py-1 focus:outline-none font-semibold text-white text-sm rounded border-2 border-twitch-dark bg-twitch" :class="{ 'shadow': banner }" @click="showingStreamers = true" v-if="streamers">
                    <i class="fab fa-twitch"></i>
                    {{ streamers.length === 1 ? t("nav.streamer") : t("nav.streamer_many", streamers.length) }}
                </button>
            </div>

            <!-- Right side -->
            <div class="flex items-center space-x-4">
                <p v-if="$page.discord && $page.discord.global_name" class="italic font-semibold drop-shadow" :title="$page.discord.username">{{ $page.discord.global_name }}</p>
                <p v-else-if="$page.discord" class="italic font-semibold drop-shadow">{{ $page.discord.username }}#{{ $page.discord.discriminator }}</p>

                <div class="w-avatar relative flex-shrink-0" @contextmenu="showContext" v-click-outside="hideContext">
                    <inertia-link :href="'/players/' + $page.auth.player.licenseIdentifier">
                        <img :src="getDiscordAvatar('webp')" class="rounded shadow border-2 border-gray-300" @error="failedDiscordAvatar" />
                    </inertia-link>

                    <div v-if="showingContext" class="absolute top-full right-0 bg-gray-700 rounded border-2 border-gray-500 min-w-context mt-1 shadow-md z-10 text-sm text-white">
                        <a class="px-2 py-1 text-left block w-full hover:bg-gray-600" v-if="$page.serverIp" :href="serverIpUrl" target="_blank">
                            <i class="fas fa-server mr-1"></i>
                            {{ t('global.connect') }}
                        </a>

                        <button @click="showStaffChat" class="px-2 py-1 text-left block w-full hover:bg-gray-600 border-t border-gray-500">
                            <i class="fas fa-comment mr-1"></i>
                            {{ t('staff_chat.title') }}
                        </button>

                        <button @click="openMinesweeper" class="px-2 py-1 text-left block w-full hover:bg-gray-600 border-t border-gray-500">
                            <i class="fas fa-bomb mr-1"></i>
                            {{ t('nav.minesweeper') }}
                        </button>

                        <button @click="showDebugInfo" class="px-2 py-1 text-left block w-full hover:bg-gray-600 border-t border-gray-500" v-if="$page.auth.player.isRoot">
                            <i class="fas fa-wrench mr-1"></i>
                            {{ t('nav.debug_info') }}
                        </button>

                        <button @click="showSocketInfo" class="px-2 py-1 text-left block w-full hover:bg-gray-600 border-t border-gray-500" v-if="$page.auth.player.isRoot">
                            <i class="fas fa-socks mr-1"></i>
                            {{ t('nav.socket_info') }}
                        </button>

                        <a href="/settings" class="px-2 py-1 text-left block w-full hover:bg-gray-600 border-t border-gray-500">
                            <i class="fas fa-cogs mr-1"></i>
                            {{ t('settings.title') }}
                        </a>

                        <a href="/auth/refresh" class="px-2 py-1 text-left block w-full hover:bg-gray-600 border-t border-gray-500">
                            <i class="fas fa-sync mr-1"></i>
                            {{ t('nav.refresh_discord') }}
                        </a>

                        <inertia-link class="px-2 py-1 block w-full hover:bg-gray-600 border-t border-gray-500" method="POST" href="/logout">
                            <i class="fas fa-sign-out-alt mr-1"></i>
                            {{ t("nav.logout") }}
                        </inertia-link>
                    </div>
                </div>
            </div>
        </nav>

        <modal :show.sync="showingPermissions">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('nav.permissions') }}
                </h1>
            </template>

            <template #default>
                <table class="w-full">
                    <tr class="text-left font-semibold">
                        <td class="py-2 px-1">{{ t('nav.perm') }}</td>
                        <td class="py-2 px-1">{{ t('nav.perm_level') }}</td>
                    </tr>

                    <tr class="text-left hover:bg-gray-100 dark:hover:bg-gray-600" v-for="value in perm" v-if="typeof value === 'string'">
                        <td class="font-semibold py-2 px-1 border-t">{{ t("nav.perms." + value) }}</td>
                        <td class="py-2 px-1 italic border-t">{{ t("nav.level_" + perm.level(value)) }}</td>
                    </tr>
                </table>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="showingPermissions = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

        <modal :show.sync="showingDebugInfo" class="relative">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('nav.debug_info') }}
                </h1>
            </template>

            <template #default>
                <div v-if="debugTime" class="absolute top-1 right-2 text-sm">{{ debugTime }}ms</div>

                <p v-if="loadingDebug" class="py-2 text-center text-xl">
                    <i class="fas fa-spinner animate-spin mr-2"></i>
                    {{ t("nav.debug_collecting") }}
                </p>

                <p v-else-if="!debugInfo" class="py-2 text-center text-xl">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    {{ t("nav.debug_info_failed") }}
                </p>

                <template v-else>
                    <div class="mb-3" v-for="info in debugInfo">
                        <template v-if="info.length === 2">
                            <div class="font-semibold mb-1">
                                {{ t("nav.debug_" + info[0]) }}
                            </div>

                            <pre class="text-xs whitespace-pre-wrap py-2 px-3 bg-gray-200 dark:bg-gray-800 rounded-sm text-red-600 dark:text-red-400 italic" v-if="!info[1]">N/A</pre>
                            <pre class="text-xs whitespace-pre-wrap py-2 px-3 bg-gray-200 dark:bg-gray-800 rounded-sm" v-else>{{ info[1] }}</pre>
                        </template>

                        <template v-else>
                            <div class="w-full border-t border-gray-500 my-6"></div>
                        </template>
                    </div>
                </template>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="showingDebugInfo = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

        <modal :show.sync="showingSocketInfo" class="relative" extraClass="max-w-large">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('nav.socket_info') }}
                </h1>
            </template>

            <template #default>
                <div v-if="socketTime" class="absolute top-1 right-2 text-sm">{{ socketTime }}ms</div>

                <p v-if="loadingSocket" class="py-2 text-center text-xl">
                    <i class="fas fa-spinner animate-spin mr-2"></i>
                    {{ t("nav.socket_collecting") }}
                </p>

                <p v-else-if="!socketInfo" class="py-2 text-center text-xl">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    {{ t("nav.socket_info_failed") }}
                </p>

                <pre class="text-xs whitespace-pre-wrap py-2 px-3 bg-gray-200 dark:bg-gray-800 rounded-sm" v-else v-html="socketInfo"></pre>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="showingSocketInfo = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

        <modal :show.sync="showingWorldTime">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('nav.world_time') }}
                </h1>
            </template>

            <template #default>
                <div class="mb-3 pb-3 border-b-2 border-dashed border-gray-400" v-if="gameTime !== false">
                    <div class="flex py-4 px-6 bg-white dark:bg-gray-600 rounded-lg shadow-sm gap-10 relative">
                        <div class="text-7xl">
                            <img :src="gameTimeClock" class="w-20" />
                        </div>

                        <div class="flex items-center overflow-hidden">
                            <div class="overflow-hidden">
                                <p class="font-semibold text-lg">San Andreas</p>
                                <p class="text-sm">{{ formattedGameTime }}</p>
                            </div>
                        </div>

                        <div class="absolute top-1 right-1 text-sm font-bold leading-4" :title="t('nav.game_time')">x12</div>
                    </div>
                </div>

                <div class="flex py-4 px-6 mb-5 bg-white dark:bg-gray-600 rounded-lg shadow-sm gap-10 relative" v-for="timezone in timezones" :key="timezone.timezone">
                    <div class="text-7xl">
                        <img :src="'/images/earth/' + timezone.icon" class="w-20" />
                    </div>

                    <div class="flex items-center overflow-hidden">
                        <div class="overflow-hidden">
                            <p class="font-semibold text-lg">{{ timezone.timezone }}</p>
                            <p class="text-sm">{{ timezone.time }}</p>
                            <p class="mt-1 text-xs text-muted dark:text-dark-muted italic overflow-ellipsis overflow-hidden whitespace-nowrap" v-if="timezone.alias.length > 0">{{ t('nav.world_time_also', timezone.alias.join(', ')) }}</p>
                        </div>
                    </div>

                    <div class="absolute top-1 right-1 text-sm font-bold leading-4" :title="t('nav.world_time_use', timezone.count)">
                        {{ timezone.count }}
                    </div>
                </div>

                <div class="italic text-sm text-muted dark:text-dark-muted">
                    {{ t('nav.world_time_desc', timezones.length) }}
                </div>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="showingWorldTime = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

        <modal :show.sync="showingStreamers" extraClass="!bg-twitch !bg-opacity-40">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('nav.streamer_title') }}
                </h1>
            </template>

            <template #default>
                <a class="flex py-4 px-6 mb-5 bg-twitch rounded-lg shadow-sm gap-10 relative text-white" v-for="streamer in streamers" :key="streamer.name" v-if="streamers && streamers.length > 0" :href="'https://twitch.tv/' + streamer.name" target="_blank">
                    <div class="text-7xl">
                        <img :src="streamer.avatar" class="w-20 h-20" />
                    </div>

                    <div class="flex items-center overflow-hidden">
                        <div class="overflow-hidden">
                            <p class="font-semibold text-lg">{{ streamer.name }}</p>
                        </div>
                    </div>

                    <div class="absolute top-0.5 right-1 text-sm font-bold drop-shadow-sm">
                        <i class="fas fa-circle text-red-600"></i>
                        LIVE
                    </div>
                </a>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded bg-twitch text-white" @click="showingStreamers = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>
    </div>
</template>

<script>
import moment from 'moment';
import Convert from 'ansi-to-html';
import Icon from './Icon';
import Modal from './Modal';

export default {
    components: {
        Icon,
        Modal
    },
    data() {
        const timezones = this.$page.timezones.map(timezone => {
            timezone.time = this.getDateForTimezone(timezone);
            timezone.icon = this.getTimezoneIcon(timezone.timezone);

            timezone.alias = [];

            return timezone;
        }).filter((timezone, index, self) => {
            const initial = self.findIndex(t => t.time === timezone.time);

            if (initial === index) return true;

            self[initial].alias.push(timezone.timezone);

            return false;
        });

        return {
            showingPermissions: false,
            showingContext: false,

            failedAvatarLoad: false,

            serverStatusLoading: false,
            serverUptime: false,
            serverUptimeDetail: false,
            serverName: false,
            serverLogo: false,

            loadingDebug: false,
            showingDebugInfo: false,
            debugInfo: false,
            debugTime: false,

            loadingSocket: false,
            showingSocketInfo: false,
            socketInfo: false,
            socketTime: false,

            showingWorldTime: false,
            timezones: timezones,

            gameTime: false,
            gameTimeUpdated: false,
            now: false,

            hoveringAvatar: false,
            banner: false,

            streamers: false,
            showingStreamers: false,

            ansi: new Convert()
        }
    },
    computed: {
        formattedGameTime() {
            const baseTime = (this.gameTime + (((this.now - this.gameTimeUpdated) / 1000) * 0.2)) % 1440;

            const hour = Math.floor(baseTime / 60);
            const minute = Math.floor(baseTime % 60).toString().padStart(2, '0');
            const second = Math.floor((baseTime % 1) * 60).toString().padStart(2, '0');

            if (hour === 0) {
                return `12:${minute}:${second} AM`;
            } else if (hour < 12) {
                return `${hour}:${minute}:${second} AM`;
            } else if (hour === 12) {
                return `12:${minute}:${second} PM`;
            } else {
                return `${hour - 12}:${minute}:${second} PM`;
            }
        },
        gameTimeClock() {
            const baseTime = (this.gameTime + (((this.now - this.gameTimeUpdated) / 1000) * 0.2)) % 1440;

            const frame = Math.ceil((((baseTime + 720) % 1440) / 1440) * 64);

            return `/images/clock/clock_${frame}.png`;
        },
        serverIpUrl() {
            const ip = this.$page.serverIp;

            if (!ip) return false;

            if (ip.match(/^\d+/)) {
                return `http://${ip}`;
            }

            return `https://${ip}`;
        }
    },
    methods: {
        getDateForTimezone(pTimezone) {
            const date = new Date((new Date()).toLocaleString('en-US', { timeZone: pTimezone.timezone }));

            return moment(date).format('dddd, MMMM Do YYYY, h:mm:ss A');
        },
        getTimezoneIcon(pTimezone) {
            const area = pTimezone.split('/').shift();

            switch (area) {
                case 'Europe':
                case 'Africa':
                    return 'earth-africa.png';
                case 'America':
                    return 'earth-america.png';
                case 'Asia':
                case 'Indian':
                    return 'earth-asia.png';
                case 'Australia':
                    return 'earth-australia.png';
                case 'Pacific':
                    return 'earth-pacific.png';
                case 'Atlantic':
                    return 'earth-atlantic.png';
                case 'Antarctica':
                    return 'earth-antarctica.png';
                default:
                    return 'earth-america.png';
            }
        },
        getDiscordAvatar(ext) {
            if (this.failedAvatarLoad) return '/images/discord_failed.png';

            const discord = this.$page.discord;

            if (!discord || !discord.id) return '/images/discord.webp';

            return `https://cdn.discordapp.com/avatars/${discord.id}/${discord.avatar}.${ext}`;
        },
        failedDiscordAvatar() {
            this.failedAvatarLoad = true;
        },
        showContext($event) {
            $event.preventDefault();

            this.showingContext = true;
        },
        hideContext() {
            this.showingContext = false;
        },
        showPermissions() {
            if (!this.$page.auth.player.isSuperAdmin) return;

            this.showingPermissions = true;
        },
        showStaffChat() {
            const left = Math.round((window.innerWidth - 480) / 2),
                top = Math.round((window.innerHeight - 700) / 2);

            window.open('/chat', 'Staff Chat', `directories=no,titlebar=no,toolbar=no,menubar=no,location=no,status=no,width=480,height=700,left=${left},top=${top}`);

            this.hideContext();
        },
        openMinesweeper() {
            const left = Math.round((window.innerWidth - 835) / 2),
                top = Math.round((window.innerHeight - 500) / 2);

            window.open('https://mine.coalaura.org', 'Minesweeper', `directories=no,titlebar=no,toolbar=no,menubar=no,location=no,status=no,width=835,height=500,left=${left},top=${top}`);

            this.hideContext();
        },
        formatUptime(pMilliseconds, pIncludeMinutes) {
            if (pMilliseconds < 3600000) {
                return moment.duration(pMilliseconds).format('m [minutes]');
            }

            return moment.duration(pMilliseconds).format('d [days], h [hours]' + (pIncludeMinutes ? ', m [minutes]' : ''));
        },
        async showDebugInfo() {
            if (this.loadingDebug) return;

            this.hideContext();

            this.loadingDebug = true;
            this.showingDebugInfo = true;

            this.debugTime = false;
            this.debugInfo = false;

            try {
                const start = Date.now();

                const debugInfo = await axios.get('/api/debug');

                let time = Date.now() - start;

                const data = debugInfo.data;

                if (data.status && data.data) {
                    time -= Math.floor(data.data.time * 1000);

                    this.debugInfo = data.data.info;
                    this.debugTime = time;
                }
            } catch (e) {
                console.error(e);
            }

            this.loadingDebug = false;
        },
        async showSocketInfo() {
            if (this.loadingSocket) return;

            this.hideContext();

            this.loadingSocket = true;
            this.showingSocketInfo = true;

            this.socketTime = false;
            this.socketInfo = false;

            try {
                const start = Date.now();

                const data = await this.requestStatic('/health', true),
                    time = Date.now() - start;

                const now = new Date(),
                    info = data.info,
                    logs = data.logs;

                // Format info lines
                this.socketInfo = info.split('\n').map(line => {
                    line = line.replace(/\(.+?\)$/m, match => {
                        return `<span class="italic" title="${match.slice(1, -1)}">${match}</span>`
                    });

                    return `<span class="${line.startsWith('+') ? 'text-green-300' : 'text-red-300'}">${line}</span>`;
                }).join('\n');

                // Format log lines
                this.socketInfo += `\n\n<pre class="bg-black py-1 px-1.5 rounded-sm console">`;
                this.socketInfo += logs.split('\n').map(line => {
                    // Format date & ANSI codes
                    return line.replace(/^(\[.+?])(.+?)$/m, (match, date, rest) => {
                        const m = moment(new Date(date.slice(1, -1)));

                        return `<span class="muted" title="${m.format("llll")} (${m.from(now)})">[${m.format('DD/MM/YYYY HH:mm:ss')}]</span>` + this.ansi.toHtml(rest);
                    });
                }).join('\n');
                this.socketInfo += '</pre>';

                this.socketTime = time;
            } catch (e) {
                console.error(e);

                this.socketInfo = `<span class="text-red-300">${e.message}</span>`;
            }

            this.loadingSocket = false;
        },
        async updateServerStatus() {
            this.serverStatusLoading = true;

            const info = await this.requestStatic("/server");

            if (info && info.uptime) {
                this.serverUptime = this.formatUptime(info.uptime, false);
                this.serverUptimeDetail = this.formatUptime(info.uptime, true);
            } else {
                this.serverUptime = false;
                this.serverUptimeDetail = false;
            }

            if (info && info.name) {
                this.serverName = info.name;
            } else {
                this.serverName = false;
            }

            if (info && info.logo) {
                this.serverLogo = info.logo;
            } else {
                this.serverLogo = false;
            }

            if (info && 'baseTime' in info) {
                this.gameTime = info.baseTime;
                this.gameTimeUpdated = Date.now();
            }

            this.serverStatusLoading = false;

            setTimeout(() => {
                this.updateServerStatus();
            }, 20000);
        },
        async updateStreamers() {
            const streamers = await this.requestMisc("/twitch");

            if (streamers && streamers.length > 0) {
                this.streamers = streamers;
            } else {
                this.streamers = false;
            }

            setTimeout(() => {
                this.updateStreamers();
            }, 20000);
        },
    },
    async mounted() {
        // Delay loading of extra data since it blocks other resources from loading
        setTimeout(() => {
            this.updateServerStatus();
            this.updateStreamers();
        }, 500);

        setInterval(() => {
            this.timezones = this.timezones.map(timezone => {
                timezone.time = this.getDateForTimezone(timezone);

                return timezone;
            });
        }, 1000);

        setInterval(() => {
            if (!this.showingWorldTime) return;

            this.now = Date.now();
        }, Math.floor(1000 / 12));

        this.banner = await this.refreshStyle();

        this.$bus.$on('settingsUpdated', async () => {
            this.banner = await this.refreshStyle();
        });
    }
}
</script>
