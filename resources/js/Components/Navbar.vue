<template>
    <div class="flex">

        <!-- Branding / Logo -->
        <div class="flex-shrink-0 px-8 py-3 text-center text-white bg-gray-900 mobile:hidden w-72 overflow-hidden">
            <inertia-link href="/" class="flex gap-2">
                <img :src="serverLogo ? serverLogo : '/images/op-logo.png'" class="block w-logo h-logo object-cover" />
                <h1 class="text-lg px-4 flex flex-col text-left justify-center overflow-hidden">
                    <span class="block leading-5">OP-FW</span>
                    <span class="block text-xs leading-1 italic whitespace-nowrap overflow-ellipsis overflow-hidden">{{ serverName ? serverName : $page.auth.cluster }}</span>
                </h1>
            </inertia-link>
        </div>

        <!-- Nav -->
        <nav class="flex items-center justify-between w-full px-12 py-4 text-white bg-gray-900 shadow">
            <!-- Left side -->
            <p class="italic">
                <span class="px-4 py-1 ml-3 font-semibold text-black text-sm not-italic border-2 border-green-700 bg-success rounded dark:bg-dark-success float-right cursor-pointer" :title="t('nav.world_time_desc', timezones.length)" @click="showingWorldTime = true" v-if="timezones.length > 0">
                    <i class="fas fa-globe"></i>
                </span>

                <span class="px-4 py-1 ml-3 font-semibold text-black text-sm not-italic border-2 rounded float-right" :class="serverStatusLoading ? 'bg-gray-500 border-gray-700' : (serverStatus ? 'bg-green-500 border-green-700' : 'bg-red-500 border-red-700')" :title="!serverStatusLoading ? (!serverStatus ? t('global.server_offline') : t('global.server_online', serverStatus)) : ''">
                    <i class="fas fa-sync-alt" v-if="serverStatusLoading"></i>
                    <i class="fas fa-server" v-else></i>

                    <span v-if="serverStatus">{{ serverStatus }}</span>
                    <span v-else>{{ $page.auth.server }}</span>
                </span>

                <span class="px-4 py-1 ml-3 font-semibold text-black text-sm not-italic border-2 border-yellow-700 bg-warning rounded dark:bg-dark-warning float-right" :title="t('global.permission')" @click="showPermissions" :class="{ 'cursor-pointer': $page.auth.player.isSuperAdmin }">
                    <i class="fas fa-tools"></i>
                    <span v-if="$page.auth.player.isRoot">{{ t('global.root') }}</span>
                    <span v-else-if="$page.auth.player.isSuperAdmin">{{ t('global.super') }}</span>
                    <span v-else-if="$page.auth.player.isSeniorStaff">{{ t('global.senior_staff') }}</span>
                    <span v-else>{{ t('global.staff') }}</span>
                </span>

                <!-- Toggle Dark mode -->
                <button class="px-4 py-1 focus:outline-none font-semibold text-white text-sm rounded border-2 border-gray-400 bg-gray-700 hover:bg-gray-600 float-right" @click="toggleTheme" v-if="isDarkMode()">
                    <i class="fas fa-moon"></i>
                    {{ t("nav.dark") }}
                </button>
                <button class="px-4 py-1 focus:outline-none font-semibold text-black text-sm rounded border-2 border-gray-700 bg-gray-400 hover:bg-gray-300 float-right" @click="toggleTheme" v-else>
                    <i class="fas fa-sun"></i>
                    {{ t("nav.light") }}
                </button>
            </p>

            <!-- Right side -->
            <div class="flex items-center space-x-4">
                <p v-if="$page.discord && $page.discord.global_name" class="italic font-semibold" :title="$page.discord.username">{{ $page.discord.global_name }}</p>
                <p v-else-if="$page.discord" class="italic font-semibold">{{ $page.discord.username }}#{{ $page.discord.discriminator }}</p>

                <div class="w-avatar relative" @contextmenu="showContext" v-click-outside="hideContext">
                    <inertia-link :href="'/players/' + $page.auth.player.licenseIdentifier" @mouseenter="hoveringAvatar = true" @mouseleave="hoveringAvatar = false">
                        <img :src="getDiscordAvatar('webp')" class="rounded shadow border-2 border-gray-300" :class="showingContext || hoveringAvatar ? 'hidden' : ''" @error="failedDiscordAvatar" />
                        <img :src="getDiscordAvatar('gif')" class="rounded shadow border-2 border-gray-300" :class="showingContext || hoveringAvatar ? '' : 'hidden'" @error="failedDiscordAvatar" rel="preload" />
                    </inertia-link>

                    <div v-if="showingContext" class="absolute top-full right-0 bg-gray-700 rounded border-2 border-gray-500 min-w-context mt-1 shadow-md z-10 text-sm text-white">
                        <button class="px-2 py-1 text-left block w-full hover:bg-gray-600" v-if="$page.serverIp" @click="copyServerIp($page.serverIp)">
                            <i class="fas fa-server mr-1"></i>
                            <span v-if="copiedIp">{{ t('global.copied_ip') }}</span>
                            <span v-else>{{ t('global.copy_ip') }}</span>
                        </button>

                        <button @click="showStaffChat" class="px-2 py-1 text-left block w-full hover:bg-gray-600 border-t border-gray-500">
                            <i class="fas fa-comment mr-1"></i>
                            {{ t('staff_chat.title') }}
                        </button>

                        <button @click="showDebugInfo" class="px-2 py-1 text-left block w-full hover:bg-gray-600 border-t border-gray-500" v-if="$page.auth.player.isRoot">
                            <i class="fas fa-wrench mr-1"></i>
                            {{ t('nav.debug_info') }}
                        </button>

                        <a href="/settings" class="px-2 py-1 text-left block w-full hover:bg-gray-600 border-t border-gray-500">
                            <i class="fas fa-cogs mr-1"></i>
                            {{ t('settings.title') }}
                        </a>

                        <a href="/auth/login" class="px-2 py-1 text-left block w-full hover:bg-gray-600 border-t border-gray-500">
                            <i class="fas fa-sync mr-1"></i>
                            {{ t('nav.refresh_login') }}
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
                <table class="w-full whitespace-no-wrap">
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

        <modal :show.sync="showingDebugInfo">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('nav.debug_info') }}
                </h1>
            </template>

            <template #default>
                <p v-if="loadingDebug" class="py-2 text-center text-xl">
                    <i class="fas fa-spinner animate-spin mr-2"></i>
                    {{ t("nav.debug_collecting") }}
                </p>

                <p v-else-if="!debugInfo" class="py-2 text-center text-xl">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    {{ t("nav.debug_info_failed") }}
                </p>

                <template v-else>
                    <div class="mb-3" v-for="info in debugInfo" :key="info.key">
                        <div class="font-mono mr-1 flex justify-between">
                            <p class="font-semibold">{{ info.key }}</p>
                            <p class="italic">{{ info.type }}</p>
                        </div>

                        <pre class="text-xs whitespace-pre-wrap py-2 px-3 bg-gray-200 dark:bg-gray-800 rounded-sm" v-html="info.value"></pre>
                    </div>
                </template>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="showingDebugInfo = false">
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
                            <img src="/images/earth/san-andreas.png" class="w-20" />
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

    </div>
