<template>
    <div class="flex flex-col w-72 px-3 py-10 pt-2 overflow-y-auto font-semibold text-white bg-gray-900v mobile:w-full mobile:py-4 sidebar" :class="{ 'w-10': collapsed }">
        <!-- General stuff -->
        <div class="pb-3 text-right" :class="{ '!text-center': collapsed }">
            <a href="#" @click="collapse">
                <i class="fas fa-compress-alt" v-if="collapsed"></i>
                <i class="fas fa-expand-alt" v-else></i>
            </a>
        </div>
        <nav v-if="!collapsed">
            <ul v-if="!isMobile()">
                <li v-for="link in links" :key="link.label" v-if="(!link.private || $page.auth.player.isSuperAdmin) && !link.hidden">
                    <inertia-link class="flex items-center px-5 py-2 mb-3 rounded hover:bg-gray-900 hover:text-white whitespace-nowrap drop-shadow" :class="isUrl(link.url) ? ['bg-gray-900', 'text-white'] : ''" :href="link.url" v-if="!('sub' in link)">
                        <icon class="w-4 h-4 mr-3 fill-current" :name="link.icon"></icon>
                        {{ link.raw ? link.raw : t(link.label) }}
                    </inertia-link>
                    <a href="#" class="flex flex-wrap items-center px-5 py-2 mb-3 -mt-1 rounded hover:bg-gray-700v hover:text-white overflow-hidden" :class="len(link.sub, $page.auth.player.isSuperAdmin)" v-if="link.sub && len(link.sub, $page.auth.player.isSuperAdmin)" @click="$event.preventDefault()">
                        <span class="block w-full mb-2 whitespace-nowrap drop-shadow">
                            <icon class="w-4 h-4 mr-3 fill-current" :name="link.icon"></icon>
                            {{ link.raw ? link.raw : t(link.label) }}
                        </span>
                        <ul class="w-full">
                            <li v-for="sub in link.sub" :key="sub.label" v-if="(!sub.private || $page.auth.player.isSuperAdmin) && !sub.hidden">
                                <inertia-link class="flex items-center px-5 py-2 mt-1 rounded hover:bg-gray-900 hover:text-white whitespace-nowrap drop-shadow" :class="isUrl(sub.url) ? ['bg-gray-900', 'text-white'] : ''" :href="sub.url">
                                    <icon class="w-4 h-4 mr-3 fill-current" :name="sub.icon"></icon>
                                    {{ sub.raw ? sub.raw : t(sub.label) }}
                                </inertia-link>
                            </li>
                        </ul>
                    </a>
                </li>
            </ul>
            <ul v-else class="mobile:flex mobile:flex-wrap mobile:justify-between">
                <template v-for="link in links">
                    <inertia-link class="flex items-center px-5 py-2 mb-3 rounded hover:bg-gray-900 hover:text-white text-sm drop-shadow" :class="isUrl(link.url) ? ['bg-gray-900', 'text-white'] : ''" :href="link.url" v-if="!('sub' in link) && (!link.private || $page.auth.player.isSuperAdmin) && !link.hidden">
                        {{ link.raw ? link.raw : t(link.label) }}
                    </inertia-link>
                    <inertia-link v-for="sub in link.sub" class="flex items-center px-5 py-2 mb-3 rounded hover:bg-gray-900 hover:text-white text-sm drop-shadow" :class="isUrl(sub.url) ? ['bg-gray-900', 'text-white'] : ''" :href="sub.url" :key="sub.label" v-if="'sub' in link && (!(sub.private || link.private) || $page.auth.player.isSuperAdmin) && !(sub.hidden || link.hidden)">
                        {{ sub.raw ? sub.raw : t(sub.label) }}
                    </inertia-link>
                </template>
            </ul>
        </nav>

        <div class="mt-auto">
            <!-- Update available -->
            <a class="block px-5 py-2 mt-3 text-center text-black bg-green-400 rounded" target="_blank" href="https://github.com/coalaura/opfw-admin" v-if="!isMobile() && $page.update && $page.auth.player.isSuperAdmin">
                <i class="mr-2 fas fa-wrench"></i> {{ t("nav.update") }}
            </a>

            <!-- Suggest a feature -->
            <a class="block px-5 py-2 mt-3 text-center text-black bg-yellow-400 rounded" target="_blank" href="https://github.com/coalaura/opfw-admin/issues/new/choose" v-if="!isMobile() && !collapsed">
                <i class="mr-2 fas fa-bug"></i> {{ t("nav.report") }}
            </a>
        </div>
    </div>
