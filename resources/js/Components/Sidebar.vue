<template>
    <div class="flex flex-col w-72 px-3 py-10 pt-2 overflow-y-auto font-semibold text-white bg-gray-900v mobile:w-full mobile:py-4 sidebar" :class="{ 'w-10': collapsed }">
        <!-- General stuff -->
        <div class="pb-3 flex justify-between items-center gap-3" :class="{ '!justify-center': collapsed }">
            <div class="relative w-full" v-if="!collapsed">
                <input v-model="search" type="text" :placeholder="t('global.search_placeholder')" class="px-3 py-1 w-full mr-2 bg-gray-900 border-none rounded" :class="{ 'pr-7': search }" />

                <i class="fas fa-times absolute text-gray-300 top-1/2 right-2.5 transform -translate-y-1/2 cursor-pointer" v-if="search" @click="search = ''"></i>
            </div>

            <a href="#" @click="collapse">
                <i class="fas fa-expand-alt" v-if="collapsed"></i>
                <i class="fas fa-compress-alt" v-else></i>
            </a>
        </div>

        <nav v-if="!collapsed">
            <ul v-if="!isMobile()">
                <li v-for="link in links" :key="link.label" v-if="!link.hidden">
                    <inertia-link class="flex items-center px-5 py-2 mb-2 rounded hover:bg-gray-900 hover:text-white whitespace-nowrap drop-shadow" :class="isUrl(link.url) ? ['bg-gray-900', 'text-white'] : ''" :href="link.url" v-if="!('children' in link) && matchesSearch(link)">
                        <i class="w-4 h-4 mr-3 fill-current" :class="link.icon"></i>
                        {{ getLinkLabel(link) }}
                    </inertia-link>
                    <a href="#" class="flex flex-wrap items-center px-5 py-2 mb-2 -mt-1 rounded hover:bg-gray-700v hover:text-white overflow-hidden" :class="height(link.children, $page.auth.player.isSuperAdmin)" v-if="link.children && height(link.children, $page.auth.player.isSuperAdmin)" @click="$event.preventDefault()">
                        <span class="block w-full mb-2 whitespace-nowrap drop-shadow">
                            <i class="w-4 h-4 mr-3 fill-current" :class="link.icon"></i>
                            {{ getLinkLabel(link) }}
                        </span>
                        <ul class="w-full">
                            <li v-for="child in link.children" :key="child.label" v-if="!child.hidden && !link.hidden && matchesSearch(child)">
                                <inertia-link class="flex items-center px-5 py-2 mt-1 rounded hover:bg-gray-900 hover:text-white whitespace-nowrap drop-shadow" :class="isUrl(child.url) ? ['bg-gray-900', 'text-white'] : ''" :href="child.url">
                                    <i class="w-4 h-4 mr-3 fill-current" :class="child.icon"></i>
                                    {{ getLinkLabel(child) }}
                                </inertia-link>
                            </li>
                        </ul>
                    </a>
                </li>
            </ul>

            <ul v-else class="mobile:flex mobile:flex-wrap mobile:justify-between">
                <template v-for="link in links">
                    <inertia-link class="flex items-center px-5 py-2 mb-2 rounded hover:bg-gray-900 hover:text-white text-sm drop-shadow" :class="isUrl(link.url) ? ['bg-gray-900', 'text-white'] : ''" :href="link.url" v-if="!('children' in link) && !link.hidden && matchesSearch(link)">
                        {{ getLinkLabel(link) }}
                    </inertia-link>
                    <inertia-link v-for="child in link.children" class="flex items-center px-5 py-2 mb-2 rounded hover:bg-gray-900 hover:text-white text-sm drop-shadow" :class="isUrl(child.url) ? ['bg-gray-900', 'text-white'] : ''" :href="child.url" :key="child.label" v-if="'children' in link && !(child.hidden || link.hidden) && matchesSearch(child)">
                        {{ getLinkLabel(child) }}
                    </inertia-link>
                </template>
            </ul>
        </nav>

        <div class="mt-auto">
            <!-- Update available -->
            <a class="block px-5 py-2 mt-3 text-center text-black bg-green-400 rounded" target="_blank" href="https://github.com/coalaura/opfw-admin" v-if="!isMobile() && !collapsed && $page.update && $page.auth.player.isSuperAdmin">
                <i class="mr-2 fas fa-wrench"></i> {{ t("nav.update") }}
            </a>

            <!-- Suggest a feature -->
            <a class="block px-5 py-2 mt-3 text-center text-black bg-yellow-400 rounded" target="_blank" href="https://github.com/coalaura/opfw-admin/issues/new/choose" v-if="!isMobile() && !collapsed">
                <i class="mr-2 fas fa-bug"></i> {{ t("nav.report") }}
            </a>
        </div>

        <div class="fixed left-3 bottom-2 text-sm">{{ time }}</div>
    </div>
</template>

<script>
import Icon from './Icon.vue';

