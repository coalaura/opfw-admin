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

        <v-section class="-mt-2 max-w-stream" :noFooter="true" :noHeader="true">
            <div class="flex gap-3">
                <div class="w-full relative">
                    <video class="w-full" ref="video" controlslist="nofullscreen nodownload noplaybackrate" poster="/images/no_stream.webp"></video>

                    <div class="absolute top-1/2 left-1/2 transform -translate-y-1/2 -translate-x-1/2 bg-red-500/40 border-2 border-red-500 text-white backdrop-filter backdrop-blur-md px-5 py-3 shadow-lgs" v-if="error">
                        <h3 class="font-bold text-md border-b-2 border-red-300 mb-2">{{ t('overwatch.stream_error') }}</h3>
                        <div class="italic">{{ error }}</div>
                    </div>
                </div>

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
            </div>
        </v-section>

    </div>
</template>

<script>
import Layout from './../../Layouts/App';
import VSection from './../../Components/Section';
import Badge from './../../Components/Badge';

import Hls from "hls.js";

export default {
    layout: Layout,
    components: {
        VSection,
        Badge,
    },
    data() {
        return {
            isLoading: false,
            error: false,

            hls: false,
            source: false,
            interval: false
        };
    },
    computed: {
        streams() {
            const overwatch = this.$page.overwatch;

            return overwatch?.streams ?? [];
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

            switch (details) {
                case "manifestLoadError":
                    this.error = this.t('overwatch.error_manifest');

                    break;
                default:
                    this.error = details;

                    break;
            }

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
                    const video = this.$refs.video;

                    if (!video) {
                        this.destroyStream(true);

                        return;
                    }

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
        }
    }
};
</script>