</template>

<script>
import Icon from './Icon';

export default {
    components: {
        Icon,
    },
    data() {
        let data = {
            url: this.$page.url,
            links: [
                {
                    label: 'home.title',
                    icon: 'dashboard',
                    url: '/',
                },
                {
                    label: 'steam.title',
                    icon: 'steam',
                    url: '/steam',
                },
                {
                    label: 'sidebar.management',
                    icon: 'users',
                    sub: [
                        {
                            label: 'players.title',
                            icon: 'user',
                            url: '/players',
                        },
                        {
                            label: 'players.new.title',
                            icon: 'kiwi',
                            url: '/new_players',
                        },
                        {
                            label: 'characters.title',
                            icon: 'book',
                            url: '/characters',
                        },
                        {
                            label: 'blacklist.title',
                            icon: 'shield',
                            private: true,
                            url: '/blacklist',
                        },
                        {
                            label: 'loading_screen.sidebar',
                            icon: 'spinner',
                            hidden: !this.perm.check(this.perm.PERM_LOADING_SCREEN),
                            url: '/loading_screen',
                        }
                    ]
                },
                {
                    label: 'sidebar.logs',
                    icon: 'boxes',
                    sub: [
                        {
                            label: 'logs.title',
                            icon: 'printer',
                            url: '/logs',
                        },
                        {
                            label: 'logs.money_title',
                            icon: 'money',
                            url: '/moneyLogs',
                            hidden: !this.perm.check(this.perm.PERM_MONEY_LOGS),
                        },
                        {
                            label: 'casino.title',
                            icon: 'chess',
                            url: '/casino',
                        },
                        {
                            label: 'panel_logs.title',
                            icon: 'paperstack',
                            url: '/panel_logs',
                        },
                        {
                            label: 'phone.title',
                            icon: 'phone',
                            url: '/phoneLogs',
                            hidden: !this.perm.check(this.perm.PERM_PHONE_LOGS),
                        }
                    ]
                },
                {
                    label: 'sidebar.bans',
                    icon: 'user-slash',
                    sub: [
                        {
                            label: 'sidebar.all_bans',
                            icon: 'friends',
                            url: '/bans',
                        },
                        {
                            label: 'sidebar.my_bans',
                            icon: 'user',
                            url: '/my_bans',
                        },
                        {
                            label: 'sidebar.system_bans',
                            icon: 'kiwi',
                            url: '/system_bans',
                        }
                    ]
                },
                {
                    label: 'sidebar.data',
                    icon: 'server',
                    sub: [
                        {
                            label: 'map.title',
                            icon: 'map',
                            url: '/map',
                            hidden: !this.perm.check(this.perm.PERM_LIVEMAP),
                        },
                        {
                            label: 'statistics.title',
                            icon: 'statistics',
                            url: '/statistics',
                        },
                        {
                            label: 'twitter.title',
                            icon: 'twitter',
                            url: '/twitter',
                        },
                        {
                            label: 'screenshot.label',
                            icon: 'images',
                            url: '/screenshots',
                        }
                    ]
                },
                {
                    label: 'sidebar.csi',
                    icon: 'prints',
                    sub: [
                        {
                            label: 'sidebar.serials',
                            icon: 'fingerprint',
                            url: '/serials',
                        },
                        {
                            label: 'sidebar.overwatch',
                            icon: 'camera',
                            url: '/overwatch',
                            hidden: !this.perm.check(this.perm.PERM_SCREENSHOT),
                        },
                        {
                            label: 'search_logs.title',
                            icon: 'binoculars',
                            url: '/searches',
                            hidden: !this.perm.check(this.perm.PERM_ADVANCED),
                        },
                        {
                            label: 'screenshot_logs.title',
                            icon: 'portrait',
                            url: '/screenshot_logs',
                            hidden: !this.perm.check(this.perm.PERM_ADVANCED),
                        }
                    ]
                },
                {
                    label: 'sidebar.random',
                    icon: 'random',
                    sub: [
                        {
                            label: 'backstories.title',
                            icon: 'box-open',
                            url: '/backstories',
                        },
                        {
                            label: 'screenshot.anti_cheat_title',
                            icon: 'ghost',
                            url: '/anti_cheat',
                        }
                    ]
                },
                {
                    label: 'sidebar.errors',
                    icon: 'bug',
                    hidden: !this.$page.auth.player.isRoot,
                    sub: [
                        {
                            label: 'errors.client.title',
                            icon: 'spider',
                            url: '/errors/client?server_version=newest',
                        },
                        {
                            label: 'errors.server.title',
                            icon: 'virus',
                            url: '/errors/server'
                        }
                    ]
                },
                {
                    label: 'sidebar.advanced',
                    icon: 'cogs',
                    sub: [
                        {
                            label: 'sidebar.advanced_search',
                            icon: 'search',
                            url: '/advanced',
                            hidden: !this.perm.check(this.perm.PERM_ADVANCED),
                        },
                        {
                            label: 'sidebar.suspicious',
                            icon: 'heart',
                            url: '/suspicious',
                            hidden: !this.perm.check(this.perm.PERM_SUSPICIOUS),
                        },
                        {
                            label: 'inventories.search.label',
                            icon: 'pallet',
                            url: '/search_inventory',
                            private: true,
                        },
                        {
                            label: 'weapons.title',
                            icon: 'damage',
                            url: '/weapons',
                            hidden: !this.perm.check(this.perm.PERM_ADVANCED),
                        }
                    ]
                },
                {
                    label: 'servers.title',
                    icon: 'office',
                    url: '/servers',
                }
            ],
        };

        const servers = this.$page.auth.servers;

        if (servers) {
            let queue = {
                label: 'queue.title',
                icon: 'subway',
                hidden: !this.perm.check(this.perm.PERM_VIEW_QUEUE),
                sub: []
            };

            $.each(servers, (key, name) => {
                queue.sub.push({
                    raw: name.toUpperCase(),
                    icon: 'subway',
                    hidden: !this.perm.check(this.perm.PERM_VIEW_QUEUE),
                    url: '/queue/' + name
                });
            });

            data.links.push(queue);
        }

        data.collapsed = false;

        return data;
    },
    watch: {
        '$page.url': function (url) {
            this.url = url;
        }
    },
    methods: {
        isUrl(url) {
            if (this.url === url) return true;
            if (this.url.substring(1) === '' || url.substring(1) === '') return false;
            return this.url.startsWith(url);
        },
        len(sub, isSuperAdmin) {
            if (this.setting('expandSidenav')) return 'h-auto';

            const length = sub.filter(l => (!l.private || isSuperAdmin) && !l.hidden).length;

            switch (length) {
                case 1:
                    return 'h-side-close hover:h-side-open-one';
                case 2:
                    return 'h-side-close hover:h-side-open-two';
                case 3:
                    return 'h-side-close hover:h-side-open-three';
                case 4:
                    return 'h-side-close hover:h-side-open-four';
                case 5:
                    return 'h-side-close hover:h-side-open-five';
                default:
                    return '';
            }
        },
        isMobile() {
            return $(window).width() <= 640;
        },
        collapse($event) {
            $event.preventDefault();

            this.collapsed = !this.collapsed;
        }
    }
};
</script>