export default {
    components: {
        Icon,
    },
    data() {
        const urls = [
            {
                label: "home.title",
                icon: "fas fa-home",
                url: "/",
            },
            {
                label: "steam.title",
                icon: "fab fa-steam",
                url: "/steam",
            },
            {
                label: "discord.title",
                icon: "fab fa-discord",
                url: "/discord",
            },
            {
                label: "players.title",
                icon: "fas fa-users",
                url: "/players",
            },
            {
                label: "players.new.title",
                icon: "fas fa-user-plus",
                url: "/new_players",
            },
            {
                label: "characters.title",
                icon: "fas fa-id-badge",
                url: "/characters",
            },
            {
                label: "stocks.title",
                icon: "fas fa-chart-line",
                url: "/stocks/companies",
            },
            {
                label: "containers.title",
                icon: "fas fa-box-open",
                url: "/containers",
            },
            {
                label: "twitter.title",
                icon: "fab fa-twitter",
                url: "/twitter",
            },
            {
                label: "map.title",
                icon: "fas fa-map",
                url: "/map",
                hidden: !this.perm.check(this.perm.PERM_LIVEMAP) && !this.$page.auth.player.isDebugger,
            },
            {
                label: "logs.title",
                icon: "fas fa-scroll",
                url: "/logs",
            },
            {
                label: "logs.damage",
                icon: "fas fa-crosshairs",
                url: "/damage",
                hidden: !this.perm.check(this.perm.PERM_DAMAGE_LOGS),
            },
            {
                label: "logs.money_title",
                icon: "fas fa-money-bill-wave",
                url: "/moneyLogs",
                hidden: !this.perm.check(this.perm.PERM_MONEY_LOGS),
            },
            {
                label: "phone.title",
                icon: "fas fa-phone",
                url: "/phoneLogs",
                hidden: !this.perm.check(this.perm.PERM_PHONE_LOGS),
            },
            {
                label: "logs.dark_chat",
                icon: "fas fa-user-secret",
                url: "/darkChat",
                hidden: !this.perm.check(this.perm.PERM_DARK_CHAT),
            },
            {
                label: "casino.title",
                icon: "fas fa-dice",
                url: "/casino",
            },
            {
                label: "panel_logs.title",
                icon: "fas fa-sliders-h",
                url: "/panel",
            },
            {
                label: "search_logs.title",
                icon: "fas fa-search",
                url: "/searches",
                hidden: !this.perm.check(this.perm.PERM_ADVANCED),
            },
            {
                label: "screenshot_logs.title",
                icon: "fas fa-camera",
                url: "/screenshot_logs",
                hidden: !this.perm.check(this.perm.PERM_ADVANCED),
            },
            {
                label: "sidebar.all_bans",
                icon: "fas fa-ban",
                url: "/bans",
            },
            {
                label: "sidebar.my_bans",
                icon: "fas fa-hand-paper",
                url: "/my_bans",
            },
            {
                label: "sidebar.system_bans",
                icon: "fas fa-shield-alt",
                url: "/system_bans",
            },
            {
                label: "tokens.title",
                icon: "fas fa-key",
                hidden: !this.perm.check(this.perm.PERM_API_TOKENS),
                url: "/tokens",
            },
            {
                label: "roles.title",
                icon: "fas fa-user-shield",
                url: "/roles",
            },
            {
                label: "blacklist.title",
                icon: "fas fa-user-slash",
                hidden: !this.$page.auth.player.isSuperAdmin,
                url: "/blacklist",
            },
            {
                label: "loading_screen.sidebar",
                icon: "fas fa-spinner",
                hidden: !this.perm.check(this.perm.PERM_LOADING_SCREEN),
                url: "/loading_screen",
            },
            {
                label: "screenshot.anti_cheat_title",
                icon: "fas fa-bug",
                url: "/anti_cheat",
                hidden: !this.perm.check(this.perm.PERM_ANTI_CHEAT),
            },
            {
                label: "statistics.title",
                icon: "fas fa-chart-bar",
                url: "/statistics",
            },
            {
                label: "points.title",
                icon: "fas fa-star",
                url: "/points",
            },
            {
                label: "staff_statistics.title",
                icon: "fas fa-user-tie",
                url: "/staff",
            },
            {
                label: "sidebar.overwatch",
                icon: "fas fa-eye",
                url: "/overwatch",
                hidden: !this.perm.check(this.perm.PERM_SCREENSHOT),
            },
            {
                label: "overwatch.live",
                icon: "fas fa-play-circle",
                url: "/live",
                hidden: !this.$page.overwatch,
            },
            {
                label: "backstories.title",
                icon: "fas fa-book-open",
                url: "/backstories",
            },
            {
                label: "weapons.title",
                icon: "fas fa-bullseye",
                url: "/weapons",
                hidden: !this.perm.check(this.perm.PERM_ADVANCED),
            },
            {
                label: "vehicles.title",
                icon: "fas fa-car",
                url: "/vehicles",
            },
            {
                label: "tools.config.title",
                icon: "fas fa-cog",
                url: "/tools/config"
            },
            {
                label: "sidebar.advanced_search",
                icon: "fas fa-search-plus",
                url: "/advanced",
                hidden: !this.perm.check(this.perm.PERM_ADVANCED),
            },
            {
                label: "sidebar.suspicious",
                icon: "fas fa-exclamation-triangle",
                url: "/suspicious",
                hidden: !this.perm.check(this.perm.PERM_SUSPICIOUS),
            },
            {
                label: "errors.client.title",
                icon: "fas fa-laptop-code",
                url: "/errors/client?server_version=newest",
                hidden: !this.$page.auth.player.isSuperAdmin,
            },
            {
                label: "errors.server.title",
                icon: "fas fa-server",
                url: "/errors/server?server_version=newest",
                hidden: !this.$page.auth.player.isSuperAdmin,
            }
        ];

        const defaultOrder = [
            {
                children: [
                    "/",
                ],
            },

            {
                label: "sidebar.lookup",
                icon: "fas fa-search",
                children: [
                    "/steam",
                    "/discord",
                ],
            },
            {
                label: "sidebar.community",
                icon: "fas fa-users",
                children: [
                    "/players",
                    "/new_players",
                    "/characters",
                    "/stocks/companies",
                    "/containers",
                    "/twitter",
                    "/map",
                ]
            },
            {
                label: "sidebar.logs",
                icon: "fas fa-scroll",
                children: [
                    "/logs",
                    "/damage",
                    "/moneyLogs",
                    "/phoneLogs",
                    "/darkChat",
                    "/casino",
                    "/panel",
                    "/searches",
                    "/screenshot_logs",
                ]
            },
            {
                label: "sidebar.bans",
                icon: "fas fa-ban",
                children: [
                    "/bans",
                    "/my_bans",
                    "/system_bans",
                ]
            },
            {
                label: "sidebar.administration",
                icon: "fas fa-tools",
                children: [
                    "/tokens",
                    "/roles",
                    "/blacklist",
                    "/loading_screen",
                    "/anti_cheat",
                ]
            },
            {
                label: "sidebar.data_stats",
                icon: "fas fa-chart-line",
                children: [
                    "/statistics",
                    "/points",
                    "/staff",
                ]
            },
            {
                label: "sidebar.tools",
                icon: "fas fa-toolbox",
                children: [
                    "/overwatch",
                    "/live",
                    "/backstories",
                    "/weapons",
                    "/vehicles",
                    "/tools/config",
                ]
            },
            {
                label: "sidebar.advanced",
                icon: "fas fa-flask",
                children: [
                    "/advanced",
                    "/suspicious",
                ]
            },
            {
                label: "sidebar.errors",
                icon: "fas fa-exclamation-triangle",
                children: [
                    "/errors/client?server_version=newest",
                    "/errors/server?server_version=newest",
                ]
            }
        ];

        const expand = this.setting("expandSidenav"),
            order = defaultOrder,
            links = [];

        for (const link of order) {
            const children = [];

            for (const child of link.children) {
                const url = urls.find(url => !url.hidden && url.url === child);

                if (url) {
                    children.push(url);
                }
            }

            if (expand) {
                links.push(...children);

                continue;
            }

            if (children.length === 1) {
                links.push(children[0]);
            } else if (children.length > 0) {
                links.push({
                    label: link.label,
                    icon: link.icon,
                    children: children,
                });
            }
        }

        return {
            url: this.$page.url,
            links: links,
            collapsed: false,
            search: "",
            heights: {},
            interval: false,
            time: dayjs().format("h:mm A"),
        };
    },
    watch: {
        '$page.url': function (url) {
            this.url = url;
        }
    },
    methods: {
        isUrl(url) {
            const test = url.replace(/[?#].+$/m, ""),
                against = this.url.replace(/[?#].+$/m, "");

            return test === against;
        },
        height(children) {
            const length = children.filter(l => !l.hidden && this.matchesSearch(l)).length;

            if (length === 0) return 'hidden';

            return `side-item side-${length}`;
        },
        isMobile() {
            return window.outerWidth <= 640;
        },
        collapse($event) {
            $event.preventDefault();

            this.collapsed = !this.collapsed;
        },
        getLinkLabel(link) {
            return link.raw ? link.raw : this.t(link.label);
        },
        matchesSearch(link) {
            const query = this.search.trim().toLowerCase();

            if (!query) return true;

            return this.getLinkLabel(link).toLowerCase().includes(query);
        }
    },
    mounted() {
        setInterval(() => {
            this.time = dayjs().format("h:mm A");
        }, 10000);
    },
    beforeMount() {
        let max = 0;

        for (const link of this.links) {
            if (!link.children) continue;

            const length = link.children.filter(l => !l.hidden).length;

            if (length > max) {
                max = length;
            }
        }

        const styles = document.createElement("style");

        // Closed
        styles.innerHTML += ".side-item { height: 37px; transition: height 0.3s ease; will-change: height; }";

        for (let i = 1; i <= max; i++) {
            // 37px = height of closed sidebar item
            let height = 37;

            // Each entry is 37.5px
            height += i * 37.5;

            // plus 0.25rem margin top (for each entry)
            height += (0.25 * 16) * i;

            // and 0.5rem padding bottom
            height += 0.5 * 16;

            styles.innerHTML += `.side-${i}:hover { height: ${height}px; }`;
        }

        document.head.appendChild(styles);
    }
};
</script>
