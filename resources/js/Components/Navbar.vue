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
                <button class="px-4 py-1 ml-3 font-semibold text-black text-sm not-italic border-2 border-pink-700 bg-pink-400 rounded float-right" v-if="$page.serverIp" @click="copyServerIp($page.serverIp)">
                    <i class="fas fa-server"></i>
                    <span v-if="copiedIp">{{ t('global.copied_ip') }}</span>
                    <span v-else>{{ t('global.copy_ip') }}</span>
                </button>

                <span class="px-4 py-1 ml-3 font-semibold text-black text-sm not-italic border-2 border-yellow-700 bg-warning rounded dark:bg-dark-warning float-right" :title="t('global.permission')" @click="showPermissions" :class="{'cursor-pointer' : $page.auth.player.isSuperAdmin}">
                    <i class="fas fa-tools"></i>
                    <span v-if="$page.auth.player.isRoot">{{ t('global.root') }}</span>
                    <span v-else-if="$page.auth.player.isSuperAdmin">{{ t('global.super') }}</span>
                    <span v-else-if="$page.auth.player.isSeniorStaff">{{ t('global.senior_staff') }}</span>
                    <span v-else>{{ t('global.staff') }}</span>
                </span>

                <button @click="showStaffChat" class="px-4 py-1 ml-3 font-semibold text-black text-sm not-italic border-2 border-green-700 bg-success rounded dark:bg-dark-success float-right">
                    <i class="fas fa-comment"></i>
                    {{ t('staff_chat.title') }}
                </button>

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
                <inertia-link class="px-4 py-1 focus:outline-none font-semibold text-sm text-white rounded border-2 border-red-700 bg-red-500 hover:bg-red-400" method="POST" href="/logout">
                    {{ t("nav.logout") }}
                </inertia-link>

                <inertia-link class="hover:text-gray-100 w-avatar" v-bind:href="'/players/' + $page.auth.player.licenseIdentifier">
                    <img :src="getDiscordAvatar()" :title="getDiscordTitle()" class="rounded shadow border-2 border-gray-300" />
                </inertia-link>
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

    </div>
</template>

<script>
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

            showingPermissions: false
        }
    },
    beforeMount() {
        this.updateTheme();
    },
    methods: {
        getDiscordAvatar() {
            const discord = this.$page.discord;

            if (!discord || !discord.id) return '/images/discord.webp';

            return `https://cdn.discordapp.com/avatars/${discord.id}/${discord.avatar}.png`;
        },
        getDiscordTitle() {
            const discord = this.$page.discord;

            if (!discord || !discord.id) return 'Missing discord info';

            return `Logged in as ${discord.username}#${discord.discriminator}`;
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
            window.open('/staff','Staff Chat','directories=no,titlebar=no,toolbar=no,menubar=no,location=no,status=no,width=550,height=700');
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
        }
    },
}
</script>
