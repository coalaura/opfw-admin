<template>
    <div>
        <portal to="title">
            <div class="mb-6">
                <h1 class="dark:text-white flex items-center gap-2">
                    <i class="mr-3 fas fa-unlock-alt" :title="perm.restriction(perm.PERM_LIVEMAP)"></i>

                    <span id="map_title">{{ t('map.title') }}</span>
                </h1>

                <p class="mt-3">
                    <span class="block">
                        Currently <b>69/420</b> online players.
                        <span class="block text-xs leading-3">Police: 12, EMS: 3, Staff: 4</span>
                    </span>
                    <span class="block text-xs text-muted dark:text-dark-muted leading-3 mt-2">
                        <b>Current Viewers: </b>
                        <span>
                            <a :href="'/players/' + $page.auth.player.licenseIdentifier" target="_blank" class="!no-underline dark:text-blue-300 text-blue-500">{{ $page.auth.player.playerName }}</a>
                        </span>
                    </span>
                </p>
            </div>
        </portal>

        <portal to="actions">
            <div class="flex gap-2">
                <!-- Show Timestamp -->
                <button class="p-2 w-11 text-center font-semibold text-white rounded bg-blue-600 dark:bg-blue-500" :title="t('map.timestamp_title')">
                    <i class="fas fa-vial"></i>
                </button>

                <!-- Show Historic -->
                <button class="p-2 w-11 text-center font-semibold text-white rounded bg-blue-600 dark:bg-blue-500" :title="t('map.historic_title')">
                    <i class="fas fa-map"></i>
                </button>

                <!-- Play/Pause -->
                <button class="p-2 w-11 text-center font-semibold text-white rounded bg-green-600 dark:bg-green-500" :title="t('map.pause')" @click="isPaused = true" v-if="!isPaused">
                    <i class="fas fa-pause"></i>
                </button>
                <button class="p-2 w-11 text-center font-semibold text-white rounded bg-red-600 dark:bg-red-500" :title="t('map.play')" @click="isPaused = false" v-else>
                    <i class="fas fa-play"></i>
                </button>
            </div>
        </portal>

        <template>
            <div class="-mt-10 flex flex-wrap">
                <div class="w-full">
                    <div class="relative w-full">
                        <input type="number" class="placeholder absolute z-1k leaflet-tl ml-12 w-16 block px-2 font-base font-semibold" :placeholder="t('map.track_placeholder')" min="0" max="65536" v-model="trackId" :class="trackingValid ? 'text-green-700 bg-green-100' : 'text-red-700 bg-red-200'" />

                        <div id="map" class="w-full relative h-max"></div>
                    </div>

                    <!-- Map Legend -->
                    <div class="my-2 flex flex-wrap justify-between text-xs w-full">
                        <div class="mx-2">
                            <img src="/images/icons/circle.png" class="w-map-icon inline-block" alt="on foot" />
                            <span class="leading-map-icon">on foot</span>
                        </div>
                        <div class="mx-2">
                            <img src="/images/icons/circle_green.png" class="w-map-icon inline-block" alt="invisible" />
                            <span class="leading-map-icon">invisible</span>
                        </div>
                        <div class="mx-2">
                            <img src="/images/icons/circle_red.png" class="w-map-icon inline-block" alt="passenger" />
                            <span class="leading-map-icon">passenger</span>
                        </div>
                        <div class="mx-2">
                            <img src="/images/icons/skull.png" class="w-map-icon inline-block" alt="dead" />
                            <span class="leading-map-icon">dead</span>
                        </div>
                        <div class="mx-2">
                            <img src="/images/icons/skull_red.png" class="w-map-icon inline-block" alt="dead passenger" />
                            <span class="leading-map-icon">dead (passenger)</span>
                        </div>
                        <div class="mx-2">
                            <img src="/images/icons/circle_police.png" class="w-map-icon inline-block" alt="police" />
                            <span class="leading-map-icon">police</span>
                        </div>
                        <div class="mx-2">
                            <img src="/images/icons/circle_ems.png" class="w-map-icon inline-block" alt="ems" />
                            <span class="leading-map-icon">ems</span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
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

import Layout from './../../Layouts/App';
import VSection from './../../Components/Section';

