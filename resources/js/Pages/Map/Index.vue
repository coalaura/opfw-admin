<template>
    <div>
        <portal to="title">
            <div class="mb-6">
                <h1 class="dark:text-white flex items-center gap-2">
                    <i class="mr-3 fas fa-unlock-alt" :title="perm.restriction(perm.PERM_LIVEMAP)"></i>

                    <span id="map_title">{{ t('map.title') }}</span>

                    <select class="inline-block w-20 h-8 ml-4 px-2 py-1 text-sm bg-gray-200 dark:bg-gray-600 border rounded" id="server">
                        <option v-for="server in servers" :key="server" :value="server">{{ server }}</option>
                    </select>
                    <select class="inline-block w-36 h-8 ml-2 px-2 py-1 text-sm bg-gray-200 dark:bg-gray-600 border rounded" v-model="selectedInstance" v-if="Object.values(container.instances).length > 0">
                        <option v-for="instance in container.instances" :key="instance.id" :value="instance.id">
                            {{ instance.id === container.mainInstance ? t('map.main_instance') : `${instance.name} - ${instance.count}` }}
                        </option>
                    </select>
                </h1>

                <p v-if="!isTimestampShowing && !isHistoricShowing" class="mt-3">
                    <span v-html="data" class="block"></span>
                    <span class="block text-xxs text-muted dark:text-dark-muted mt-0 leading-3" v-if="lastConnectionError">
                        {{ lastConnectionError }}
                    </span>
                    <span class="block text-xs text-muted dark:text-dark-muted leading-3 mt-2" v-if="activeViewers.length > 0">
                        <b>{{ t('map.current_viewers') }}: </b>
                        <span v-html="formatViewers()"></span>
                    </span>
                </p>
            </div>
        </portal>

        <portal to="actions">
            <div class="flex gap-2">
                <!-- Show Timestamp -->
                <button class="p-2 w-11 text-center font-semibold text-white rounded bg-blue-600 dark:bg-blue-500" :title="t('map.timestamp_title')" @click="isTimestamp = true" v-if="this.perm.check(this.perm.PERM_ADVANCED)">
                    <i class="fas fa-vial"></i>
                </button>

                <!-- Show Historic -->
                <button class="p-2 w-11 text-center font-semibold text-white rounded bg-blue-600 dark:bg-blue-500" :title="t('map.historic_title')" @click="showHistoric()" v-if="this.perm.check(this.perm.PERM_ADVANCED)">
                    <i class="fas fa-map"></i>
                </button>

                <!-- Play/Pause -->
                <button class="p-2 w-11 text-center font-semibold text-white rounded bg-yellow-600 dark:bg-yellow-500" :title="t('map.pause')" @click="isPaused = true" v-if="!isPaused && !isTimestampShowing && !isHistoricShowing">
                    <i class="fas fa-pause"></i>
                </button>
                <button class="p-2 w-11 text-center font-semibold text-white rounded bg-green-600 dark:bg-green-500" :title="t('map.play')" @click="isPaused = false" v-if="isPaused && !isTimestampShowing && !isHistoricShowing">
                    <i class="fas fa-play"></i>
                </button>
            </div>
        </portal>

        <!-- Historic Data -->
        <div class="fixed bg-black bg-opacity-70 top-0 left-0 right-0 bottom-0 z-2k" v-if="isHistoric">
            <div class="shadow-xl absolute bg-gray-100 dark:bg-gray-600 text-black dark:text-white left-2/4 top-2/4 -translate-x-2/4 -translate-y-2/4 transform p-6 rounded w-alert">
                <h3 class="mb-2">
                    {{ t('map.historic_title') }}
                </h3>

                <!-- license Identifier -->
                <div class="w-full p-3 flex justify-between px-0">
                    <label class="mr-4 block w-1/3 pt-2 font-bold" for="historic_license">
                        {{ t('map.historic_license') }}
                    </label>
                    <div class="flex gap-3 w-2/3">
                        <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="historic_license" v-model="form.historic_license" @input="checkHistoricLicense" />

                        <button v-if="historicValidLicense" :title="t('map.historic_resolve')" class="p-2 px-3 rounded hover:bg-gray-200 dark:hover:bg-gray-500 dark:bg-gray-500" @click="resolveHistoricLicenseDates">
                            <i class="fas fa-band-aid"></i>
                        </button>
                    </div>
                </div>

                <!-- From -->
                <div class="w-full p-3 flex justify-between px-0" v-if="historicValidLicense">
                    <label class="mr-4 block w-1/3 pt-2 font-bold" for="historic_date_from">
                        {{ t('map.historic_from') }}
                    </label>
                    <input class="w-1/3 px-4 py-2 mr-1 bg-gray-200 dark:bg-gray-600 border rounded" type="date" step="any" id="historic_date_from" v-model="form.historic_from_date" />
                    <input class="w-1/3 px-4 py-2 ml-1 bg-gray-200 dark:bg-gray-600 border rounded" type="time" step="any" id="historic_time_from" v-model="form.historic_from_time" />
                </div>

                <!-- Till -->
                <div class="w-full p-3 flex justify-between px-0" v-if="historicValidLicense">
                    <label class="mr-4 block w-1/3 pt-2 font-bold" for="historic_date_till">
                        {{ t('map.historic_till') }}
                    </label>
                    <input class="w-1/3 px-4 py-2 mr-1 bg-gray-200 dark:bg-gray-600 border rounded" type="date" step="any" id="historic_date_till" v-model="form.historic_till_date" />
                    <input class="w-1/3 px-4 py-2 ml-1 bg-gray-200 dark:bg-gray-600 border rounded" type="time" step="any" id="historic_time_till" v-model="form.historic_till_time" />
                </div>

                <p>
                    {{ t('map.historic_note') }}
                </p>

                <!-- Buttons -->
                <div class="flex items-center mt-2">
                    <button class="px-5 py-2 font-semibold text-white bg-success dark:bg-dark-success rounded mr-2" v-if="historicValidLicense" @click="showHistory">
                        <i class="mr-1 fas fa-plus"></i>
                        {{ t('global.confirm') }}
                    </button>
                    <button class="px-5 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-500 dark:bg-gray-500" @click="isHistoric = false">
                        {{ t('global.cancel') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="fixed bg-black bg-opacity-70 top-0 left-0 right-0 bottom-0 z-2k" v-if="isTimestamp">
            <div class="shadow-xl absolute bg-gray-100 dark:bg-gray-600 text-black dark:text-white left-2/4 top-2/4 -translate-x-2/4 -translate-y-2/4 transform p-6 rounded w-alert">
                <h3 class="mb-2">
                    {{ t('map.timestamp_title') }}
                </h3>

                <!-- From -->
                <div class="w-full p-3 flex justify-between px-0">
                    <label class="mr-4 block w-1/3 pt-2 font-bold" for="historic_license">
                        {{ t('map.timestamp_date') }}
                    </label>
                    <input class="w-2/3 px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" v-model="form.timestamp" />
                </div>

                <!-- Buttons -->
                <div class="flex items-center mt-2">
                    <button class="px-5 py-2 font-semibold text-white bg-success dark:bg-dark-success rounded mr-2" @click="showTimestamp">
                        <i class="mr-1 fas fa-plus"></i>
                        {{ t('global.confirm') }}
                    </button>
                    <button class="px-5 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-500 dark:bg-gray-500" @click="isTimestamp = false">
                        {{ t('global.cancel') }}
                    </button>
                </div>
            </div>
        </div>

        <template>
            <div class="-mt-10 flex flex-wrap">
                <div class="w-full">
                    <div v-if="historyRange.view" class="mb-3">
                        <div class="flex">
                            <button class="px-2 py-1 mr-2 font-semibold text-white rounded bg-primary dark:bg-dark-primary" @click="historyRangeButton(-20)">-20s
                            </button>
                            <button class="px-2 py-1 mr-2 font-semibold text-white rounded bg-primary dark:bg-dark-primary" @click="historyRangeButton(-5)">-5s
                            </button>
                            <button class="px-2 py-1 mr-2 font-semibold text-white rounded bg-primary dark:bg-dark-primary" @click="historyRangeButton(-1)">-1s
                            </button>

                            <input type="range" :min="historyRange.min" :max="historyRange.max" value="0" @change="historyRangeChange" @input="historyRangeChange" id="range-slider" class="w-full px-2 py-1 range bg-transparent" />

                            <button class="px-2 py-1 ml-2 font-semibold text-white rounded bg-primary dark:bg-dark-primary" @click="historyRangeButton(1)">+1s
                            </button>
                            <button class="px-2 py-1 ml-2 font-semibold text-white rounded bg-primary dark:bg-dark-primary" @click="historyRangeButton(5)">+5s
                            </button>
                            <button class="px-2 py-1 ml-2 font-semibold text-white rounded bg-primary dark:bg-dark-primary" @click="historyRangeButton(20)">+20s
                            </button>
                        </div>
                        <p class="text-center">{{ historyRange.val }}</p>
                        <p class="text-center text-sm" v-html="historicDetails"></p>
                    </div>

                    <div class="relative w-full">
                        <div class="w-full flex gap-3">
                            <div id="map" class="w-full relative h-max"></div>
                        </div>

                        <input v-if="!isTimestampShowing && !isHistoricShowing" type="number" class="placeholder absolute z-1k leaflet-tl ml-12 w-16 block px-2 font-base font-semibold" @input="updateTrackingInfo" :placeholder="t('map.track_placeholder')" min="0" max="65536" v-model="trackServerId" :class="trackingValid ? 'text-green-700 bg-green-100' : 'text-red-700 bg-red-200'" />

                        <button class="absolute z-1k leaflet-tl ml-29 text-white bg-rose-700 hover:bg-rose-800 px-2 font-base" v-if="trackServerId" @click="track('')">
                            <i class="fas fa-trash"></i>
                        </button>

                        <div class="absolute z-1k inset-0 text-lg font-semibold backdrop-blur backdrop-filter justify-center items-center flex" v-if="loadingScreenStatus">
                            <i class="fas fa-spinner animate-spin mr-3"></i> {{ loadingScreenStatus }}
                        </div>
                    </div>

                    <!-- Map Legend -->
                    <div class="my-2 flex flex-wrap justify-between text-xs w-full">
                        <div class="mx-2">
                            <img :src="'/images/icons/circle.png'" class="w-map-icon inline-block" alt="on foot" />
                            <span class="leading-map-icon">on foot</span>
                        </div>
                        <div class="mx-2">
                            <img :src="'/images/icons/circle_green.png'" class="w-map-icon inline-block" alt="invisible" />
                            <span class="leading-map-icon">invisible</span>
                        </div>
                        <div class="mx-2">
                            <img :src="'/images/icons/circle_red.png'" class="w-map-icon inline-block" alt="passenger" />
                            <span class="leading-map-icon">passenger</span>
                        </div>
                        <div class="mx-2">
                            <img :src="'/images/icons/skull.png'" class="w-map-icon inline-block" alt="dead" />
                            <span class="leading-map-icon">dead</span>
                        </div>
                        <div class="mx-2">
                            <img :src="'/images/icons/skull_red.png'" class="w-map-icon inline-block" alt="dead passenger" />
                            <span class="leading-map-icon">dead (passenger)</span>
                        </div>
                        <div class="mx-2">
                            <img :src="'/images/icons/circle_police.png'" class="w-map-icon inline-block" alt="police" />
                            <span class="leading-map-icon">police</span>
                        </div>
                        <div class="mx-2">
                            <img :src="'/images/icons/circle_ems.png'" class="w-map-icon inline-block" alt="ems" />
                            <span class="leading-map-icon">ems</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap">
                    <!-- Invisible Players -->
                    <div v-if="invisiblePeople.length > 0 && !isTimestampShowing && !isHistoricShowing" class="pt-4 mr-4 font-medium">
                        <h3 class="mb-2">{{ t('map.invisible_title') }}</h3>
                        <table class="text-sm font-mono">
                            <tr v-for="(player, x) in invisiblePeople" :key="x">
                                <td class="pr-2">
                                    <a class="dark:text-red-400 text-red-600" target="_blank" :href="'/players/' + player.license">{{ player.name }}</a>
                                </td>
                                <td class="pr-2 dark:text-red-400 text-red-600">
                                    ({{ player.source }})
                                </td>
                                <td class="pr-2">
                                    {{ t('map.invisible') }}
                                </td>
                                <td>
                                    <a class="track-cid dark:text-red-400 text-red-600" href="#" :data-trackid="player.source" data-popup="true">
                                        {{ t('map.short.track') }}
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="flex flex-wrap gap-5 mt-3" v-if="!isTimestampShowing && !isHistoricShowing">
                    <simple-player-list :title="t('map.staff_online')" :players="container.staff" color="text-map-staff" :usePlayerName="true" @track="track"></simple-player-list>

                    <simple-player-list :title="t('map.duty_list_pd')" :players="container.on_duty.pd" color="text-map-police" @track="track"></simple-player-list>
                    <simple-player-list :title="t('map.duty_list_ems')" :players="container.on_duty.ems" color="text-map-ems" @track="track"></simple-player-list>
                </div>
            </div>
        </template>

        <modal :show.sync="viewingUnloadedPlayerList">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('map.unloaded_players') }}
                </h1>
            </template>

            <template #default>
                <table class="whitespace-nowrap">
                    <tr class="sticky top-0 bg-gray-300 dark:bg-gray-700 no-alpha">
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('map.source') }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('map.name') }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left">&nbsp;</th>
                    </tr>

                    <tr v-for="player in container.unloadedPlayers" class="border-t border-gray-500">
                        <td class="px-2 py-0.5">{{ player.source }}</td>
                        <td class="px-2 py-0.5">
                            <a :href="'/players/' + player.license" target="_blank" class="dark:text-blue-300 text-blue-500 no-underline">
                                {{ player.name }}
                            </a>
                        </td>
                        <td class="px-2 py-0.5">
                            <div class="flex gap-1">
                                <i class="fas fa-wheelchair dark:text-lime-400 text-lime-600" :title="t('global.staff')" v-if="player.isStaff"></i>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="container.unloadedPlayers.length === 0" class="border-t border-gray-500">
                        <td class="px-2 py-0.5" colspan="3">
                            {{ t('map.no_unloaded_players') }}
                        </td>
                    </tr>
                </table>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="viewingUnloadedPlayerList = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>
    </div>
