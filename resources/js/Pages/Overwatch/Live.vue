<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                <i class="mr-3 fas fa-unlock-alt" :title="perm.restriction(perm.PERM_SCREENSHOT)"></i>

                {{ t('overwatch.live') }}
            </h1>
            <p>
                {{ t('overwatch.live_description') }}
            </p>
        </portal>

        <v-section class="-mt-2 relative" :noFooter="true" :noHeader="true" :resizable="true" @resize="setChatHeight">
            <div class="flex items-stretch" :class="fullscreen ? 'bg-vdarkbg' : 'gap-3'" ref="container">
                <div class="w-72 flex flex-col gap-3 overflow-hidden" v-if="!fullscreen">
                    <h3 class="font-bold text-md border-b-2 border-gray-500 flex justify-between items-start">
                        {{ t('overwatch.streams') }}

                        <small v-if="spectators.length">{{ spectators.length }}</small>
                    </h3>

                    <div class="italic flex flex-col gap-1 h-full">
                        <div class="font-semibold cursor-pointer py-1 px-2 bg-black/20 border border-gray-500 transition flex flex-col" :class="getSpectatorListingClass(spectator)" v-for="(spectator, index) in spectators" :key="spectator.license" @click="setStream(index, spectator.stream)" v-if="spectators.length">
                            <div class="flex items-center justify-between">
                                {{ t('overwatch.stream', index + 1) }}

                                <template v-if="spectator.stream === source">
                                    <i class="fas fa-spinner animate-spin" v-if="isLoading"></i>
                                    <i class="fas fa-exclamation-triangle" v-else-if="error"></i>
                                    <i class="fas fa-video" v-else></i>
                                </template>

                                <template v-else-if="spectator.spectating">
                                    <span class="italic">{{ spectator.spectating.source }}</span>
                                </template>
                            </div>

                            <div class="text-gray-600 dark:text-gray-400 text-xxs font-medium italic -mt-1" v-if="spectator.stream === source && uptime">{{ uptime }}</div>
                        </div>

                        <div class="italic" v-else>{{ t('overwatch.no_streams') }}</div>

                        <div class="flex flex-col gap-1 border-t border-gray-500 pt-3 mt-2" v-if="source">
                            <div class="font-semibold cursor-pointer py-1 px-2 bg-black/20 border border-gray-500 text-center w-full select-none" :class="{ 'opacity-50 cursor-not-allowed': !replay || isSavingReplay || isReplayTimeout }" @click="saveReplay" :title="t(`overwatch.${replay ? 'save_replay' : 'replay_unavailable'}`)">
                                <i class="fas fa-spinner animate-spin" v-if="isSavingReplay"></i>
                                <i class="fas fa-video" v-else-if="replay"></i>
                                <i class="fas fa-video-slash" v-else></i>

                                {{ t('overwatch.clip') }}
                            </div>

                            <div class="flex justify-between gap-1">
                                <template v-for="action in actions">
                                    <div class="font-semibold cursor-pointer py-1 px-2 bg-black/20 border border-gray-500 text-center select-none relative group" :class="getActionColor(action)" v-if="action.sub">
                                        <i class="fas fa-spinner animate-spin" v-if="isPerformingAction"></i>
                                        <i :class="`fas fa-${action.icon}`" v-else></i>

                                        <div class="p-1 opacity-0 pointer-events-none absolute duration-75" :class="sub.direction + (!isPerformingAction && !isActionTimedOut ? ' group-hover:pointer-events-auto group-hover:opacity-100' : '')" v-for="sub in action.sub">
                                            <div class="font-semibold cursor-pointer py-1 px-2 bg-black/20 border border-gray-500 text-center select-none backdrop-filter backdrop-blur-md shadow-md" :class="getActionColor(sub)" @click="performAction(sub)" :title="t(`overwatch.${sub.name}`)">
                                                <i class="fas fa-spinner animate-spin" v-if="isPerformingAction"></i>
                                                <i :class="`fas fa-${sub.icon}`" v-else></i>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="font-semibold cursor-pointer py-1 px-2 bg-black/20 border border-gray-500 text-center select-none" :class="getActionColor(action)" @click="performAction(action)" :title="t(`overwatch.${action.name}`)" v-else>
                                        <i class="fas fa-spinner animate-spin" v-if="isPerformingAction"></i>
                                        <i :class="`fas fa-${action.icon}`" v-else></i>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3" v-if="source">
                        <div class="flex gap-3 items-center text-lime-600 dark:text-lime-400 text-xs italic leading-4 border-b border-gray-500 pb-3" v-if="isTimedOut">
                            {{ t('overwatch.stream_timeout') }}
                        </div>

                        <div class="flex flex-col border-b border-gray-500 pb-3" v-if="target">
                            <inertia-link class="font-medium truncate" :href="`/players/${target.license}`" :title="target.name">[{{ target.source }}] {{ target.name }}</inertia-link>
                            <div class="italic text-muted dark:text-dark-muted text-xxs -mt-1 mb-0.5" :title="formatSeconds(playtime(target), 'YMdhm', true)" v-if="'playtime' in target">
                                {{ playtime(target, true) }}
                            </div>

                            <div class="border-t border-gray-500 pt-2 mt-2 text-sm">
                                <div :title="character.name + '\n' + character.backstory" v-if="character">
                                    <inertia-link class="font-semibold text-lg truncate" :href="`/players/${target.license}/characters/${character.id}`">{{ character.name }}</inertia-link>
                                    <div class="italic text-muted dark:text-dark-muted text-xxs -mt-1 mb-0.5">{{ t('overwatch.born', dayjs(character.birthday).format('MMM Do YYYY')) }}</div>
                                    <div class="italic text-muted dark:text-dark-muted leading-5 break-words">
                                        {{ truncate(character.backstory, 70) }}
                                        <i class="fas fa-brain cursor-help text-teal-500 dark:text-teal-400 ml-1" :title="t('global.ai_generated')" v-if="isAIGenerated(character.backstory)"></i>
                                    </div>
                                </div>

                                <div class="italic text-red-800 dark:text-red-200" v-else>{{ t('overwatch.no_character') }}</div>
                            </div>
                        </div>

                        <div class="flex gap-3 items-center">
                            <input type="number" placeholder="1234" class="w-full bg-black/20 border border-gray-500 px-2 py-1" v-model="newServerId">
                            <button class="bg-black/20 border border-gray-500 px-2 py-1" :class="{ 'opacity-50 cursor-not-allowed': !validServerId || isUpdating || isLoading || isTimedOut }" @click="setSpectating">
                                <i class="fas fa-spinner animate-spin" v-if="isUpdating"></i>
                                <template v-else>{{ t('global.apply') }}</template>
                            </button>
                        </div>

                        <div class="flex gap-3 items-center text-xl">
                            <i class="fas fa-volume-mute w-7 cursor-pointer" v-if="volume === 0" @click="setVolume(0.5)"></i>
                            <i class="fas fa-volume-down w-7 cursor-pointer" v-else @click="setVolume(0)"></i>

                            <input type="range" min="0" max="1" step="0.01" v-model.number="volume" class="w-full range" @input="setVolume">

                            <i :class="`fas fa-${fullscreen ? 'compress' : 'expand'} w-7 cursor-pointer`" @click="toggleFullscreen"></i>
                        </div>
                    </div>
                </div>

                <div class="w-full relative">
                    <video class="w-full pointer-events-none" :class="{ 'h-full object-contain': fullscreen }" ref="video" controlslist="nodownload noplaybackrate" :poster="'/images/no_stream.webp'"></video>

                    <div class="absolute top-1/2 left-1/2 transform -translate-y-1/2 -translate-x-1/2 bg-red-500/40 border-2 border-red-500 text-white backdrop-filter backdrop-blur-md px-5 py-3 shadow-lgs" v-if="error">
                        <h3 class="font-bold text-md border-b-2 border-red-300 mb-2">{{ t('overwatch.stream_error') }}</h3>
                        <div class="italic">{{ error }}</div>
                    </div>

                    <div class="absolute bottom-0.5 right-1 font-semibold text-lg flex items-center leading-5 filter drop-shadow-md gap-2" v-if="source">
                        <div class="flex gap-0.5 text-[#ff8280]" :title="t('overwatch.active_viewers', activeViewers.join(', '))">
                            <svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" fill="#ff8280" d="M5 7a5 5 0 1 1 6.192 4.857A2 2 0 0 0 13 13h1a3 3 0 0 1 3 3v2h-2v-2a1 1 0 0 0-1-1h-1a3.99 3.99 0 0 1-3-1.354A3.99 3.99 0 0 1 7 15H6a1 1 0 0 0-1 1v2H3v-2a3 3 0 0 1 3-3h1a2 2 0 0 0 1.808-1.143A5.002 5.002 0 0 1 5 7zm5 3a3 3 0 1 1 0-6 3 3 0 0 1 0 6z" clip-rule="evenodd"></path>
                            </svg>
                            {{ activeViewers.length }}
                        </div>

                        <div class="flex gap-0.5 text-[#9ca3af]" :title="t('overwatch.inactive_viewers', inactiveViewers.join(', '))" v-if="inactiveViewers.length > 0">
                            <svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" fill="#9ca3af" d="M2 10a8 8 0 1 1 16 0 8 8 0 0 1-16 0zm8 6a6 6 0 0 1-4.904-9.458l8.362 8.362A5.972 5.972 0 0 1 10 16zm4.878-2.505a6 6 0 0 0-8.372-8.372l8.372 8.372z" clip-rule="evenodd"></path>
                            </svg>
                            {{ inactiveViewers.length }}
                        </div>
                    </div>
                </div>

                <PanelChat :active="true" :height="height" :room="chatRoom" :activeViewers.sync="activeViewers" :inactiveViewers.sync="inactiveViewers" :dimensions="fullscreen ? 'w-chat-full px-2 py-1 border-l-4 border-lightbd dark:border-darkbd bg-lightbg dark:bg-darkbg' : 'w-96'" :emotes="$page.emotes" />
            </div>
        </v-section>

    </div>