</template>

<script>
import moment from 'moment';
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
            copiedIp: false,
            copyIpTimeout: false,

            showingPermissions: false,
            showingContext: false,

            failedAvatarLoad: false,

            serverStatusLoading: false,
            serverStatus: false,
            serverName: false,
            serverLogo: false,

            loadingDebug: false,
            showingDebugInfo: false,
            debugInfo: false,

            showingWorldTime: false,
            timezones: timezones,

            gameTime: false,
            gameTimeUpdated: false,
            now: false,

            hoveringAvatar: false
        }
    },
    mounted() {
        this.updateServerStatus();
        this.updateGameTime();

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
        copyServerIp(ip) {
            clearTimeout(this.copyIpTimeout);

            navigator.clipboard.writeText("connect " + ip).then(() => {
                this.copiedIp = true;

                this.copyIpTimeout = setTimeout(() => {
                    this.copiedIp = false;
                }, 2000);
            });
        },
        showStaffChat() {
            window.open('/chat', 'Staff Chat', 'directories=no,titlebar=no,toolbar=no,menubar=no,location=no,status=no,width=480,height=700');

            this.hideContext();
        },
        formatUptime(pMilliseconds) {
            if (pMilliseconds < 3600000) {
                return moment.duration(pMilliseconds).format('m [minutes]');
            }

            return moment.duration(pMilliseconds).format('d [days], h [hours]');
        },
        async showDebugInfo() {
            if (this.loadingDebug) return;

            this.loadingDebug = true;
            this.showingDebugInfo = true;

            try {
                const start = Date.now();

                const debugInfo = await axios.get('/api/debug');

                let time = Date.now() - start;

                const data = debugInfo.data;

                if (data.status && data.data) {
                    time -= Math.floor(data.data.time * 1000);

                    this.debugInfo = Object.entries(data.data.info).map(([key, value]) => {
                        if (!value || value === "unavailable") {
                            value = `<span class="text-red-600 dark:text-red-400 italic">${value}</span>`;
                        }

                        return {
                            key: key,
                            value: value,
                            type: typeof value
                        };
                    });

                    this.debugInfo.sort((a, b) => {
                        return a.key.localeCompare(b.key);
                    });

                    this.debugInfo.unshift({
                        key: 'Ping Pong',
                        value: time + 'ms',
                        type: 'number'
                    });
                } else {
                    this.debugInfo = false;
                }
            } catch (e) {
                console.error(e);

                this.debugInfo = false;
            }

            this.loadingDebug = false;
        },
        async updateServerStatus() {
            this.serverStatusLoading = true;

            const info = await this.requestData("/info");

            if (info && info.uptime) {
                this.serverStatus = this.formatUptime(info.uptime);
            } else {
                this.serverStatus = false;
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

            this.serverStatusLoading = false;

            setTimeout(() => {
                this.updateServerStatus();
            }, 20000);
        },
        async updateGameTime() {
            const world = await this.requestData("/world");

            if (world && 'baseTime' in world) {
                this.gameTime = world.baseTime;
                this.gameTimeUpdated = Date.now();
            }

            setTimeout(() => {
                this.updateGameTime();
            }, 60000);
        }
    },
}
</script>