</template>

<script>
import "leaflet.markercluster/dist/MarkerCluster.css";
import "leaflet.markercluster/dist/MarkerCluster.Default.css";

import L from "leaflet";
import { GestureHandling } from "leaflet-gesture-handling";
import "leaflet-rotatedmarker";
import "leaflet-fullscreen";
import "leaflet.markercluster";

import Layout from './../../Layouts/App.vue';
import VSection from './../../Components/Section.vue';
import SimplePlayerList from './../../Components/Map/SimplePlayerList.vue';
import Modal from './../../Components/Modal.vue';

import PlayerContainer from './PlayerContainer.js';
import Player from './Player.js';
import Vector3 from "./Vector3.js";
import Bounds from './map.config.js';
import { mapNumber } from './helper.js';

((global) => {
    const MarkerMixin = {
        _updateZIndex: function (offset) {
            this._icon.style.zIndex = this.options.forceZIndex ? (this.options.forceZIndex + (this.options.zIndexOffset || 0)) : (this._zIndex + offset);
        },
        setForceZIndex: function (forceZIndex) {
            this.options.forceZIndex = forceZIndex ? forceZIndex : null;
        }
    };
    if (global) global.include(MarkerMixin);
})(L.Marker);

export default {
    layout: Layout,
    components: {
        VSection,
        SimplePlayerList,
        Modal,
    },
    props: {
        servers: {
            type: Array,
            required: true
        },
        activeServer: {
            type: String,
            required: true
        },
        staff: {
            type: Array,
            required: true
        },
        staffMap: {
            type: Array,
            required: true
        },
        blips: {
            type: Array,
            required: true
        },
        marker: {
            type: Array
        },
        token: {
            type: String,
            required: true
        },
        myself: {
            type: String,
            required: true
        },
        cluster: {
            type: String,
            required: true
        },
    },
    data() {
        return {
            map: null,
            container: new PlayerContainer(this.staff),
            markers: {},
            data: this.t('map.loading'),
            connection: false,
            isPaused: false,
            invisiblePeople: [],
            isDragging: false,
            form: {
                timestamp: Math.floor(Date.now() / 1000),

                historic_license: '',
                historic_from_date: '',
                historic_from_time: '',
                historic_till_date: '',
                historic_till_time: ''
            },
            layers: {
                "Players": L.layerGroup(),
                "Emergency Vehicles": L.layerGroup(),
                "Vehicles": L.layerGroup(),
                "Blips": L.layerGroup(),
            },

            trackServerId: "",
            trackingValid: false,

            lastConnectionError: null,
            characters: {},
            cayoCalibrationMode: false, // Set this to true to recalibrate the cayo perico map

            heatmapLayers: [],
            historyMarker: null,
            loadingScreenStatus: null,
            historicValidLicense: false,

            isTimestamp: false,
            isHistoric: false,

            isTimestampShowing: false,
            isHistoricShowing: false,

            historicDetails: '',

            historyRange: {
                view: false,
                min: 0,
                max: 1,
                val: 0,
                data: [],

                minAltitude: 0,
                maxAltitude: 0
            },

            activeViewers: [],

            selectedInstance: false,
            viewingUnloadedPlayerList: false
        };
    },
    methods: {
        checkHistoricLicense() {
            const license = this.form.historic_license;

            if (!license || !license.match(/^license:[a-f0-9]{40}$/gm)) {
                this.historicValidLicense = false;

                return;
            }

            this.historicValidLicense = true;
        },
        updateTrackingInfo() {
            const source = parseInt(this.trackServerId);

            this.trackingValid = Number.isInteger(source) && Object.values(this.container.players).find(player => player.player.source === source);
        },
        track(source) {
            this.trackServerId = source;

            this.updateTrackingInfo();
        },
        async resolveHistoricLicenseDates() {
            try {
                const data = await _get(`/players/${this.form.historic_license}/ban`);

                if (data?.data && data.status) {
                    // Round to next minute
                    const date = dayjs((data.data.timestamp + 60) * 1000);

                    this.form.historic_till_date = date.format("YYYY-MM-DD");
                    this.form.historic_till_time = date.format("HH:mm");

                    date.subtract(20, 'minutes');

                    this.form.historic_from_date = date.format("YYYY-MM-DD");
                    this.form.historic_from_time = date.format("HH:mm");
                }
            } catch (e) {
                console.error(e);
            }
        },
        formatViewers() {
            const viewers = this.activeViewers.filter(v => !this.isFake(v));

            if (!viewers || viewers.length === 0) {
                return '-';
            }

            return viewers.map(v => this.getStaffName(v)).join(', ');
        },
        getStaffName(license) {
            let player_name = license;

            for (let x = 0; x < this.staffMap.length; x++) {
                const staff = this.staffMap[x];

                if (staff.license_identifier === license) {
                    player_name = staff.player_name;
                    break;
                }
            }

            const cls = this.container.players && license in this.container.players ? 'dark:text-green-300 text-green-500' : 'dark:text-blue-300 text-blue-500';
            const title = this.container.players && license in this.container.players ? this.t('map.viewer_in_server') : this.t('map.viewer_not_server');

            return `<a href="/players/${license}" target="_blank" title="${title}" class="!no-underline ${cls}">${player_name}</a>`;
        },
        showHistoric() {
            const fromDate = dayjs().subtract(1, 'hours');
            const tillDate = dayjs().add(1, 'minutes');

            if (!this.form.historic_from_date) {
                this.form.historic_from_date = fromDate.format("YYYY-MM-DD");
            }
            if (!this.form.historic_till_date) {
                this.form.historic_till_date = tillDate.format("YYYY-MM-DD");
            }

            if (!this.form.historic_from_time) {
                this.form.historic_from_time = fromDate.format("HH:mm");
            }
            if (!this.form.historic_till_time) {
                this.form.historic_till_time = tillDate.format("HH:mm");
            }

            this.isHistoric = true;
        },
        isFake(license) {
            const player = this.container.get(license);

            return player?.player?.isFake;
        },
        historyRangeButton(move) {
            if (this.historyRange && this.historyMarker) {
                const newVal = Number.parseInt($('#range-slider').val()) + move;

                $('#range-slider').val(Math.min(this.historyRange.max, Math.max(this.historyRange.min, newVal)));

                this.historyRangeChange();
            }
        },
        historyRangeChange(timestamp) {
            if (this.historyRange && this.historyMarker) {
                const val = Number.parseInt(typeof timestamp === "number" ? timestamp : $('#range-slider').val());

                const pos = this.historyRange.data[val];

                const timezone = new Date(val * 1000).toLocaleDateString('en-US', {
                    day: '2-digit',
                    timeZoneName: 'short',
                }).slice(4);

                let icon = "circle";
                let label = `${dayjs.unix(val).format("MM/DD/YYYY - h:mm:ss")} ${timezone} (${val})`;

                const flags = [
                    pos?.i ? 'invisible' : false,
                    pos?.c ? 'invincible' : false,
                    pos?.f ? 'frozen' : false,
                    pos?.d ? 'dead' : false
                ].filter(flag => flag).join(", ");

                const damageIcons = Player.getDamageIcons(Player.getDamageFlags(pos?.df));

                const speed = pos && "s" in pos ? `${(pos.s * 2.236936).toFixed(1)}mph` : "N/A";

                this.historicDetails = `Flags: ${flags ? flags : 'N/A'} - Altitude: ${pos ? `${pos.z.toFixed(1)}m` : "N/A"} - Speed: ${speed} <div class="flex gap-1 justify-center">${damageIcons.join("")}</div>`;

                if (pos && !pos.missing) {
                    const coords = Vector3.fromGameCoords(Number.parseInt(pos.x), Number.parseInt(pos.y), 0).toMap();

                    this.historyMarker.setLatLng([coords.lat, coords.lng]);

                    if (pos.i) {
                        icon = "circle_green";
                    }
                } else {
                    label += ' [no-data]';

                    icon = "circle_red";
                }

                this.historyRange.val = label;

                this.historyMarker.setIcon(new L.Icon(
                    {
                        iconUrl: `/images/icons/${icon}.png`,
                        iconSize: [20, 20]
                    }
                ));
            }
        },
        async showTimestamp() {
            const timestamp = this.form.timestamp;

            if (timestamp && timestamp > 0 && timestamp < Date.now() / 1000) {
                this.isTimestamp = false;

                this.isHistoricShowing = false;
                this.isTimestampShowing = true;

                this.trackServerId = "";

                await this.renderTimestamp(timestamp);
            } else {
                alert('Invalid timestamp');
            }
        },
        async showHistory() {
            const fromUnix = dayjs(`${this.form.historic_from_date} ${this.form.historic_from_time}`).unix();
            const tillUnix = dayjs(`${this.form.historic_till_date} ${this.form.historic_till_time}`).unix();

            if (fromUnix && tillUnix) {
                if (this.form.historic_license || !this.form.historic_license.startsWith('license:')) {
                    this.isHistoric = false;

                    this.isHistoricShowing = true;
                    this.isTimestampShowing = false;

                    this.trackServerId = "";

                    await this.renderHistory(this.form.historic_license.replace('license:', ''), fromUnix, tillUnix);
                } else {
                    alert('Invalid license identifier');
                }
            } else {
                alert('Invalid from / till');
            }
        },
        async renderHistory(license, from, till) {
            if (this.loadingScreenStatus) return;

            this.loadingScreenStatus = this.t('map.historic_fetch');

            const server = this.activeServer;
            const history = await this.loadHistory(server, license, from, till);

            this.loadingScreenStatus = this.t('map.historic_render');

            if (this.heatmapLayers) {
                for (let x = 0; x < this.heatmapLayers.length; x++) {
                    this.map.removeLayer(this.heatmapLayers[x]);
                }

                if (this.historyMarker) {
                    this.map.removeLayer(this.historyMarker);
                }

                this.heatmapLayers = [];
            }

            this.historyRange.view = false;

            if (history) {
                $('.leaflet-control-layers-selector').each(function () {
                    if ($(this).prop('checked')) {
                        $(this).trigger('click');
                    }
                });

                const timestamps = Object.keys(history);

                const first = timestamps[0];
                const last = timestamps[timestamps.length - 1];

                const addPolyline = (coords) => {
                    const line = L.polyline(coords, { color: '#3380f3' });

                    line.on('click', (e) => {
                        const latlng = e.latlng;

                        let closestDistance = 1000;
                        let closest = false;

                        Object.entries(history).forEach(entrySet => {
                            const coords = Vector3.fromGameCoords(Number.parseInt(entrySet[1].x), Number.parseInt(entrySet[1].y), 0).toMap();

                            const dst = distance(coords.lat, coords.lng, latlng.lat, latlng.lng);

                            if (dst < closestDistance) {
                                closestDistance = dst;
                                closest = entrySet[0];
                            }
                        });

                        $('#range-slider').val(closest);

                        this.historyRangeChange();
                    });

                    line.addTo(this.map);

                    this.heatmapLayers.push(line);
                }

                let latlngs = [];
                let lastEntryNull = 0;

                for (let x = first; x <= last; x++) {
                    let pos = history[x];

                    if (!pos) {
                        pos = history[x - 1];

                        if (!pos) {
                            pos = history[x + 1];
                        }
                    }

                    if (pos) {
                        if (lastEntryNull >= 10 && pos.i && pos.c && pos.f) {
                            continue;
                        }

                        const coords = Vector3.fromGameCoords(pos.x, pos.y, 0).toMap();

                        latlngs.push([coords.lat, coords.lng]);

                        lastEntryNull = 0;
                    } else {
                        if (latlngs.length > 0) {
                            addPolyline(latlngs);

                            latlngs = [];
                        }

                        lastEntryNull++;
                    }
                }

                if (latlngs.length > 0) {
                    addPolyline(latlngs);
                }

                function distance(x1, y1, x2, y2) {
                    const xDiff = x1 - x2;
                    const yDiff = y1 - y2;

                    return Math.abs(Math.sqrt(xDiff * xDiff + yDiff * yDiff));
                }

                this.historyMarker = L.marker(latlngs[0], {});

                this.historyMarker.setIcon(new L.Icon(
                    {
                        iconUrl: '/images/icons/circle.png',
                        iconSize: [20, 20]
                    }
                ));

                const temp = Object.values(history).map(entry => entry.z);

                this.historyRange.minAltitude = Math.min(...temp);
                this.historyRange.maxAltitude = Math.max(...temp);

                this.historyRange.val = `${(new Date(timestamps[0] * 1000)).toGMTString()} (${timestamps[0]})`;
                this.historyRange.min = timestamps[0];
                this.historyRange.max = timestamps[timestamps.length - 1];

                this.historyRange.data = history;

                this.historyRange.view = true;

                this.historyMarker.addTo(this.map);

                //this.map.fitBounds(this.heatmapLayer.getBounds());

                this.historyRangeChange(Number.parseInt(timestamps[0]));
            }

            this.loadingScreenStatus = null;
        },
        async loadHistory(server, license, from, till) {
            this.loadingScreenStatus = this.t('map.historic_fetch');

            try {
                const result = await _get(`${this.resolveSocketHost("http")}/socket/${server}/history/${license}/${from}/${till}`, {
                    token: this.token
                });

                this.loadingScreenStatus = this.t('map.historic_parse');

                if (result?.status) {
                    const data = result.data;

                    const keys = Object.keys(data);
                    const first = keys[0];
                    const last = keys[keys.length - 1];

                    let lastEntry = data[first];

                    for (let x = first; x <= last; x++) {
                        if (data[x]) {
                            lastEntry = data[x];
                        } else {
                            data[x] = JSON.parse(JSON.stringify(lastEntry));

                            data[x].missing = true;
                        }
                    }

                    return data;
                }if (!result.status) {
                    console.error(result.error);

                    alert(result.error);

                    window.location.reload();
                }
            } catch (e) {
                console.error(e);
            }

            return null;
        },
        async loadPlayerNames(licenses) {
            try {
                const result = await _post('/map/names', JSON.stringify({
                    licenses: licenses
                }));

                if (result?.status) {
                    return result.data;
                } else if (!result.status) {
                    console.error(result.error);
                }
            } catch (e) {
                console.error(e);
            }

            return null;
        },
        formatCharacterFlags(pFlags) {
            pFlags = pFlags ? pFlags : 0;

            const generic = "text-gray-800 hover:text-blue-600 transition-colors";

            return [
                // !!(pFlags & 64) spawned
                (pFlags & 32) ? `<i class="fas fa-ice-cream ${generic}" title="frozen"></i>` : false,
                (pFlags & 16) ? `<i class="fas fa-fist-raised ${generic}" title="invincible"></i>` : false,
                (pFlags & 8) ? `<i class="fas fa-eye-slash ${generic}" title="invisible"></i>` : false,
                (pFlags & 4) ? `<i class="fas fa-egg ${generic}" title="in_shell"></i>` : false,
                (pFlags & 2) ? `<i class="fas fa-truck-loading ${generic}" title="in trunk"></i>` : false,
                (pFlags & 1) ? `<i class="fas fa-skull-crossbones ${generic}" title="dead"></i>` : false,
            ].filter(Boolean).join("");
        },
        formatUserFlags(pFlags) {
            pFlags = pFlags ? pFlags : 0;

            const generic = "text-gray-800 hover:text-blue-600 transition-colors";

            return [
                (pFlags & 16) ? `<i class="fas fa-newspaper ${generic}" title="in queue"></i>` : false,
                (pFlags & 8) ? `<i class="fas fa-camera-retro ${generic}" title="modified camera coords"></i>` : false,
                (pFlags & 4) ? `<i class="fas fa-gamepad ${generic}" title="in minigame"></i>` : false,
                // !!(pFlags & 2) fakeDisconnected
                // !!(pFlags & 1) identityOverride
            ].filter(Boolean).join("");
        },
        async renderTimestamp(timestamp) {
            this.historyRange.view = false;

            if (this.loadingScreenStatus) return;

            this.loadingScreenStatus = this.t('map.timestamp_fetch');

            const server = this.activeServer;
            const players = await this.loadTimestamp(server, timestamp);

            if (players) {
                this.loadingScreenStatus = this.t('map.timestamp_load_names');

                const licenses = players.map(player => player.license);
                const playerInfos = (await this.loadPlayerNames(licenses)) || {
                    players: [],
                    characters: [],
                };

                this.loadingScreenStatus = this.t('map.timestamp_render');

                if (this.heatmapLayers) {
                    for (let x = 0; x < this.heatmapLayers.length; x++) {
                        this.map.removeLayer(this.heatmapLayers[x]);
                    }

                    if (this.historyMarker) {
                        this.map.removeLayer(this.historyMarker);
                    }

                    this.heatmapLayers = [];
                }

                $('.leaflet-control-layers-selector').each(function () {
                    if ($(this).prop('checked')) {
                        $(this).trigger('click');
                    }
                });

                const cluster = L.markerClusterGroup({
                    maxClusterRadius: 10
                });

                this.heatmapLayers.push(cluster);

                cluster.addTo(this.map);

                for (let x = 0; x < players.length; x++) {
                    const player = players[x];
                    const location = Vector3.fromGameCoords(player.x, player.y, 0.0);

                    const marker = L.marker(location.toMap(),
                        {
                            icon: new L.Icon(
                                {
                                    iconUrl: `/images/icons/${player.i ? 'circle_green' : 'circle'}.png`,
                                    iconSize: [17, 17]
                                }
                            ),
                            forceZIndex: 99
                        }
                    );

                    const markerHeading = mapNumber(-player.heading, -180, 180, 0, 360) - 180;

                    marker.setRotationAngle(markerHeading);

                    const playerName = playerInfos.players[player.license]?.trim() || player.license.substring(8);
                    const characterName = playerInfos.characters[player.cid]?.trim() || false;
                    const speed = player.speed && player.speed > 0.45 ? `${Math.floor(player.speed * 2.236936)}mph` : false;
                    const heading = Math.round(player.heading < 0 ? -player.heading : 360 - player.heading);
                    const altitude = Math.round(player.z);
                    const characterFlags = this.formatCharacterFlags(player.characterFlags);
                    const userFlags = this.formatUserFlags(player.userFlags);

                    const infos = [
                        `<div>Heading: <i>${heading}&deg;</i></div>`,
                        altitude ? `<div>Altitude: <i>${altitude}m</i></div>` : false,
                        speed ? `<div>Speed: <i>${speed}</i></div>` : false,
                    ].filter(Boolean).join("");

                    const flags = [
                        characterFlags ? `<div class="flex gap-2">${characterFlags}</div>` : false,
                        userFlags ? `<div class="flex gap-2">${userFlags}</div>` : false
                    ].filter(Boolean).join("");

                    const damageIcons = Player.getDamageIcons(Player.getDamageFlags(player.df));

                    const popup = `${characterName ? `<a href="/players/${player.license}/characters/${player.cid}" target="_blank" class="block"><i class="fas fa-street-view" title="Character"></i> ${characterName}</a>` : ""}<a href="/players/${player.license}" target="_blank" class="block"><i class="fas fa-user-circle" title="Player"></i> ${playerName}</a><div class="mt-1 pt-1 border-t border-gray-300 flex flex-col">${infos}</div>${flags ? `<div class="flex flex-col gap-1 mt-1 pt-1 border-t border-gray-300">${flags}</div>` : ""}${damageIcons.length ? `<span class="flex gap-1 mt-1 border-t border-gray-700 pt-2">${damageIcons.join("")}</span>` : ""}`;

                    marker.bindPopup(popup, {
                        autoPan: false
                    });

                    cluster.addLayer(marker);
                }
            }

            this.loadingScreenStatus = null;
        },
        async loadTimestamp(server, timestamp) {
            try {
                const result = await _get(`${this.resolveSocketHost("http")}/socket/${server}/timestamp/${timestamp}`, {
                    token: this.token
                });

                this.loadingScreenStatus = this.t('map.timestamp_parse');

                if (result?.status) {
                    const players = [];

                    for (const license in result.data) {
                        if (Object.hasOwn(Object, license)) continue;

                        const coords = result.data[license];

                        players.push({
                            license: `license:${license}`,
                            cid: coords._,
                            heading: coords.w,
                            speed: coords.s,
                            x: coords.x,
                            y: coords.y,
                            z: coords.z,

                            characterFlags: coords.cf,
                            userFlags: coords.uf
                        });
                    }

                    return players;
                } else if (!result?.status) {
                    alert(result.error);

                    window.location.reload();
                }
            } catch (e) {
                console.error(e);
            }

            return null;
        },
        async initializeMap() {
            if (this.connection) return;

            try {
                const pause = () => {
                    this.connection?.emit?.("pause", document.visibilityState === "hidden");
                };

                let lastTrackedId = "";

                this.connection = this.createSocket("world", {
                    onData: async data => {
                        try {
                            const trackedId = parseInt(this.trackServerId) || false;

                            await this.renderMapData(data, trackedId, trackedId !== lastTrackedId);

                            lastTrackedId = trackedId;
                        } catch (e) {
                            console.error('Failed to parse socket message ', e);
                        }
                    },
                    onNoData: () => {
                        this.data = this.t('map.waiting_startup', this.activeServer);
                    },
                    onConnect: () => {
                        if (document.visibilityState === "visible") return;

                        pause();
                    },
                    onDisconnect: () => {
                        this.connection = false;

                        this.data = this.t('map.closed_expected', this.activeServer);

                        window.removeEventListener("blur", pause);
                        window.removeEventListener("focus", pause);
                    }
                });

                document.addEventListener("visibilitychange", pause);
            } catch (e) {
                this.data = this.t('map.closed_unexpected', this.activeServer);

                console.error('Failed to connect to socket', e);
            }
        },
        addToLayer(marker, layer) {
            for (const key in this.layers) {
                if (layer !== key) {
                    this.layers[key].removeLayer(marker);
                }
            }

            this.layers[layer].addLayer(marker);
        },
        getLayer(player) {
            const vehicle = player.vehicle;

            if (vehicle && (vehicle.icon.type === 'police_car' || vehicle.icon.type === 'ems_car')) {
                return "Emergency Vehicles";
            }
            if (vehicle) {
                return "Vehicles";
            }
                return "Players";
        },
        async renderMapData(data, trackedId, trackedChanged) {
            if (this.isPaused || this.isDragging || this.isTimestampShowing || this.isHistoricShowing) {
                return;
            }

            let isActivelyTracking = false;
            let trackingInfo = false;

            if (data && typeof data.players === "object") {
                if (this.map) {
                    if (!this.selectedInstance) {
                        this.selectedInstance = data.instance;
                    }

                    this.container.updatePlayers(data.players, this, this.selectedInstance, data.instance);

                    this.updateTrackingInfo();

                    const unknownCharacters = [];

                    this.container.eachPlayer((id, player) => {
                        if (!player.character) {
                            console.log("Player has no character", player);
                            return;
                        }

                        const characterID = player.getCharacterID();

                        if (characterID && !unknownCharacters.includes(characterID) && !(characterID in this.characters)) {
                            unknownCharacters.push(characterID);
                        }

                        if (!(id in this.markers)) {
                            this.markers[id] = Player.newMarker();
                        }

                        this.markers[id] = player.updateMarker(this.markers[id], trackedId, this.container.vehicles);

                        this.addToLayer(this.markers[id], this.getLayer(player));

                        if (this.markers[id]._icon) {
                            this.markers[id]._icon.dataset.playerId = id;
                        }

                        if (player.player.source === trackedId) {
                            // this.map.setBearing(player.bearing);

                            this.map.setView(player.location.toMap(), trackedChanged ? 7 : this.map.getZoom(), {
                                duration: 0.1
                            });

                            // Convert m/s to mph
                            const speed = player.speed * 2.23693629;

                            trackingInfo = `${player.location.z.toFixed(1)}m at ${speed.toFixed(1)}mph`;

                            isActivelyTracking = true;
                        }
                    });

                    for (const id in this.markers) {
                        if (!Object.hasOwn(this.markers, id) || this.container.shouldDrawPlayerMarker(id, this.selectedInstance)) {
                            continue;
                        }

                        this.map.removeLayer(this.markers[id]);

                        delete this.markers[id];
                    }

                    this.invisiblePeople = this.container.invisible;

                    let unloaded = "";

                    if (this.container.stats.unloaded > 0) {
                        unloaded = `, <a href="#" class="view-unloaded dark:text-blue-300 text-blue-500 !no-underline">${this.t("map.data_unloaded", this.container.stats.unloaded)}</a>`;
                    } else {
                        unloaded = `, ${this.t("map.data_unloaded", 0)}`;
                    }

                    this.data = `${this.t(
                        'map.data',
                        this.container.stats.loaded,
                        this.container.stats.total
                    )}<span class="block text-xs leading-3">${this.t(
                        'map.data_stats',
                        this.container.stats.police,
                        this.container.stats.ems,
                        this.container.stats.staff
                    )}${unloaded}</span>`;

                    if (unknownCharacters.length > 0) {
                        // Prevent it being requested twice while the other is still loading
                        for (const id of unknownCharacters) {
                            this.characters[id] = null;
                        }

                        _post('/api/characters', {
                            ids: unknownCharacters
                        }).then(result => {
                            if (result?.status) {
                                for (const ch of result.data) {
                                    this.characters[ch.character_id] = ch;
                                }
                            }
                        });
                    }
                }

                this.activeViewers = data.viewers.sort();
            } else {
                this.data = this.t('map.error', this.activeServer);

                if (data?.error) {
                    let error = Array.isArray(data.error) ? data.error.pop() : "Something went wrong";

                    if (error.length >= 70) {
                        error = `${error.substr(0, 70)}...`;
                    }

                    this.data += `<span class="block text-xs leading-3">${error}</span>`;
                }
            }

            if (isActivelyTracking) {
                this.map.dragging.disable();
            } else {
                this.map.dragging.enable();
            }

            if (trackingInfo) {
                $("#map .leaflet-bottom.leaflet-left").html(`<div class="leaflet-control-attribution leaflet-control italic">${trackingInfo}</div>`)
            } else {
                $("#map .leaflet-bottom.leaflet-left").html("");
            }
        },
        async buildMap() {
            if (this.map) {
                return;
            }

            L.Map.addInitHook("addHandler", "gestureHandling", GestureHandling);

            this.map = L.map('map', {
                crs: L.CRS.Simple,
                gestureHandling: true,
                minZoom: 1,
                maxZoom: 8,
                maxBounds: L.latLngBounds(L.latLng(0, 0), L.latLng(-256, 256)),
                rotate: true
            });

            this.map.attributionControl.addAttribution('map by <a href="https://github.com/coalaura" target="_blank">Laura</a> <i>accurate to about 1-2m</i>');

            L.tileLayer("https://tiles.shrt.day/{z}/{x}/{y}.webp", {
                noWrap: true,
                bounds: [
                    [0, 0],
                    [-256, 256],
                ],
            }).addTo(this.map);

            this.map.setView([-159.287, 124.773], 3);

            L.control.layers({}, this.layers).addTo(this.map);

            for (const key in this.layers) {
                this.layers[key].addTo(this.map);
            }

            for (const blip of this.blips) {
                const coords = JSON.parse(blip.coords);
                const coordsText = `${coords.x.toFixed(2)} ${coords.y.toFixed(2)}`;
                const location = Vector3.fromGameCoords(coords.x, coords.y, 0);

                const marker = L.marker(location.toMap(),
                    {
                        icon: new L.Icon(
                            {
                                iconUrl: `/images/icons/${blip.icon}`,
                                iconSize: [22, 22]
                            }
                        ),
                        forceZIndex: 99
                    }
                );

                marker.bindPopup(`${blip.label}<br><i>${coordsText}</i>`, {
                    autoPan: false
                });

                this.layers.Blips.addLayer(marker);
            }

            if (this.marker) {
                const location = Vector3.fromGameCoords(this.marker[0], this.marker[1], 0);

                const marker = L.marker(location.toMap(),
                    {
                        icon: new L.Icon(
                            {
                                iconUrl: '/images/icons/marker.png',
                                iconSize: [22, 22]
                            }
                        ),
                        forceZIndex: 99
                    }
                );

                marker.bindPopup(this.t('map.marker', `${this.marker[0]}, ${this.marker[1]}`), {
                    autoPan: false
                });

                marker.addTo(this.map);

                this.map.setView(location.toMap(), 8);

                marker.openPopup();
            }

            this.map.on('dragstart', () => {
                this.isDragging = true;
            });
            this.map.on('dragend', () => {
                this.isDragging = false;
            });

            this.map.on('fullscreenchange', () => {
                setTimeout(() => {
                    this.map._onResize();
                }, 500);
            });

            this.map.addControl(new L.Control.Fullscreen());

            const styles = [
                '.leaflet-marker-icon {transform-origin:center center !important;}',
                '.leaflet-grab {cursor:default;}',
                '.coordinate-attr {font-size: 11px;padding:0 5px;color:rgb(0, 120, 168);line-height:16.5px}',
                '.leaflet-control-layers-overlays {user-select:none !important}',
                '.leaflet-control-layers-selector {outline:none !important}',
                '.leaflet-container {background:#143D6B}',
                'path.leaflet-interactive[stroke="#FFBF00"] {cursor:default}',
                `.leaflet-attr {width:${$('.leaflet-bottom.leaflet-right').width()}px}`
            ];
            $('#map').append(`<style>${styles.join('')}</style>`);
        }
    },
    mounted() {
        this.buildMap();

        $('#server').val(this.activeServer);

        this.initializeMap();

        if (Math.round(Math.random() * 100) === 1) { // 1% chance it says fib spy satellite map
            $('#map_title').text(this.t('map.spy_satellite'));
        }

        $("body").on("click", ".view-unloaded", e => {
            e.preventDefault();

            this.viewingUnloadedPlayerList = true;
        });

        const id = Number.parseInt(window.location.hash.substring(1));

        if (id) {
            this.track(id);

            window.location.hash = "";
        }
    }
};
</script>