</template>

<script>
import Layout from './../../Layouts/App.vue';
import VSection from './../../Components/Section.vue';
import Badge from './../../Components/Badge.vue';
import PanelChat from './../../Components/PanelChat.vue';

import Hls from "hls.js";

const HlsErrorDetails = {
    keySystemNoKeys: "No keys available for the key system.",
    keySystemNoAccess: "Access to the key system was denied.",
    keySystemNoSession: "No active session in the key system.",
    keySystemNoConfiguredLicense: "No configured license found for the key system.",
    keySystemLicenseRequestFailed: "License request failed for the key system.",
    keySystemServerCertificateRequestFailed: "Server certificate request failed for the key system.",
    keySystemServerCertificateUpdateFailed: "Server certificate update failed for the key system.",
    keySystemSessionUpdateFailed: "Session update failed for the key system.",
    keySystemStatusOutputRestricted: "Output is restricted by the key system.",
    keySystemStatusInternalError: "An internal error occurred in the key system.",
    keySystemDestroyMediaKeysError: "Error destroying media keys in the key system.",
    keySystemDestroyCloseSessionError: "Error closing session in the key system.",
    keySystemDestroyRemoveSessionError: "Error removing session in the key system.",
    manifestLoadError: "Failed to load the manifest.",
    manifestLoadTimeOut: "Manifest load timed out.",
    manifestParsingError: "Error parsing the manifest.",
    manifestIncompatibleCodecsError: "Manifest contains only incompatible codecs.",
    levelEmptyError: "No fragments found in the level.",
    levelLoadError: "Failed to load the level.",
    levelLoadTimeOut: "Level load timed out.",
    levelParsingError: "Error parsing the level.",
    levelSwitchError: "Error switching levels.",
    audioTrackLoadError: "Failed to load the audio track.",
    audioTrackLoadTimeOut: "Audio track load timed out.",
    subtitleTrackLoadError: "Failed to load the subtitle track.",
    subtitleTrackLoadTimeOut: "Subtitle track load timed out.",
    fragLoadError: "Failed to load fragment.",
    fragLoadTimeOut: "Fragment load timed out.",
    fragDecryptError: "Error decrypting the fragment.",
    fragParsingError: "Error parsing the fragment.",
    fragGap: "Fragment skipped due to a gap.",
    remuxAllocError: "Remux allocation failed.",
    keyLoadError: "Failed to load decryption key.",
    keyLoadTimeOut: "Decryption key load timed out.",
    bufferAddCodecError: "Error adding codec to buffer.",
    bufferIncompatibleCodecsError: "Buffer codecs are incompatible.",
    bufferAppendError: "Error appending to buffer.",
    bufferAppendingError: "Error during buffer appending.",
    bufferStalledError: "Buffer has stalled.",
    bufferFullError: "Buffer is full.",
    bufferSeekOverHole: "Buffer seeked over a hole.",
    bufferNudgeOnStall: "Buffer nudge triggered due to stall.",
    assetListLoadError: "Failed to load the asset list.",
    assetListLoadTimeout: "Asset list load timed out.",
    assetListParsingError: "Error parsing the asset list.",
    interstitialAssetItemError: "Error with interstitial asset item.",
    internalException: "An internal exception occurred in the HLS player.",
    aborted: "Operation was aborted.",
    attachMediaError: "Failed to attach media.",
    unknown: "An unknown error occurred."
};

