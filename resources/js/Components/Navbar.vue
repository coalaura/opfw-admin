<template>
    <div class="flex">

        <!-- Branding / Logo -->
        <div class="flex-shrink-0 px-8 py-3 text-center text-white bg-gray-900 mobile:hidden">
            <inertia-link href="/" class="flex justify-between">
                <img src="/images/op-logo.png" class="block" />
                <h1 class="text-lg px-4">
                    <span class="block py-4">OP-FW <sub class="font-normal italic">{{ $page.auth.cluster }}</sub></span>
                </h1>
            </inertia-link>
        </div>

        <!-- Nav -->
        <nav class="flex items-center justify-between w-full px-12 py-4 text-white bg-gray-900 shadow">
            <!-- Left side -->
            <p class="italic">
                <span class="px-4 py-1 ml-3 font-semibold text-black text-sm not-italic border-2 rounded float-right" :class="serverStatusLoading ? 'bg-gray-500 border-gray-700' : (serverStatus ? 'bg-green-500 border-green-700' : 'bg-red-500 border-red-700')" :title="!serverStatusLoading ? (!serverStatus ? t('global.server_offline') : t('global.server_online', serverStatus)) : ''">
                    <i class="fas fa-sync-alt" v-if="serverStatusLoading"></i>
                    <i class="fas fa-server" v-else></i>

                    <span v-if="serverStatus">{{ serverStatus }}</span>
                    <span v-else>{{ $page.auth.server }}</span>
                </span>

                <span class="px-4 py-1 ml-3 font-semibold text-black text-sm not-italic border-2 border-yellow-700 bg-warning rounded dark:bg-dark-warning float-right" :title="t('global.permission')" @click="showPermissions" :class="{'cursor-pointer' : $page.auth.player.isSuperAdmin}">
                    <i class="fas fa-tools"></i>
                    <span v-if="$page.auth.player.isRoot">{{ t('global.root') }}</span>
                    <span v-else-if="$page.auth.player.isSuperAdmin">{{ t('global.super') }}</span>
                    <span v-else-if="$page.auth.player.isSeniorStaff">{{ t('global.senior_staff') }}</span>
                    <span v-else>{{ t('global.staff') }}</span>
                </span>

                <!-- Toggle Dark mode -->
                <button class="px-4 py-1 focus:outline-none font-semibold text-white text-sm rounded border-2 border-gray-400 bg-gray-700 hover:bg-gray-600 float-right" @click="toggleTheme" v-if="theme === 'light'">
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
                    <inertia-link :href="'/players/' + $page.auth.player.licenseIdentifier">
                        <img :src="getDiscordAvatar()" class="rounded shadow border-2 border-gray-300" @error="failedDiscordAvatar" />
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
                <p v-if="loadingDebug" class="py-2 text-center">{{ t("global.loading") }}</p>
                <p v-else-if="!debugInfo" class="py-2 text-center">{{ t("nav.debug_info_failed") }}</p>

                <template v-else>
                    <div class="mb-3" v-for="info in debugInfo" :key="info.key">
                        <div class="font-mono mr-1 flex justify-between">
                            <p class="font-semibold">{{ info.key }}</p>
                            <p class="italic">{{ info.type }}</p>
                        </div>

                        <pre class="text-xs whitespace-pre-wrap py-2 px-3 bg-gray-200 dark:bg-gray-800 rounded-sm">{{ info.value }}</pre>
                    </div>
                </template>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="showingDebugInfo = false">
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
        return {
            theme: 'light',

            copiedIp: false,
            copyIpTimeout: false,

            showingPermissions: false,
            showingContext: false,

            failedAvatarLoad: false,

            serverStatusLoading: false,
            serverStatus: false,

            loadingDebug: false,
            showingDebugInfo: false,
            debugInfo: false
        }
    },
    beforeMount() {
        this.updateTheme();
    },
    mounted() {
        this.updateServerStatus();
    },
    methods: {
        getDiscordAvatar() {
            if (this.failedAvatarLoad) return '/images/discord_failed.png';

            const discord = this.$page.discord;

            if (!discord || !discord.id) return '/images/discord.webp';

            return `https://cdn.discordapp.com/avatars/${discord.id}/${discord.avatar}.png`;
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
            window.open('/staff', 'Staff Chat', 'directories=no,titlebar=no,toolbar=no,menubar=no,location=no,status=no,width=550,height=700');

            this.hideContext();
        },
        updateTheme() {
            const cachedTheme = localStorage.getItem('theme') ? localStorage.getItem('theme') : false;
            const userPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (cachedTheme)
                this.theme = cachedTheme;
            else if (userPrefersDark)
                this.theme = 'dark';

            $('html').removeClass('dark');
            if (this.theme === 'dark') {
                $('html').addClass('dark');
            }
        },
        toggleTheme() {
            if ($('html').hasClass('dark')) {
                localStorage.setItem('theme', 'light');
            } else {
                localStorage.setItem('theme', 'dark');
            }

            this.updateTheme();
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
                const debugInfo = await axios.get('/api/debug');

                const data = debugInfo.data;

                if (data.status && data.data) {
                    this.debugInfo = Object.entries(data.data).map(([key, value]) => {
                        return {
                            key: key,
                            value: value,
                            type: typeof value
                        };
                    });

                    this.debugInfo.sort((a, b) => {
                        return a.key.localeCompare(b.key);
                    });
                } else {
                    this.debugInfo = false;
                }
            } catch(e) {
                console.error(e);

                this.debugInfo = false;
            }

            this.loadingDebug = false;
        },
        async updateServerStatus() {
            this.serverStatusLoading = true;

            const uptime = await this.requestData("/uptime");

            // We got a 404
            if (uptime === null) {
                this.serverStatus = false;

                return;
            }

            if (uptime) {
                this.serverStatus = this.formatUptime(uptime);
            } else {
                this.serverStatus = false;
            }

            this.serverStatusLoading = false;

            setTimeout(() => {
                this.updateServerStatus();
            }, 20000);
        }
    },
}
</script>
