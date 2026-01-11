<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                <i class="mr-3 fas fa-unlock-alt" :title="perm.restriction(perm.PERM_ANTI_CHEAT)"></i>

                {{ t('screenshot.anti_cheat') }}
            </h1>
            <p>
                {{ t('screenshot.anti_cheat_description') }}
            </p>
        </portal>

        <portal to="actions">
            <button class="px-4 py-2 text-sm font-semibold text-white bg-rose-600 rounded dark:bg-rose-400 mr-3" type="button" @click="showingReasons = true">
                <i class="mr-1 fas fa-info"></i>
                {{ t('screenshot.anti_cheat_reasons') }}
            </button>

            <button class="px-4 py-2 text-sm font-semibold text-white bg-teal-600 rounded dark:bg-teal-400 mr-3" type="button" @click="loadChartData">
                <i class="mr-1 fas fa-chart-area"></i>
                {{ t('screenshot.statistics') }}
            </button>

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

        <!-- Table -->
        <v-section class="overflow-x-auto" :noHeader="true" :noFooter="true" v-if="showChart">
            <div class="h-48 w-full flex items-center justify-center text-3xl" v-if="!chartData">
                <i class="fas fa-spinner animate-spin"></i>
            </div>

            <SimpleChart :data="chartData" :lines="false" height="h-48" v-else />
        </v-section>

        <v-section class="overflow-x-auto" :noHeader="true">
            <template>
                <HashResolver>
                    <table class="w-full">
                        <tr class="font-semibold text-left mobile:hidden">
                            <th class="p-3 pl-8 max-w-56">{{ t('screenshot.player') }}</th>
                            <th class="p-3 w-32">{{ t('screenshot.playtime') }}</th>
                            <th class="p-3 w-40">{{ t('screenshot.screenshot') }}</th>
                            <th class="p-3">{{ t('screenshot.note') }}</th>
                            <th class="p-3 w-32">{{ t('screenshot.ban_status') }}</th>
                            <th class="p-3 pr-8 w-60">{{ t('screenshot.created_at') }}</th>
                        </tr>
                        <tr class="border-t border-gray-300 dark:border-gray-500 hover:bg-gray-200 dark:hover:bg-gray-600" :class="{ 'new-entry': screenshot.new, '!bg-red-500 !bg-opacity-10 opacity-50 hover:opacity-100': screenshot.isBan }" v-for="screenshot in list" :key="screenshot.id">
                            <template v-if="screenshot.isBan">
                                <td class="p-3 py-2 pl-8 text-center mobile:block max-w-56">
                                    <inertia-link class="block px-2 py-1 truncate font-semibold text-center text-sm text-white bg-red-600 rounded dark:bg-red-400" :href="'/players/' + screenshot.license_identifier">
                                        {{ screenshot.player_name }}

                                        <i class="fas fa-user-ninja ml-1 text-white-500 dark:text-white-400" :title="t('players.show.suspicious_spoof')" v-if="screenshot.suspicious"></i>
                                    </inertia-link>
                                </td>
                                <td class="p-3 py-2 mobile:block italic text-gray-600 dark:text-gray-400 text-xs" colspan="4">
                                    Banned indefinitely for <span class="font-semibold">{{ screenshot.reason }}</span>
                                </td>
                                <td class="p-3 py-2 pr-8 mobile:block italic text-gray-600 dark:text-gray-400 w-60 text-sm whitespace-nowrap">
                                    {{ screenshot.timestamp * 1000 | formatTime(true) }}
                                </td>
                            </template>
                            <template v-else>
                                <td class="p-3 pl-8 mobile:block max-w-56">
                                    <inertia-link class="block px-4 py-2 truncate font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="'/players/' + screenshot.license_identifier">
                                        {{ screenshot.player_name }}

                                        <i class="fas fa-user-ninja ml-1 text-red-500 dark:text-red-400" :title="t('players.show.suspicious_spoof')" v-if="screenshot.suspicious"></i>
                                    </inertia-link>
                                </td>
                                <td class="p-3 mobile:block w-32">
                                    {{ screenshot.playtime | humanizeSeconds }}
                                </td>
                                <td class="p-3 mobile:block w-40">
                                    <a :href="screenshot.url" target="_blank" class="text-indigo-600 dark:text-indigo-400" v-if="screenshot.url">{{ t('screenshot.view', screenshot.url.split(".").pop()) }}</a>
                                    <span class="text-teal-600 dark:text-teal-400" v-else>N/A</span>
                                </td>
                                <td class="p-3 mobile:block">
                                    <span class="cursor-help" @click="showMetadata(screenshot.metadata, screenshot.url)">{{ screenshot.details || 'N/A' }}</span>

                                    <div v-if="screenshot.subtitle" class="text-xs text-gray-500 dark:text-gray-400 font-mono ac-subtitle" v-html="screenshot.subtitle" :title="screenshot.subtitleText"></div>
                                </td>
                                <td class="p-3 mobile:block font-semibold w-32">
                                    <span class="text-red-600 dark:text-red-400" v-if="screenshot.ban">
                                        {{ t('global.banned') }}
                                    </span>
                                    <span class="text-green-600 dark:text-green-400 whitespace-nowrap" v-else>
                                        {{ t('global.not_banned') }}
                                    </span>
                                </td>
                                <td class="p-3 mobile:block w-60 italic text-gray-600 dark:text-gray-400 whitespace-nowrap" v-if="screenshot.timestamp">{{ screenshot.timestamp * 1000 | formatTime(true) }}</td>
                                <td class="p-3 pr-8 mobile:block w-60 italic text-gray-600 dark:text-gray-400 whitespace-nowrap" v-else>{{ t('global.unknown') }}</td>
                            </template>
                        </tr>
                        <tr v-if="screenshots.length === 0" class="border-t border-gray-300 dark:border-gray-500">
                            <td class="px-8 py-3 text-center" colspan="100%">
                                {{ t('screenshot.no_screenshots') }}
                            </td>
                        </tr>
                    </table>
                </HashResolver>
            </template>

            <template #footer>
                <div class="flex items-center justify-between mt-6 mb-1">

                    <!-- Navigation -->
                    <div class="flex flex-wrap">
                        <inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="links.prev" v-if="page >= 2">
                            <i class="mr-1 fas fa-arrow-left"></i>
                            {{ t("pagination.previous") }}
                        </inertia-link>
                        <inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" v-if="screenshots.length === 20" :href="links.next">
                            {{ t("pagination.next") }}
                            <i class="ml-1 fas fa-arrow-right"></i>
                        </inertia-link>
                    </div>

                    <!-- Meta -->
                    <div class="font-semibold">
                        {{ t("pagination.page", page) }}
                    </div>

                </div>
            </template>
        </v-section>

        <modal :show.sync="showingReasons">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('screenshot.anti_cheat_reasons') }}
                </h1>
            </template>

            <template #default>
                <template v-for="(contents, category) in reasons">
                    <h2 class="mt-5 border-b-2 border-dashed border-gray-500">{{ category }}</h2>

                    <div class="flex flex-col px-4 py-3 bg-white dark:bg-gray-600 rounded-lg shadow-sm mt-3" v-for="(value, key) in contents" :key="key">
                        <header class="mb-3 border-b border-gray-200 dark:border-gray-400 text-lg font-semibold flex justify-between items-center">
                            {{ key }}

                            <div class="flex items-center gap-1 text-base" v-html="getDetectionStars(category, value)"></div>
                        </header>

                        <div class="flex-grow text-muted dark:text-dark-muted">
                            <h4 class="text-sm font-bold border-b border-gray-500 mb-1">{{ t('screenshot.anti_cheat_reason') }}</h4>
                            <div class="italic text-sm">{{ value.reason.replace("${DATA}", "xyz") }}</div>

                            <template v-if="value.info">
                                <h4 class="text-sm font-bold border-b border-gray-500 mt-3 mb-1">{{ t('screenshot.anti_cheat_info') }}</h4>
                                <div class="italic text-sm">{{ value.info }}</div>
                            </template>
                        </div>
                    </div>
                </template>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="showingReasons = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

        <metadataViewer :title="t('screenshot.metadata')" :image="showingMetadataImage" :metadata="showingMetadata" :show.sync="isShowingMetadata"></metadataViewer>

        <scoped-style>
            .ac-subtitle {
                filter: brightness(0.8);
            }

            .dark .ac-subtitle {
                filter: brightness(1.2);
            }
        </scoped-style>
    </div>
