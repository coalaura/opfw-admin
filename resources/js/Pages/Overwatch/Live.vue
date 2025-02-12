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

        <v-section class="-mt-2 max-w-stream relative" :noFooter="true" :noHeader="true" :resizable="true" @resize="setChatHeight">
            <div class="flex items-stretch gap-3" ref="container">
                <div class="w-72">
                    <h3 class="font-bold text-md border-b-2 border-gray-500 mb-3 flex justify-between items-start">
                        {{ t('overwatch.streams') }}

                        <small v-if="streams.length">{{ streams.length }}</small>
                    </h3>

                    <div class="italic flex flex-col gap-1" v-if="streams.length">
                        <div class="font-semibold cursor-pointer py-1 px-2 bg-black/20 border border-gray-500 transition flex items-center justify-between" :class="getStreamListingClass(stream)" v-for="(stream, index) in streams" @click="setStream(stream)">
                            {{ t('overwatch.stream', index + 1) }}

                            <template v-if="stream === source">
                                <i class="fas fa-spinner animate-spin" v-if="isLoading"></i>
                                <i class="fas fa-exclamation-triangle" v-else-if="error"></i>
                                <i class="fas fa-video" v-else></i>
                            </template>
                        </div>
                    </div>

                    <div class="italic" v-else>{{ t('overwatch.no_streams') }}</div>
                </div>

                <div class="w-full relative">
                    <video class="w-full pointer-events-none" ref="video" controlslist="nofullscreen nodownload noplaybackrate" poster="/images/no_stream.webp"></video>

                    <div class="absolute top-1/2 left-1/2 transform -translate-y-1/2 -translate-x-1/2 bg-red-500/40 border-2 border-red-500 text-white backdrop-filter backdrop-blur-md px-5 py-3 shadow-lgs" v-if="error">
                        <h3 class="font-bold text-md border-b-2 border-red-300 mb-2">{{ t('overwatch.stream_error') }}</h3>
                        <div class="italic">{{ error }}</div>
                    </div>
                </div>

                <PanelChat :active="true" :height="height" dimensions="w-96" />
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

export default {
    layout: Layout,
    components: {
        VSection,
        Badge,
        PanelChat,
    },
    data() {
        return {
            isLoading: false,
            error: false,

            height: false,

            hls: false,
            source: false,
            interval: false
        };
    },
    computed: {
        streams() {
            return this.$page.overwatch?.streams ?? [];
        }
    },
    methods: {
        getStreamListingClass(stream) {
            if (this.isLoading) {
                return "opacity-50 cursor-not-allowed";
            }

            let list = "hover:bg-black/30";

            if (this.source === stream) {
                // text-lime-600 dark:text-lime-400 border-lime-600 dark:border-lime-400
                // text-red-600 dark:text-red-400 border-red-600 dark:border-red-400
                const color = this.error ? "red" : "lime";

                list += ` text-${color}-600 dark:text-${color}-400 border-${color}-600 dark:border-${color}-400`;
            }

            return list;
        },
        setError(details) {
            this.isLoading = false;

            this.error = HlsErrorDetails[details] || details;

            this.destroyStream(false);
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
        async setStream(source) {
            if (this.isLoading) {
                return;
            }

            this.destroyStream(true);

            this.isLoading = true;

            this.hls = new Hls({
                backBufferLength: 30
            });

            const video = this.$refs.video;

            this.source = source;

            this.hls.loadSource(this.source);
            this.hls.attachMedia(video);

            this.hls.on(Hls.Events.MEDIA_ATTACHED, () => {
                console.log("Attached media to HLS");
            });

            this.hls.on("hlsError", (_, data) => {
                this.setError(data.details);
            });

            this.hls.on(Hls.Events.MANIFEST_PARSED, async () => {
                this.isLoading = false;

                video.currentTime = video.duration || 0;

                video.play();

                this.interval = setInterval(() => {
                    if (!video.duration) {
                        return;
                    }

                    if ((video.duration - video.currentTime) > 5) {
                        video.currentTime = video.duration;

                        video.play();
                    }
                }, 1000);
            });

            this.hls.startLoad();
        },
        setChatHeight() {
            this.height = `${this.$refs.video.scrollHeight}px`;
        },
        preload(url, cb) {
            const image = new Image();

            image.addEventListener("load", cb);

            image.src = url;
        }
    },
    created() {
        window.addEventListener("resize", this.setChatHeight);
    },
    destroyed() {
        window.removeEventListener("resize", this.setChatHeight);
    },
    mounted() {
        this.preload(this.$refs.video.poster, this.setChatHeight);
    }
};
</script>
