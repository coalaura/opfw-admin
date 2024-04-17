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

            <button class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" type="button" @click="refresh">
                <span v-if="!isLoading">
                    <i class="fa fa-refresh mr-1"></i>
                    {{ t('global.refresh') }}
                </span>
                <span v-else>
                    <i class="fas fa-spinner animate-spin mr-1"></i>
                    {{ t('global.loading') }}
                </span>
            </button>
        </portal>

        <!-- Table -->
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
                        <tr class="border-t border-gray-300 dark:border-gray-500 hover:bg-gray-200 dark:hover:bg-gray-600" :class="{ 'new-entry': screenshot.new, '!bg-red-500 !bg-opacity-10 opacity-50 hover:opacity-100': screenshot.isBan }" v-for="screenshot in list" :key="screenshot.url">
                            <template v-if="screenshot.isBan">
                                <td class="p-3 py-2 pl-8 text-center mobile:block max-w-56">
                                    <inertia-link class="block px-2 py-1 truncate font-semibold text-center text-sm text-white bg-red-600 rounded dark:bg-red-400" :href="'/players/' + screenshot.license_identifier">
                                        {{ screenshot.player_name }}
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
                                    </inertia-link>
                                </td>
                                <td class="p-3 mobile:block w-32">
                                    {{ screenshot.playtime | humanizeSeconds }}
                                </td>
                                <td class="p-3 mobile:block w-40">
                                    <a :href="screenshot.url" target="_blank" class="text-indigo-600 dark:text-indigo-400">{{ t('screenshot.view', screenshot.url.split(".").pop()) }}</a>
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
                    <h2 class="mt-5">{{ category }}</h2>

                    <table class="w-full">
                        <tr class="text-left hover:bg-gray-100 dark:hover:bg-gray-600" v-for="(value, key) in contents">
                            <td class="font-semibold py-2 px-1 border-t">{{ key }}</td>
                            <td class="py-2 px-1 italic border-t text-sm">{{ value }}</td>
                        </tr>
                    </table>
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
import Layout from './../../Layouts/App';
import VSection from './../../Components/Section';
import Pagination from './../../Components/Pagination';
import Modal from './../../Components/Modal';
import HashResolver from './../../Components/HashResolver';
import MetadataViewer from './../../Components/MetadataViewer';

