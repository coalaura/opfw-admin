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
            <div class="flex items-stretch gap-3" ref="container">
                <div class="w-72 flex flex-col gap-3">
                    <h3 class="font-bold text-md border-b-2 border-gray-500 flex justify-between items-start">
                        {{ t('overwatch.streams') }}

                        <small v-if="spectators.length">{{ spectators.length }}</small>
                    </h3>

                    <div class="italic flex flex-col gap-1 h-full">
                        <div class="font-semibold cursor-pointer py-1 px-2 bg-black/20 border border-gray-500 transition flex items-center justify-between" :class="getSpectatorListingClass(spectator)" v-for="(spectator, index) in spectators" :key="spectator.license" @click="setStream(spectator.stream)" v-if="spectators.length">
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

                        <div class="italic" v-else>{{ t('overwatch.no_streams') }}</div>

                        <div class="flex gap-1 border-t border-gray-500 pt-3 mt-2" v-if="source">
                            <div class="font-semibold cursor-pointer py-1 px-2 bg-black/20 border border-gray-500 text-center w-full select-none" :class="{ 'opacity-50 cursor-not-allowed': !replay || isSavingReplay }" @click="saveReplay" :title="t(`overwatch.${replay ? 'save_replay' : 'replay_unavailable'}`)">
                                <i class="fas fa-video" v-if="replay"></i>
                                <i class="fas fa-video-slash" v-else></i>

                                {{ t('overwatch.clip') }}
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3" v-if="source">
                        <div class="flex gap-3 items-center text-lime-600 dark:text-lime-400 text-xs italic leading-4 border-b border-gray-500 pb-3" v-if="isTimedOut">
                            {{ t('overwatch.stream_timeout') }}
                        </div>

                        <div class="flex flex-col border-b border-gray-500 pb-3" v-if="target">
                            <div class="font-semibold">{{ t('overwatch.watching') }}:</div>
                            <a :href="`/players/${target.license}`" target="_blank" class="text-lime-600 dark:text-lime-400 no-underline italic">[{{ target.source }}] {{ target.name }}</a>
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
                    <video class="w-full pointer-events-none" ref="video" controlslist="nodownload noplaybackrate" poster="/images/no_stream.webp"></video>

                    <div class="absolute top-1/2 left-1/2 transform -translate-y-1/2 -translate-x-1/2 bg-red-500/40 border-2 border-red-500 text-white backdrop-filter backdrop-blur-md px-5 py-3 shadow-lgs" v-if="error">
                        <h3 class="font-bold text-md border-b-2 border-red-300 mb-2">{{ t('overwatch.stream_error') }}</h3>
                        <div class="italic">{{ error }}</div>
                    </div>
                </div>

                <PanelChat :active="true" :height="height" dimensions="w-96" :emotes="emotes" />
            </div>
        </v-section>

    </div>
</template>

<script>
import Layout from './../../Layouts/App';
import VSection from './../../Components/Section';
import Badge from './../../Components/Badge';
import PanelChat from './../../Components/PanelChat';

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
        emotes: {
            type: Object | Array,
            required: true
        },
        replay: {
            type: Boolean,
            required: true
        }
    },
    data() {
        return {
            isTimedOut: false,
            isUpdating: false,
            isLoading: false,
            error: false,

            socket: false,
            spectators: [],
            newServerId: "",

            isSavingReplay: false,

            height: false,
            volume: 0.5,
            fullscreen: false,

            hls: false,
            source: false,
            interval: false
        };
    },
    computed: {
        target() {
            if (!this.source) return false;

            const spectator = this.spectators.find(spectator => spectator.stream === this.source);

            if (!spectator) return false;

            return spectator.spectating;
        },
        validServerId() {
            const serverId = parseInt(this.newServerId);

            if (!Number.isInteger(serverId)) return false;

            return serverId === 0 || (serverId && serverId >= 1 && serverId <= 65535);
        }
    },
    methods: {
        async saveReplay() {
            if (!this.replay || this.isSavingReplay) return;

            const spectator = this.spectators.find(spectator => spectator.stream === this.source);

            if (!spectator) return false;

            this.isSavingReplay = true;

            try {
                const response = await fetch(`/live/replay/${spectator.license}`);

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
                a.download = `replay-${this.$moment().format('YYYY-MM-DD_HH-mm-ss')}.webm`;

                document.body.appendChild(a);

                a.click();

                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            } catch(e) {
                alert(e.message);
            }

            this.isSavingReplay = false;
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
                this.$refs.video.requestFullscreen();
            }
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
        },
        destroyStream(full) {
            clearInterval(this.interval);

            if (full) {
                this.source = false;
                this.error = false;
                this.isLoading = false;
            }

            if (!this.hls) return;

            this.hls.destroy();

            this.hls = false;
        },
        async setSpectating() {
            if (this.isLoading || this.isUpdating || this.isTimedOut) {
                return;
            }

            const spectator = this.spectators.find(spectator => spectator.stream === this.source);

            if (!spectator) {
                return;
            }

            if (!this.validServerId || (spectator.spectating && this.newServerId === spectator.spectating.source)) {
                return;
            }

            this.isUpdating = true;

            try {
                const data = await fetch(`/live/set/${spectator.license}/${this.newServerId}`, {
                    method: "PATCH",
                }).then(response => response.json());

                if (!data?.status) {
                    alert(data?.message || "Something went wrong.");
                } else {
                    this.isTimedOut = true;

                    setTimeout(() => {
                        this.isTimedOut = false;
                    }, 10000);
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
                    timeout = setTimeout(() => {
                        hls.loadSource(source);
                    }, 500);

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
                }, 1000);

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
        setStream(source) {
            if (this.isLoading || this.isUpdating) {
                return;
            }

            this.destroyStream(true);

            this.isLoading = true;
            this.source = source;

            const video = this.$refs.video;

            this.hls = this.createStream(source, video, () => {
                this.isLoading = false;

                this.setVolume();
            }, this.setError);
        },
        setChatHeight() {
            this.height = `${this.$refs.video.scrollHeight}px`;
        },
        updateFullscreen() {
            this.fullscreen = !!document.fullscreenElement;
        },
        preload(url, cb) {
            const image = new Image();

            image.addEventListener("load", cb);

            image.src = url;
        },
        init() {
            if (this.socket) return;

            this.socket = this.createSocket("spectators", {
                onData: data => {
                    this.spectators = data;
                },
                onDisconnect: () => {
                    this.socket = false;

                    setTimeout(() => {
                        this.init();
                    }, 2000);
                }
            });
        },
    },
    created() {
        window.addEventListener("resize", this.setChatHeight);
        window.addEventListener("fullscreenchange", this.updateFullscreen);
    },
    destroyed() {
        window.removeEventListener("resize", this.setChatHeight);
        window.removeEventListener("fullscreenchange", this.updateFullscreen);
    },
    mounted() {
        this.setVolume();

        this.init();

        this.preload(this.$refs.video.poster, () => {
            setTimeout(this.setChatHeight, 250);
        });
    }
};
</script>