</template>

<script>
import Layout from './../../Layouts/App.vue';
import VSection from './../../Components/Section.vue';
import Pagination from './../../Components/Pagination.vue';
import Modal from './../../Components/Modal.vue';
import HashResolver from './../../Components/HashResolver.vue';
import MetadataViewer from './../../Components/MetadataViewer.vue';
import SimpleChart from '../../Components/Charts/SimpleChart.vue';

export default {
    layout: Layout,
    components: {
        Pagination,
        VSection,
        Modal,
        HashResolver,
        MetadataViewer,
        SimpleChart,
    },
    props: {
        screenshots: {
            type: Array,
            required: true,
        },
        banMap: {
            type: Object,
            required: true,
        },
        links: {
            type: Object,
            required: true,
        },
        reasons: {
            type: Object,
            required: true,
        },
        page: {
            type: Number,
            required: true,
        },
        all: {
            type: Boolean,
            required: true
        }
    },
    data() {
        return {
            isLoading: false,

            showingReasons: false,

            isShowingMetadata: false,
            showingMetadata: null,
            showingMetadataImage: null,

            previousIds: false,

            showChart: false,
            chartData: false
        };
    },
    computed: {
        list() {
            return this.screenshots.map(screenshot => {
                screenshot.ban = this.getBanInfo(screenshot.license_identifier);
                screenshot.isBan = !screenshot.id.startsWith("s_");

                screenshot.new = this.previousIds && !this.previousIds.includes(screenshot.id);

                screenshot.subtitle = this.markdown(this.getSubtitle(screenshot.type, screenshot.metadata), true);

                return screenshot;
            });
        }
    },
    methods: {
        refresh: async function () {
            if (this.isLoading) return;

            this.previousIds = this.screenshots.map(screenshot => screenshot.id);

            this.isLoading = true;

            try {
                await this.$inertia.replace(`/anti_cheat${this.all ? '?all' : ''}`, {
                    preserveState: true,
                    preserveScroll: true,
                    only: ['screenshots', 'banMap', 'links', 'page'],
                });
            } catch (e) { }

            this.isLoading = false;
        },
        async loadChartData() {
            this.showChart = true;
            this.chartData = false;

            try {
                const data = await _get('/anti_cheat/statistics');

                if (data?.status) {
                    this.chartData = data.data.data;
                }
            } catch (e) { }
        },
        getDetectionStars(category, ban) {
            if (category === 'INJECTION') {
                return `<i class="fas fa-asterisk"></i>`;
            }

            if (!ban.info) return '';

            const star = `<i class="fas fa-star"></i>`;
            const half = `<i class="fas fa-star-half-alt"></i>`;
            const empty = `<i class="far fa-star"></i>`;

            if (ban.info.startsWith("Impossible to be scuff")) {
                return star + star + star;
            }if (ban.info.startsWith("Highly unlikely to be scuff")) {
                return star + star + half;
            }if (ban.info.startsWith("Very unlikely to be scuff")) {
                return star + half + empty;
            }if (ban.info.startsWith("Unlikely to be scuff")) {
                return half + empty + empty;
            }

            return '';
        },
        showMetadata(metadata, screenshotUrl) {
            this.showingMetadata = JSON.parse(JSON.stringify(metadata));
            this.showingMetadataImage = screenshotUrl;

            this.isShowingMetadata = true;
        },
        getBanInfo(licenseIdentifier, key) {
            const ban = licenseIdentifier in this.banMap ? this.banMap[licenseIdentifier] : null;

            if (key) {
                return ban && key in ban ? ban[key] : null;
            }

            return ban;
        },
        getSubtitle(type, metadata) {
            if (!type || !metadata) return false;

            function trace(metadata) {
                if (!metadata.trace || !metadata.resource) return "";

                const lines = metadata.trace.split("\n");

                let first;

                while (first = lines.shift()) {
                    if (first.includes("spike_client")) continue;

                    break;
                }

                // [Lua ] @dpemotes/Client/Emote.lua:270: in global 'OnEmotePlay'
                return first.replace(/^\[(.+?)] (.+?:\d+:|\[C]:) in (\?|(\w+ )?'(.+?)')$/gm, (match, type, file, location) => {
                    if (file === '[C]:') {
                        return `C (${metadata.resource}): ${location}`;
                    }

                    const path = `${metadata.resource}/${file.split('/').pop()}`;

                    return `${path} ${location}`;
                });
            }

            switch (type) {
                case 'illegal_event':
                case 'illegal_server_event':
                case 'honeypot':
                    return metadata.eventName;
                case 'illegal_event_usage':
                    return metadata.event;
                case 'suspicious_explosion':
                    return metadata.explosionEvent;
                case 'suspicious_transfer':
                    if (metadata.amount === undefined) return false;

                    return `$${metadata.amount}`;
                case 'runtime_texture':
                    if (!metadata.textureDict || !metadata.textureName) return false;

                    return `${metadata.textureDict} / ${metadata.textureName}`;
                case 'illegal_weapon':
                    return metadata.weaponLabel;
                case 'damage_modifier':
                    if (metadata.expected === undefined || metadata.actual === undefined) return false;

                    return `**${metadata.actual}** - expected **${metadata.expected}**`;
                case 'thermal_night_vision':
                    return metadata.nativeName;
                case 'blacklisted_command':
                    return metadata.command;
                case 'text_entry':
                    if (!metadata.textEntry || !metadata.textEntryValue) return false;

                    return `${metadata.textEntry} - ${metadata.textEntryValue}${metadata.keyboardValue ? `: "*${metadata.keyboardValue}*"` : ''}`;
                case 'fast_movement': {
                    if (metadata.data === undefined || metadata.data.maxDistance === undefined || metadata.data.totalTravelled === undefined) {
                        if (metadata.distance === undefined) return false;

                        return `**${metadata.distance.toFixed(2)}m**`;
                    }

                    const diff = metadata.data.totalTravelled - metadata.data.maxDistance;

                    return `**${metadata.data.totalTravelled.toFixed(2)}m > ${metadata.data.maxDistance.toFixed(2)}m** (${diff < 0 ? '-' : '+'}${Math.abs(diff).toFixed(2)}m)`;
                }
                case 'teleported':
                    if (metadata.distance === undefined) return false;

                    return `**${metadata.distance.toFixed(2)}m**`;
                case 'distance_taze':
                    if (metadata.event === undefined) return false;

                    return `**${metadata.event.distance.toFixed(2)}m**`;
                case 'bad_screen_word':
                    if (metadata.words === undefined) return false;

                    return metadata.words.join(', ');
                case 'semi_godmode':
                case 'infinite_ammo':
                    return metadata.weaponName;
                case 'illegal_native':
                case 'honeypot_native': {
                    if (!metadata.resource || !metadata.native) return false;

                    const native = metadata.native.startsWith('0x') ? `[${metadata.native}](https://docs.fivem.net/natives/?_0x${metadata.native.substr(2).toUpperCase()})` : metadata.native;

                    return `${trace(metadata)} - **${metadata.resource}** / **${native}**`;
                }
                case 'illegal_global':
                    if (!metadata.resource || !metadata.variable) return false;

                    return `${trace(metadata)} - **${metadata.variable}**`;
                case 'illegal_damage': {
                    if (!metadata.type || !metadata.event) return false;

                    const { weaponDamage, weaponHash, distance } = metadata.event;

                    let dmg = `${weaponDamage}hp`;

                    if (metadata.type === 'high_damage' && metadata.maxAllowed !== undefined) {
                        dmg = `${metadata.maxAllowed}+${weaponDamage - metadata.maxAllowed}hp`;
                    }

                    return `${metadata.type}: --${weaponHash}-- - **${dmg}** (${distance.toFixed(2)}m)`;
                }
                case 'illegal_vehicle_modifier':
                    if (!metadata.modifierName || metadata.actualValue === undefined || metadata.expectedValue === undefined) return false;

                    return `${metadata.modifierName}(**${metadata.actualValue}**) - expected **${metadata.expectedValue}**`;
                case 'spawned_object':
                case 'illegal_ped_spawn':
                case 'illegal_vehicle_spawn':
                    if (!metadata.entity) return false;

                    return `${metadata.script ? `${metadata.script}:` : ''} --${metadata.entity.model || metadata.entity.modelHash}-- ${metadata.distance ? `(${metadata.distance.toFixed(2)}m)` : ""}${metadata.isAddon ? ' (**addon**)' : ''}`;
                case 'illegal_local_vehicle':
                    if (!metadata.model) return false;

                    return `${metadata.script ? `${metadata.script}:` : ''} --${metadata.model}--${metadata.isAddon ? ' (**addon**)' : ''}`;
                case 'invalid_health':
                    if (metadata.health === undefined || metadata.maxHealth === undefined || metadata.armor === undefined || metadata.maxArmor === undefined) return false;

                    return `${metadata.health}/${metadata.maxHealth}hp - ${metadata.armor}/${metadata.maxArmor}ap`;
                case 'ped_change': {
                    if (!Array.isArray(metadata.changes)) return false;

                    const changed = metadata.changes.length;

                    const copied = metadata.copiedPlayers && metadata.copiedPlayers.length > 0 ? `- Copied ${metadata.copiedPlayers.map(p => {
                        const regex = / \[\d+\] \((.+?)\)$/gm;
                        const match = regex.exec(p);
                        const license = match ? match[1] : '#';

                        const name = p.replace(regex, '');

                        return `*[${name}](/players/${license})*`;
                    }).join(', ')}` : '';

                    return `**${changed}** propert${changed === 1 ? 'y' : 'ies'} changed ${copied}`;
                }
                case 'advanced_noclip': {
                    if (metadata.aboveGround === undefined) return false;

                    const speed = metadata.playerPed?.speed?.toFixed(2) || '0';

                    return `**${metadata.aboveGround.toFixed(2)}m** AGL @ **${speed}m/s**`;
                }
                case 'freecam_detected':
                case 'illegal_freeze': {
                    if (metadata.playerPed === undefined) return false;

                    const flags = metadata.playerPed.flags.join(', ') || 'no flags';
                    const camDistance = metadata.distance ? ` - ${metadata.distance.toFixed(2)}m` : '';

                    return `${flags}${metadata.playerPed.inVehicle ? ' (in vehicle)' : ''}${camDistance}`;
                }
                case 'illegal_handling_field': {
                    if (!metadata.fields) return false;

                    const fields = metadata.fields.map(field => field.split(':').shift()).join(', ');

                    return `*${fields}*`;
                }
                case 'high_distance_damage': {
                    if (!metadata.event) return false;

                    const { weaponDamage, weaponHash, distance } = metadata.event;

                    return `--${weaponHash}-- - **${weaponDamage}hp** (${distance.toFixed(2)}m)`;
                }
            }

            return false;
        }
    }
};
</script>