export default {
    layout: Layout,
    components: {
        Pagination,
        VSection,
        Modal,
        HashResolver,
        MetadataViewer,
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
        }
    },
    data() {
        return {
            isLoading: false,

            showingReasons: false,

            isShowingMetadata: false,
            showingMetadata: null,
            showingMetadataImage: null,

            previousIds: false
        };
    },
    computed: {
        list() {
            return this.screenshots.map(screenshot => {
                screenshot.ban = this.getBanInfo(screenshot.license_identifier);
                screenshot.isBan = !screenshot.url.startsWith("http");

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
                await this.$inertia.replace('/anti_cheat', {
                    preserveState: true,
                    preserveScroll: true,
                    only: ['screenshots', 'banMap', 'links', 'page'],
                });
            } catch (e) { }

            this.isLoading = false;
        },
        formatSecondDiff(sec) {
            return this.$moment.duration(sec, 'seconds').format('d[d] h[h] m[m] s[s]');
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

                    const path = metadata.resource + '/' + file.split('/').pop();

                    return `${path} ${location}`;
                });
            }

            switch (type) {
                case 'illegal_event':
                case 'illegal_server_event':
                case 'honeypot':
                    return metadata.eventName;
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

                    return `${metadata.textEntry} - ${metadata.textEntryValue}` + (metadata.keyboardValue ? `: "*${metadata.keyboardValue}*"` : '');
                case 'fast_movement':
                case 'underground':
                case 'distance_taze':
                    if (metadata.distance === undefined) return false;

                    const closestBlip = metadata.data?.closestBlip,
                        suffix = closestBlip ? ` - *${closestBlip.distance.toFixed(1)}m from ${closestBlip.label}*` : '';

                    return `**${metadata.distance.toFixed(2)}m**${suffix}`;
                case 'bad_screen_word':
                    if (metadata.words === undefined) return false;

                    return metadata.words.join(', ');
                case 'semi_godmode':
                case 'infinite_ammo':
                    return metadata.weaponName;
                case 'illegal_native':
                    if (!metadata.resource || !metadata.native) return false;

                    const args = (metadata.arguments || []).map(arg => {
                        if (typeof arg === 'number') {
                            arg = arg.toFixed(2);
                        } else if (arg && typeof arg === 'object') {
                            if ('x' in arg && 'y' in arg && 'z' in arg && 'w' in arg) {
                                arg = `vector4(${arg.x.toFixed(1)}, ${arg.y.toFixed(1)}, ${arg.z.toFixed(1)}, ${arg.w.toFixed(1)})`;
                            } else if ('x' in arg && 'y' in arg && 'z' in arg) {
                                arg = `vector3(${arg.x.toFixed(1)}, ${arg.y.toFixed(1)}, ${arg.z.toFixed(1)})`;
                            } else if ('x' in arg && 'y' in arg) {
                                arg = `vector2(${arg.x.toFixed(1)}, ${arg.y.toFixed(1)})`;
                            }
                        }

                        return `--${arg}--`;
                    }).join(', ');

                    const native = metadata.native.startsWith('0x') ? `[${metadata.native}](https://docs.fivem.net/natives/?_0x${metadata.native.substr(2).toUpperCase()})` : metadata.native;

                    return `${trace(metadata)} - **${native}** (${args})`;
                case 'illegal_global':
                    if (!metadata.resource || !metadata.variable) return false;

                    return `${trace(metadata)} - **${metadata.variable}**`;
                case 'illegal_damage':
                    if (!metadata.type || metadata.weaponType === undefined || metadata.distance === undefined || metadata.damage === undefined) return false;

                    let dmg = metadata.damage + 'hp';

                    if (metadata.type === 'high_damage' && metadata.maxAllowed !== undefined) {
                        dmg = `${metadata.maxAllowed}+${metadata.damage - metadata.maxAllowed}hp`;
                    }

                    return `${metadata.type}: --${metadata.weaponType}-- - **${dmg}** (${metadata.distance.toFixed(2)}m)`;
                case 'illegal_vehicle_modifier':
                    if (!metadata.modifierName || metadata.actualValue === undefined || metadata.expectedValue === undefined) return false;

                    return `${metadata.modifierName}(**${metadata.actualValue}**) - expected **${metadata.expectedValue}**`;
                case 'spawned_object':
                case 'illegal_ped_spawn':
                case 'illegal_vehicle_spawn':
                    if (!metadata.distance || !metadata.entity) return false;

                    return `${metadata.script ? metadata.script + ':' : ''} --${metadata.entity.model}-- (${metadata.distance.toFixed(2)}m)` + (metadata.isAddon ? ' (**addon**)' : '');
                case 'illegal_local_vehicle':
                    if (!metadata.model) return false;

                    return `${metadata.script ? metadata.script + ':' : ''} --${metadata.model}--` + (metadata.isAddon ? ' (**addon**)' : '');
                case 'invalid_health':
                    if (metadata.health === undefined || metadata.maxHealth === undefined || metadata.armor === undefined || metadata.maxArmor === undefined) return false;

                    return `${metadata.health}/${metadata.maxHealth}hp - ${metadata.armor}/${metadata.maxArmor}ap`;
                case 'ped_change':
                    if (!Array.isArray(metadata.changes)) return false;

                    const changed = metadata.changes.length;

                    const copied = metadata.copiedPlayers && metadata.copiedPlayers.length > 0 ? '- Copied ' + metadata.copiedPlayers.map(p => {
                        const regex = / \[\d+\] \((.+?)\)$/gm,
                            match = regex.exec(p),
                            license = match ? match[1] : '#';

                        const name = p.replace(regex, '');

                        return `*[${name}](/players/${license})*`;
                    }).join(', ') : '';

                    return `**${changed}** propert${changed === 1 ? 'y' : 'ies'} changed ${copied}`;
                case 'advanced_noclip':
                    if (metadata.aboveGround === undefined) return false;

                    const speed = metadata.playerPed?.speed?.toFixed(2) || '0';

                    return `**${metadata.aboveGround.toFixed(2)}m** AGL @ **${speed}m/s**`;
                case 'freecam_detected':
                case 'illegal_freeze':
                    if (metadata.playerPed === undefined) return false;

                    const flags = metadata.playerPed.flags.join(', ') || 'no flags';

                    return `${flags}` + (metadata.playerPed.inVehicle ? ' (in vehicle)' : '');
            }

            return false;
        }
    }
};
</script>
