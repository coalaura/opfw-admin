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

import Layout from './../../Layouts/App.vue';
import VSection from './../../Components/Section.vue';

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
            this.trackId = Number.parseInt(this.trackId);

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

            for (const key in this.layers) {
                this.layers[key].addTo(this.map);
            }

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

            const count = Math.round(Math.random() * 10) + 10;
            const icons = ['circle', 'circle_green', 'skull_red', 'circle_red', 'skull', 'circle_police', 'circle_ems'];
            const names = ["Zelda Zippertoes", "Flapjack McWiggles", "Noodle McSprinkles", "Snorkel Buttercrust", "Topsy Turvypants", "Waldo Wibblewobble", "Blippy Fizzlenose", "Sprout McJellybeans", "Cranky Doodlefluff", "Fungus Puddlejumper", "Jellybean Thunderpants", "Waffles McFluffy", "Pickle VonSprout", "Gizmo Tinkerton", "Bingo Fuzzlenut", "Doodle Whiskerbottom", "Zippy Puddingfoot", "Wiggle Wormwood", "Boogie VonDoodle", "Peanut Butterbuns", "Flipper Doodlehop", "Twinkles McSnuffles", "Skippy Bumblebutt", "Zippy McWigglesnort", "Spunky Dipperdoodle", "Frodo Bananabread", "Grumpy Fluffernoodle", "Slinky Tiddlewinks", "Bubbles McGiggles", "Fizzles McJibbles", "Pogo VonTwist", "Scooter McBreezy", "Squishy Whiskersnort", "Wobble McPudding", "Snickers Doodlewhip", "Squeezy Fuzzbottom", "Jumbo Jigglenoodle", "Muffin Waffleboot", "Gizmo Fiddlesticks", "Gloop McSplatter", "Fluffy Wobblepants", "Whiskers VonSnort", "Pippin Tinkertot", "Blimpy Squiggleton", "Nibbles McPuff", "Blinky Snorklewobble", "Jiggly Tumbleweed", "Twiddle Fizzlebop", "Waffle Snuggletop", "Scooby VonTwizzle"];
            const activities = ["Robbing a bank", "Flying a stolen helicopter", "Running from the cops", "Selling tacos", "Racing a lawnmower", "Hosting a car meet", "Breaking into a mansion", "Delivering pizza", "Starting a protest", "Stealing a boat", "Buying illegal fireworks", "Performing a stunt jump", "Fighting with a local", "Trying to sell a broken bike", "Starting a nightclub", "Escaping from jail", "Doing yoga on the beach", "Stealing a cop car", "Spray-painting graffiti", "Driving an ambulance off-road", "Playing guitar at a park", "Starting a street race", "Hacking an ATM", "Selling stolen goods", "Scuba diving for treasure", "Arguing over parking", "Playing poker in a backroom", "Fleeing from a drug deal", "Fixing a broken car", "Riding a horse in the city", "Organizing a protest", "Running an illegal casino", "Stealing a firetruck", "Hijacking a train", "Selling hot dogs", "Breaking into a plane", "Shooting fireworks downtown", "Evading a police blockade", "Flying a drone", "Rescuing a hostage", "Holding up a store", "Winning a street race", "Robbing a jewelry store", "Buying a fake ID", "Escaping a speeding ticket", "Running a taxi service", "Jumping off a building", "Starting a food truck business", "Setting off alarms downtown", "Trying to outdrive a tank"];

            const bounce = () => Math.random() * 0.5 + 0.75;

            const randomIcon = () => L.icon({
                iconUrl: `/images/icons/${icons[Math.floor(Math.random() * icons.length)]}.png`,
                iconSize: [18, 18],
            });

            const popup = marker => `<span class="font-bold block border-b border-gray-700 mb-1">${marker.name} <sup>${marker.id}</sup></span>`
                + `<span class="block border-b border-gray-700 pb-1 mb-1"><b>Speed:</b> ${(Math.sqrt(Math.abs(marker.velX) ** 2 + Math.abs(marker.velY) ** 2) * 2.237).toFixed(1)}mph</span>`
                + `<span class="block italic">${marker.activity}</span>`;

            const addRandom = () => {
                const x = Math.random() * -256;
                const y = Math.random() * 256;
                const r = Math.random() * 360;

                const velX = (Math.random() * 11.75 + 0.25) * (Math.random() > 0.5 ? 1 : -1);
                const velY = (Math.random() * 11.75 + 0.25) * (Math.random() > 0.5 ? 1 : -1);
                const velR = (Math.random() * 18.0 + 2.0) * (Math.random() > 0.5 ? 1 : -1);

                const marker = {
                    id: Math.floor(Math.random() * 2500) + 1,
                    name: names[Math.floor(Math.random() * names.length)],
                    activity: activities[Math.floor(Math.random() * activities.length)],

                    x: x,
                    y: y,
                    r: r,

                    velX: velX,
                    velY: velY,
                    velR: velR,

                    blip: L.marker([x, y], {
                        icon: randomIcon(),
                    })
                };

                marker.blip.setRotationAngle(r);

                marker.blip.bindPopup(popup(marker), {
                    autoPan: false
                });

                this.markers.push(marker);

                marker.blip.addTo(this.map);

                // lifetime of 30 seconds to 10 minutes
                const lifetime = Math.floor(Math.random() * 570) + 30;

                setTimeout(() => {
                    this.markers.splice(this.markers.indexOf(marker), 1);

                    marker.blip.remove();
                }, lifetime * 1000);

                // Ensure a replacement occurs sometime around the lifetime
                const replacement = lifetime * ((Math.random() * 0.5) + 0.25);

                setTimeout(addRandom, replacement * 1000);

                // Small chance to add a new marker
                if (Math.random() < 0.02) {
                    addRandom();
                }
            };

            for (let i = 0; i < count; i++) {
                addRandom();
            }

            let lastTime = Date.now();

            const animate = () => {
                const now = Date.now();
                const deltaTime = Math.min((now - lastTime) / 1000.0, 0.2);

                lastTime = now;

                // 30 fps
                setTimeout(() => {
                    requestAnimationFrame(animate);
                }, Math.max(0, Math.floor(1000.0 / 30.0) - deltaTime * 1000.0));

                if (this.isPaused) {
                    return;
                }

                const minX = -256;
                const maxX = 0;
                const minY = 0;
                const maxY = 256;
                const collisionDistance = 0.4;

                for (let i = 0; i < this.markers.length; i++) {
                    const marker = this.markers[i];

                    marker.x += marker.velX * deltaTime;
                    marker.y += marker.velY * deltaTime;

                    if (marker.x <= minX || marker.x >= maxX) {
                        marker.velX *= -1 * bounce();
                        marker.x = Math.max(minX, Math.min(marker.x, maxX));

                        Math.random() < 0.05 && marker.blip.setIcon(randomIcon());
                    }

                    if (marker.y <= minY || marker.y >= maxY) {
                        marker.velY *= -1 * bounce();
                        marker.y = Math.max(minY, Math.min(marker.y, maxY));

                        Math.random() < 0.05 && marker.blip.setIcon(randomIcon());
                    }

                    // Small chance to change activity
                    if (Math.random() < 0.0005) {
                        marker.activity = activities[Math.floor(Math.random() * activities.length)];
                    }

                    for (let j = i + 1; j < this.markers.length; j++) {
                        const otherMarker = this.markers[j];

                        const dx = otherMarker.x - marker.x;
                        const dy = otherMarker.y - marker.y;

                        const distance = Math.sqrt(dx * dx + dy * dy);

                        if (distance < collisionDistance) {
                            const tempVelX = marker.velX;
                            const tempVelY = marker.velY;

                            marker.velX = otherMarker.velX;
                            marker.velY = otherMarker.velY;

                            otherMarker.velX = tempVelX;
                            otherMarker.velY = tempVelY;

                            const overlap = (collisionDistance - distance) / 2;
                            const separationFactor = overlap / distance;

                            marker.x -= separationFactor * dx;
                            marker.y -= separationFactor * dy;

                            otherMarker.x += separationFactor * dx;
                            otherMarker.y += separationFactor * dy;
                        }
                    }

                    marker.blip.setLatLng([marker.x, marker.y]);

                    marker.blip.setRotationAngle(marker.r);

                    if (this.trackId === marker.id && this.trackingValid) {
                        this.map.setView([marker.x, marker.y], this.map.getZoom());
                    }

                    marker.blip._popup.setContent(popup(marker));
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
