<template>
    <div class="flex items-center text-[80%] relative">
        <svg
             class="absolute top-1/2 left-1/2 pointer-events-none transform -rotate-90 -translate-x-1/2 -translate-y-1/2 h-full"
             viewBox="0 0 40 40"
             xmlns="http://www.w3.org/2000/svg" v-if="audio">
            <circle
                    cx="20"
                    cy="20"
                    r="18"
                    stroke="currentColor"
                    stroke-width="2"
                    fill="none"
                    :stroke-dasharray="circleDashArray"
                    :stroke-dashoffset="circleDashOffset" />
        </svg>

        <i class="fas fa-pause cursor-pointer" @click="pause()" v-if="playing"></i>
        <i class="fas fa-play cursor-pointer" @click="resume()" v-else-if="audio"></i>
        <i class="fas fa-headphones cursor-pointer" :title="t('global.read_out_loud')" @click="play()" v-else></i>
    </div>
</template>

<script>
export default {
    name: 'TextToSpeech',
    props: {
        source: {
            type: String,
            required: true,
        }
    },
    data() {
        return {
            playing: false,
            audio: null,

            duration: 0,
            currentTime: 0,
            circleDashArray: 113,
            circleDashOffset: 113
        };
    },
    methods: {
        play() {
            if (this.playing) return;

            this.playing = true;

            this.audio = new Audio(this.source);
            this.audio.play();

            this.audio.addEventListener("loadedmetadata", () => {
                this.duration = this.audio.duration;
            });

            this.audio.addEventListener("timeupdate", () => {
                this.currentTime = this.audio.currentTime;

                const progress = this.currentTime / this.duration;

                this.circleDashOffset = this.circleDashArray * (1 - progress);
            });

            this.audio.addEventListener("ended", () => {
                this.playing = false;
                this.audio = null;
            });
        },
        resume() {
            if (this.playing) return;

            this.playing = true;

            this.audio.play();
        },
        pause() {
            if (!this.playing) return;

            this.playing = false;

            this.audio.pause();
        }
    },
    beforeUnmount() {
        this.pause();
    }
}
</script>
