<template>
    <modal :show="show">
        <template #header>
            <h1 class="dark:text-white">
                {{ title }}
            </h1>
        </template>

        <template #default>
            <video v-if="mainImageURL && (['mp4', 'webm'].includes(mainImageURL.split('.').pop()))" :src="mainImageURL" controls class="w-full mb-3"></video>
            <img :src="mainImageURL" v-else-if="mainImageURL" class="w-full mb-3" />

            <slot></slot>

            <hashResolver>
                <template #default>
                    <div class="mt-4" v-for="meta in metadataJSON">
                        <p class="font-semibold mb-1 font-mono cursor-pointer" @click="meta.open = !meta.open">
                            <i class="fas fa-caret-right" v-if="!meta.open"></i>
                            <i class="fas fa-caret-down" v-else></i>

                            {{ meta.key }}
                        </p>

                        <pre class="text-xs whitespace-pre-wrap py-2 px-3 bg-gray-200 dark:bg-gray-800 rounded-sm hljs cursor-pointer" @click="meta.open = true" v-if="!meta.open"><span class="hljs-number">...</span></pre>
                        <pre class="text-xs whitespace-pre-wrap py-2 px-3 bg-gray-200 dark:bg-gray-800 rounded-sm hljs" v-html="meta.value" v-else-if="meta.value"></pre>
                        <pre class="text-xs whitespace-pre-wrap py-2 px-3 bg-gray-200 dark:bg-gray-800 rounded-sm italic" v-else>empty</pre>
                    </div>
                </template>
            </hashResolver>

            <div class="flex justify-evenly gap-3 mt-4 pt-4 border-t-2 border-dashed border-gray-500" v-if="images.length">
                <div class="border rounded border-gray-500 p-2 bg-gray-200 dark:bg-gray-600" v-for="image in images">
                    <h3 class="text-center text-base mb-3 font-mono font-semibold">{{ image.key }}</h3>

                    <img :src="image.value" class="w-40 rounded bg-gray-400 dark:bg-gray-800 p-3" />
                </div>
            </div>
        </template>

        <template #actions>
            <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="close">
                {{ t('global.close') }}
            </button>
        </template>
    </modal>
</template>

<script>
import Modal from './Modal';
import HashResolver from './HashResolver';

import hljs from 'highlight.js';
import json from 'highlight.js/lib/languages/json';

import 'highlight.js/styles/github-dark-dimmed.css';

hljs.registerLanguage('json', json);

const KnownTypes = {
    "lastLag": "s",
    "lastLagTime": "ms",
    "waypoint": "m",
    "speed": "m/s",
    "timePassed": "s",
    "distance": "m",
    "calculatedSpeed": "m/s",
    "allowedSpeed": "m/s",
    "lastBelowGround": "ticks",
    "lastFalling": "ms",
    "lastFastMovement": "ticks",
    "lastHighSpeed": "ms",
    "spawnTime": "ms"
};

const CustomPreProcessors = {
    "trace": data => data.split("\n"),
    "modifications": data => data.split("\n"),

    "changes": data => {
        return data.map(change => change.replace(/(?<=^\w+: \d+ -> )\w+: (?=\d+$)/m, ""));
    }
};

const CustomFormatters = {
    "closestBlip": data => `"${data.distance.toFixed(1)}m - ${data.label}"`,
};