export default {
    layout: Layout,
    components: {
        VSection,
    },
    data() {
        return {
            map: null,
            isPaused: false,
            layers: {
                "Players": L.layerGroup(),
                "Emergency Vehicles": L.layerGroup(),
                "Vehicles": L.layerGroup(),
                "Blips": L.layerGroup(),
            },

            trackId: false,
            markers: []
        };
    },
    computed: {
        trackingValid() {
            this.trackId = parseInt(this.trackId);

            if (!this.trackId) {
                return false;
            }

            return !!this.markers.find(m => m.id === this.trackId);
        }
    },
    methods: {
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

            L.tileLayer("https://cattos.xyz/api.png?x={x}&y={y}&z={z}", {
                noWrap: true,
                bounds: [
                    [0, 0],
                    [-256, 256],
                ],
            }).addTo(this.map);

            this.map.setView([-159.287, 124.773], 3);

            L.control.layers({}, this.layers).addTo(this.map);

            $.each(this.layers, key => {
                this.layers[key].addTo(this.map);
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
                '.leaflet-attr {width:' + $('.leaflet-bottom.leaflet-right').width() + 'px}'
            ];

            $('#map').append('<style>' + styles.join('') + '</style>');

            const count = Math.round(Math.random() * 10) + 10,
                icons = ['circle', 'circle_green', 'skull_red', 'circle_red', 'skull', 'circle_police', 'circle_ems'],
                names = ["Zelda Zippertoes", "Flapjack McWiggles", "Noodle McSprinkles", "Snorkel Buttercrust", "Topsy Turvypants", "Waldo Wibblewobble", "Blippy Fizzlenose", "Sprout McJellybeans", "Cranky Doodlefluff", "Fungus Puddlejumper"];

            for (let i = 0; i < count; i++) {
                const x = Math.round(Math.random() * -256),
                    y = Math.round(Math.random() * 256),
                    r = Math.round(Math.random() * 360);

                // Random velocity between 0.5 and 10.0 (negative or positive)
                const velX = (Math.random() * 9.5 + 0.5) * (Math.random() > 0.5 ? 1 : -1),
                    velY = (Math.random() * 9.5 + 0.5) * (Math.random() > 0.5 ? 1 : -1),
                    velR = (Math.random() * 18.0 + 2.0) * (Math.random() > 0.5 ? 1 : -1);

                const marker = {
                    id: Math.floor(Math.random() * 2500) + 1,
                    name: names[Math.floor(Math.random() * names.length)],

                    x: x,
                    y: y,
                    r: r,

                    velX: velX,
                    velY: velY,
                    velR: velR,

                    blip: L.marker([x, y], {
                        icon: L.icon({
                            iconUrl: `/images/icons/${icons[Math.floor(Math.random() * icons.length)]}.png`,
                            iconSize: [18, 18],
                        }),
                    })
                };

                marker.blip.setRotationAngle(r);

                const popup = `<span class="font-bold block border-b border-gray-700 mb-1">${marker.name} <sup>${marker.id}</sup></span>`
                    + `<span class="block"><b>Altitude:</b> ${(Math.random() * 100).toFixed(1)}m</span>`
                    + `<span class="block"><b>Speed:</b> ${(Math.random() * 100).toFixed(1)}mph</span>`;

                marker.blip.bindPopup(popup, {
                    autoPan: false
                });

                this.markers.push(marker);

                marker.blip.addTo(this.map);
            }

            let lastTime = Date.now();

            const animate = () => {
                const now = Date.now(),
                    deltaTime = (now - lastTime) / 1000.0;

                lastTime = now;

                // 30 fps
                setTimeout(() => {
                    requestAnimationFrame(animate);
                }, Math.max(0, Math.floor(1000.0 / 30.0) - deltaTime * 1000.0));

                if (this.isPaused) {
                    return;
                }

                for (let i = 0; i < this.markers.length; i++) {
                    const marker = this.markers[i];

                    marker.x += marker.velX * deltaTime;
                    marker.y += marker.velY * deltaTime;
                    marker.r += (marker.velR * deltaTime) % 360.0;

                    if (marker.x < -256 || marker.x > 0) {
                        marker.velX *= -1;
                    }

                    if (marker.y < 0 || marker.y > 256) {
                        marker.velY *= -1;
                    }

                    marker.blip.setLatLng([marker.x, marker.y]);

                    marker.blip.setRotationAngle(marker.r);

                    if (this.trackId === marker.id && this.trackingValid) {
                        this.map.setView([marker.x, marker.y], this.map.getZoom(), {
                            duration: 0.1
                        });
                    }
                }
            };

            requestAnimationFrame(animate);
        }
    },
    mounted() {
        this.buildMap();

        if (Math.round(Math.random() * 100) === 1) { // 1% chance it says fib spy satellite map
            $(document).ready(() => {
                $('#map_title').text(this.t('map.spy_satellite'));
            });
        }
    }
};
</script>