const RetryHlsErrorDetails = [
    "bufferStalledError"
];

export default {
    layout: Layout,
    components: {
        VSection,
        Badge,
        PanelChat,
    },
    props: {
        replay: {
            type: Boolean,
            required: true
        }
    },
    data() {
        return {
            timestamp: Math.floor(Date.now() / 1000),
            timestampLoop: false,

            isTimedOut: false,
            isUpdating: false,
            isLoading: false,
            error: false,

            isActionTimedOut: false,
            isPerformingAction: false,

            activeViewers: [],
            inactiveViewers: [],
            socket: false,
            initial: true,
            spectators: [],
            newServerId: "",

            isReplayTimeout: false,
            isSavingReplay: false,

            chatRoom: false,
            height: false,
            volume: 0.5,
            fullscreen: false,

            hls: false,
            source: false,
            interval: false
        };
    },
    computed: {
        spectator() {
            if (!this.source) return null;

            return this.spectators.find(spectator => spectator.stream === this.source);
        },
        uptime() {
            const spectator = this.spectator;

            if (!spectator || !spectator.session) return "";

            return this.t('overwatch.uptime', this.formatSeconds(this.timestamp - spectator.session));
        },
        target() {
            return this.spectator?.spectating;
        },
        character() {
            return this.target?.character;
        },
        validServerId() {
            const serverId = parseInt(this.newServerId.trim());

            if (!Number.isInteger(serverId)) return false;

            return serverId === 0 || (serverId && serverId >= 1 && serverId <= 65535);
        },
        actions() {
            return [
                {
                    name: 'revive',
                    icon: 'medkit'
                },
                {
                    icon: 'arrows-alt',
                    sub: [
                        {
                            name: 'center',
                            icon: 'arrow-circle-up',
                            direction: 'bottom-full left-1/2 -translate-x-1/2'
                        },
                        {
                            name: 'backwards',
                            icon: 'arrow-circle-down',
                            direction: 'top-full left-1/2 -translate-x-1/2'
                        },
                        {
                            name: 'right',
                            icon: 'arrow-circle-right',
                            direction: 'left-full top-1/2 -translate-y-1/2'
                        },
                        {
                            name: 'left',
                            icon: 'arrow-circle-left',
                            direction: 'right-full top-1/2 -translate-y-1/2'
                        }
                    ]
                },
                {
                    name: 'camera',
                    icon: 'ticket-alt',
                    active: this.spectator?.data?.spectatorCamera
                },
                {
                    name: 'new_player',
                    icon: 'kiwi-bird'
                }
            ];
        }
    },
    methods: {
        getActionColor(action) {
            const color = [];

            if (this.isPerformingAction || this.isActionTimedOut || action.disabled) {
                color.push("opacity-50 cursor-not-allowed");
            }

            if (action.active === true) {
                color.push("border-lime-400 dark:border-lime-600 text-lime-400 dark:text-lime-600");
            } else if (action.active === false) {
                color.push("border-red-400 dark:border-red-600 text-red-400 dark:text-red-600");
            }

            return color.join(" ");
        },
        async performAction(action) {
            if (this.isPerformingAction || this.isActionTimedOut || action.disabled || !this.spectator) return;

            this.isPerformingAction = true;
            this.isActionTimedOut = true;

            try {
                await _patch(`/live/do/${this.spectator.license}/${action.name}`);
            } catch {}

            this.isPerformingAction = false;

            setTimeout(() => {
                this.isActionTimedOut = false;
            }, 5000);
        },
        async saveReplay() {
            if (!this.replay || this.isSavingReplay || !this.spectator) return;

            this.isSavingReplay = true;
            this.isReplayTimeout = true;

            try {
                const response = await fetch(`/live/replay/${this.spectator.license}`);

                if (!response.ok) {
                    throw new Error("Failed to fetch replay.");
                } else if (response.headers.get("content-type").includes("application/json")) {
                    const json = await response.json();

                    throw new Error(json?.error || "Failed to fetch replay.")
                }

                const blob = await response.blob(),
                    url = URL.createObjectURL(blob);

                const a = document.createElement('a');

                a.href = url;
                a.download = `replay-${dayjs().format('YYYY-MM-DD_HH-mm-ss')}.webm`;

                document.body.appendChild(a);

                a.click();

                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            } catch(e) {
                alert(e.message);
            }

            this.isSavingReplay = false;

            setTimeout(() => {
                this.isReplayTimeout = false;
            }, 2000);
        },
        getSpectatorListingClass(spectator) {
            if (this.isLoading) {
                return "opacity-50 cursor-not-allowed";
            }

            let list = "hover:bg-black/30";

            if (this.isUpdating) {
                list += " cursor-not-allowed";
            }

            if (this.source === spectator.stream) {
                // text-lime-600 dark:text-lime-400 border-lime-600 dark:border-lime-400
                // text-red-600 dark:text-red-400 border-red-600 dark:border-red-400
                const color = this.error ? "red" : "lime";

                list += ` text-${color}-600 dark:text-${color}-400 border-${color}-600 dark:border-${color}-400`;
            }

            return list;
        },
        toggleFullscreen() {
            if (this.fullscreen) {
                document.exitFullscreen();
            } else {
                this.$refs.container.requestFullscreen();
            }

            this.updateFullscreen();
        },
        setError(details) {
            this.isLoading = false;

            this.error = HlsErrorDetails[details] || details;

            this.destroyStream(false);
        },
        setVolume(override) {
            if (typeof override === "number") {
                this.volume = override;
            }

            this.$refs.video.volume = this.volume;

            this.pageStore.set("volume", this.volume);
        },
        destroyStream(full) {
            clearInterval(this.interval);

            if (full) {
                this.source = false;
                this.error = false;
                this.isLoading = false;

                this.updateChatRoom();
            }

            if (!this.hls) return;

            this.hls.destroy();

            this.hls = false;
        },
        updateChatRoom() {
            if (this.source) {
                let index = 1;

                for (const spectator of this.spectators) {
                    if (spectator.stream === this.source) {
                        this.chatRoom = `#${index}`;

                        return;
                    }

                    index++
                }
            }

            this.chatRoom = false;
        },
        async setSpectating() {
            if (this.isLoading || this.isUpdating || this.isTimedOut || !this.spectator) {
                return;
            }

            const serverId = parseInt(this.newServerId?.trim());

            if (!this.validServerId || (serverId === this.target?.source)) {
                return;
            }

            this.isUpdating = true;

            try {
                const data = await _patch(`/live/set/${this.spectator.license}/${serverId}`);

                if (!data?.status) {
                    alert(data?.message || "Something went wrong.");
                } else {
                    this.isTimedOut = true;

                    setTimeout(() => {
                        this.isTimedOut = false;
                    }, 6000);
                }
            } catch(e) {
                console.error(e);
            }

            this.isUpdating = false;
        },
        createStream(source, element, onReady, onError) {
            const hls = new Hls({
                backBufferLength: 30
            });

            let timeout, interval;

            hls.loadSource(source);
            hls.attachMedia(element);

            hls.on(Hls.Events.MEDIA_ATTACHED, () => {
                console.log("Attached media to HLS");
            });

            hls.on("hlsError", (_, data) => {
                if (RetryHlsErrorDetails.includes(data.details)) {
                    const ms = Math.floor(Math.random(1000)) + 500;

                    timeout = setTimeout(() => {
                        hls.loadSource(source);
                    }, ms);

                    return;
                }

                onError?.(data.details);
            });

            hls.on(Hls.Events.MANIFEST_PARSED, async () => {
                element.currentTime = element.duration || 0;
                element.play();

                clearInterval(interval);

                interval = setInterval(() => {
                    if (!element.duration) {
                        return;
                    }

                    if ((element.duration - element.currentTime) > 5) {
                        element.currentTime = element.duration;

                        element.play();
                    }
                }, 2000);

                onReady?.();
            });

            hls.startLoad();

            return {
                destroy: () => {
                    clearTimeout(timeout);
                    clearInterval(interval);

                    hls.destroy();
                }
            };
        },
        setStream(index, source) {
            if (this.isLoading || this.isUpdating) {
                return;
            }

            this.destroyStream(true);

            this.isLoading = true;
            this.source = source;

            this.updateChatRoom();

            const video = this.$refs.video;

            this.hls = this.createStream(source, video, () => {
                this.isLoading = false;

                this.setVolume();

                window.location.hash = (index + 1).toString();
            }, this.setError);
        },
        setChatHeight() {
            if (this.fullscreen) {
                this.height = false;

                return;
            }

            this.height = `${this.$refs.video.scrollHeight}px`;
        },
        updateFullscreen() {
            this.fullscreen = !!document.fullscreenElement;

            this.setChatHeight();

            this.$nextTick(() => {
                this.setChatHeight();
            });
        },
        preload(url, cb) {
            const image = new Image();

            image.addEventListener("load", cb);

            image.src = url;
        },
        selectPreviousStream() {
            if (!this.initial) {
                return;
            }

            this.initial = false;

            const previous = parseInt(window.location.hash.substring(1)),
                index = previous ? previous - 1 : false;

            if (Number.isInteger(index) && index >= 0 && index < this.spectators.length) {
                this.setStream(index, this.spectators[index].stream);
            }
        },
        playtime(target, format) {
            const now = this.timestamp,
                session = target.session;

            if (!playtime || !loaded) {
                return this.t("overwatch.no_playtime");
            }

            const actual = now - session;

            if (format) {
                return this.t("overwatch.playtime", this.$options.filters.humanizeSeconds(actual));
            }

            return actual;
        },
        init() {
            if (this.socket) return;

            this.socket = this.createSocket("spectators", {
                onData: data => {
                    this.spectators = data;

                    this.selectPreviousStream();
                },
                onDisconnect: () => {
                    this.socket = false;

                    setTimeout(() => {
                        this.init();
                    }, 2000);
                }
            });
        },
        handleKeypress(e) {
            // Ctrl + M toggles the mute button
            if (e.ctrlKey && e.key === "m") {
                if (this.volume > 0) {
                    this.setVolume(0);
                } else {
                    this.setVolume(0.5);
                }

                return;
            }

            // F (if no input is focused) toggles fullscreen
            if (e.key === "f" && document.activeElement.tagName !== "INPUT") {
                this.toggleFullscreen();

                return;
            }
        }
    },
    created() {
        window.addEventListener("keyup", this.handleKeypress);
        window.addEventListener("resize", this.setChatHeight);
        window.addEventListener("fullscreenchange", this.updateFullscreen);
    },
    destroyed() {
        window.removeEventListener("keyup", this.handleKeypress);
        window.removeEventListener("resize", this.setChatHeight);
        window.removeEventListener("fullscreenchange", this.updateFullscreen);
    },
    mounted() {
        this.volume = this.pageStore.get("volume", 0.5);

        this.setVolume();

        this.init();

        this.preload(this.$refs.video.poster, () => {
            setTimeout(this.setChatHeight, 250);
        });

        this.timestampLoop = setInterval(() => {
            this.timestamp = Math.floor(Date.now() / 1000);
        }, 10000);
    },
    beforeUnmount() {
        this.destroyStream();

        clearInterval(this.timestampLoop);
    }
};
</script>