export default {
    name: 'MetadataViewer',
    props: {
        title: String,
        image: [String, Boolean],
        metadata: Object,
        show: Boolean
    },
    components: {
        Modal,
        HashResolver
    },
    watch: {
        metadata() {
            this.updateMetadata();
        }
    },
    data() {
        return {
            metadataJSON: [],
            mainImageURL: null,

            images: []
        };
    },
    mounted() {
        this.updateMetadata();
    },
    methods: {
        updateMetadata() {
            // Reset images
            this.images = [];

            const screenshotURL = this.metadata?.screenshotURL;

            const metadataJSON = [];

            if (this.metadata) {
                const metadata = this.cleanupObject(this.metadata);

                delete metadata.screenshotURL;

                for (const key in metadata) {
                    let value = metadata[key];

                    // Is it an image?
                    if (typeof value === "string" && value.match(/^https?:\/\/.*\.(png|jpe?g|gif|webp)$/im)) {
                        this.images.push({
                            key,
                            value
                        });

                        delete metadata[key];

                        continue;
                    }

                    if (key in CustomPreProcessors) {
                        value = CustomPreProcessors[key](value);
                    }

                    if (typeof value === "object") {
                        const label = key + (Array.isArray(value) ? ` (${value.length})` : "");

                        metadataJSON.push({
                            key: `metadata.${label}`,
                            value: this.highlightJSON(value),
                            open: false
                        });

                        delete metadata[key];
                    }
                }

                metadataJSON.unshift({
                    key: 'metadata',
                    value: this.highlightJSON(metadata),
                    open: true
                });
            }

            this.metadataJSON = metadataJSON;
            this.mainImageURL = this.image || screenshotURL;
        },
        cleanupObject(value) {
            if (typeof value === "object") {
                for (const key in value) {
                    value[key] = this.cleanupObject(value[key]);
                }

                return value;
            }

            if (typeof value === "number") {
                value = Math.round(value * 100) / 100;
            }

            return value;
        },
        msToTime(ms) {
            if (ms < 1000) {
                return `${ms}<span class="text-gray-400 ml-0.5">ms</span>`;
            }

            let fmt = [];

            let seconds = Math.floor(ms / 1000).toString().padEnd(2, '0');
            ms = ms % 1000;

            let minutes = Math.floor(seconds / 60).toString().padEnd(2, '0');
            seconds = seconds % 60;

            seconds > 0 && fmt.unshift(`${seconds}<span class="text-gray-400 ml-0.5">s</span>`);

            const hours = Math.floor(minutes / 60).toString().padEnd(2, '0');
            minutes = minutes % 60;

            minutes > 0 && fmt.unshift(`${minutes}<span class="text-gray-400 ml-0.5">m</span>`);
            hours > 0 && fmt.unshift(`${hours}<span class="text-gray-400 ml-0.5">h</span>`);

            (ms > 0 || fmt.length === 0) && fmt.push(`${ms}<span class="text-gray-400 ml-0.5">ms</span>`);

            return fmt.join(" ");
        },
        highlightJSON(object) {
            const isArray = Array.isArray(object),
                maxLine = Object.keys(object).map(k => k.length).reduce((a, b) => Math.max(a, b), 0);

            const lines = [];

            for (const key in object) {
                const type = key in KnownTypes && object[key] !== false ? KnownTypes[key] : null,
                    raw = object[key];

                let value = JSON.stringify(raw)
                    .replace(/{"x": ?(-?\d+(\.\d+)?), ?"y": ?(-?\d+(\.\d+)?)}/gm, 'vector2($1, $3)') // vector2
                    .replace(/{"x": ?(-?\d+(\.\d+)?), ?"y": ?(-?\d+(\.\d+)?), ?"z": ?(-?\d+(\.\d+)?)}/gm, 'vector3($1, $3, $5)') // vector3
                    .replace(/{"x": ?(-?\d+(\.\d+)?), ?"y": ?(-?\d+(\.\d+)?), ?"z": ?(-?\d+(\.\d+)?), ?"w": ?(-?\d+(\.\d+)?)}/gm, 'vector4($1, $3, $5, $7)') // vector4
                    .replace(/(?<="):(?! |$)|,(?=")/gm, '$& ');

                value = hljs.highlight(value, { language: 'json' }).value;

                if (type) {
                    if (['ms', 's'].includes(type)) {
                        const actual = raw * (type === 'ms' ? 1 : 1000);

                        value = `<span class="hljs-number" title="${raw} ${type}">${this.msToTime(actual)}</span>`;
                    } else {
                        value += `<span class="text-gray-400 ml-0.5">${type}</span>`;
                    }
                } else if (typeof raw === 'object' && 'before' in raw && 'after' in raw) {
                    const before = JSON.stringify(raw.before),
                        after = JSON.stringify(raw.after);

                    value = `<span class="hljs-number">${before} <span class="text-gray-400">-></span> ${after}</span>`;
                } else if (key in CustomFormatters && raw) {
                    value = CustomFormatters[key](raw);
                }

                const line = isArray ? value : `<b>${key.padEnd(maxLine, " ")}</b>: ${value}`;

                lines.push(`<span class="block hover:bg-black hover:!bg-opacity-10 py-xs px-1">${line}</span>`);
            }

            if (!isArray) {
                lines.sort();
            }

            return lines.join("");
        },
        close() {
            this.$emit('update:show', false);
        }
    }
}
</script>
