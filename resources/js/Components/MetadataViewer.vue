<template>
    <modal :show="show">
        <template #header>
            <h1 class="dark:text-white">
                {{ title }}
            </h1>
        </template>

        <template #default>
            <video v-if="mainImageURL && (['mp4', 'webm'].includes(mainImageURL.split('.').pop()))" :src="mainImageURL" controls class="w-full mb-3"></video>
            <img :src="mainImageURL" v-else-if="mainImageURL" class="w-full mb-3" v-handle-error />

            <slot></slot>

            <hashResolver>
                <template #default>
                    <div class="mt-4 relative" v-for="meta in metadataJSON">
                        <i class="fas fa-copy absolute right-1 top-0.5 cursor-pointer text-sm z-10" @click="copyMetadata(meta.raw)"></i>
                        <i class="fas fa-map-marked-alt absolute right-6 top-0.5 cursor-pointer text-sm z-10" @click="viewVectors(meta.value)" v-if="findVectors(meta.value)"></i>

                        <p class="font-semibold mb-1 font-mono cursor-pointer relative" @click="meta.open = !meta.open">
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

                    <img :src="image.value" class="w-40 rounded bg-gray-400 dark:bg-gray-800 p-3" v-handle-error />
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
import Modal from './Modal.vue';
import HashResolver from './HashResolver.vue';

import hljs from 'highlight.js/lib/core';
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
    "maxDistance": "m",
    "totalTravelled": "m",
    "calculatedSpeed": "m/s",
    "maxSpeed": "m/s",
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
    "devices": data => data.split("\n"),

    "changes": data => {
        // LS Customs logs
        if (typeof data === "string") {
            const changes = {};

            data.split("\n").forEach(line => {
                const regex = /^(.+?) (.+?) -> (.+?)$/gm,
                    match = regex.exec(line);

                if (match) {
                    const [_, key, oldValue, newValue] = match;

                    changes[key] = {
                        before: JSON.parse(oldValue),
                        after: JSON.parse(newValue)
                    };
                }
            });

            return changes;
        }

        // Anti-Cheat ped_change
        return data.map(change => change.replace(/(?<=^\w+: \d+ -> )\w+: (?=\d+$)/m, ""));
    }
};

const CustomFormatters = {
    "closestBlip": data => `"${data.distance.toFixed(1)}m - ${data.label}"`,
    "timings": data => {
        let lastTime;

        return data.map(timing => {
            const date = dayjs(timing.timestamp * 1000).format('MM/DD h:mm:ss A'),
                diff = lastTime ? (timing.time - lastTime) : false,
                diffStr = diff ? `${(diff < 0 ? '-' : '+') + Math.abs(diff)}ms` : '';

            lastTime = timing.time;

            return `${date} - ${timing.time}ms ${diffStr}`;
        }).join("\n");
    },
};

export default {
    name: "MetadataViewer",
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
        copyMetadata(text) {
            this.copyToClipboard(text);
        },
        findVectors(value) {
            const text = value.replace(/(<([^>]+)>)/gi, "");

            const rgx = /((\w+)\s*: )?vector\d\((-?\d+(\.\d+)?), (-?\d+(\.\d+)?)/g,
                vectors = [];

            let m;

            while ((m = rgx.exec(text)) !== null) {
                if (m.index === rgx.lastIndex) rgx.lastIndex++;

                const name = m[2],
                    x = parseFloat(m[3]),
                    y = parseFloat(m[5]);

                vectors.push({
                    x: x,
                    y: y,
                    label: name,
                });
            }

            if (!vectors.length) return false;

            return vectors;
        },
        viewVectors(value) {
            const vectors = this.findVectors(value);

            if (!vectors) return;

            window.open(this.buildMapUrl(true, vectors));
        },
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
                    } else if (key in CustomFormatters && value) {
                        value = CustomFormatters[key](value);
                    }

                    if (typeof value === "object") {
                        const label = key + (Array.isArray(value) ? ` (${value.length})` : "");

                        metadataJSON.push({
                            key: `metadata.${label}`,
                            value: this.highlightJSON(value),
                            raw: JSON.stringify(value),
                            open: false
                        });

                        delete metadata[key];
                    }
                }

                metadataJSON.unshift({
                    key: 'metadata',
                    value: this.highlightJSON(metadata),
                    raw: JSON.stringify(metadata),
                    open: true
                });
            }

            this.metadataJSON = metadataJSON;
            this.mainImageURL = this.image || screenshotURL;

            // Sort "before", "after", then alphabetically
            this.images.sort((a, b) => {
                if (a.key === "before" && b.key !== "before") return -1;
                if (b.key === "before" && a.key !== "before") return 1;

                return a.key.localeCompare(b.key);
            });
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

            const fmt = [];

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
            if (typeof object !== "object") {
                return object;
            } else if (object === null) {
                return `<span class="font-semibold hljs-number">null</span>`;
            }

            const isArray = Array.isArray(object),
                maxLine = Object.keys(object).map(k => k.length).reduce((a, b) => Math.max(a, b), 0);

            const lines = [];

            for (const key in object) {
                const type = key in KnownTypes && object[key] !== false ? KnownTypes[key] : null,
                    raw = object[key];

                let value = JSON.stringify(raw)
                    .replace(/{"x": ?(-?\d+(\.\d+)?), ?"y": ?(-?\d+(\.\d+)?)}/gm, "vector2($1, $3)") // vector2
                    .replace(/{"x": ?(-?\d+(\.\d+)?), ?"y": ?(-?\d+(\.\d+)?), ?"z": ?(-?\d+(\.\d+)?)}/gm, "vector3($1, $3, $5)") // vector3
                    .replace(/{"x": ?(-?\d+(\.\d+)?), ?"y": ?(-?\d+(\.\d+)?), ?"z": ?(-?\d+(\.\d+)?), ?"w": ?(-?\d+(\.\d+)?)}/gm, "vector4($1, $3, $5, $7)") // vector4
                    .replace(/(?<="):(?! |$)|,(?=")/gm, '$& ');

                value = hljs.highlight(value, { language: 'json' }).value;

                if (type) {
                    if (['ms', 's'].includes(type)) {
                        const actual = raw * (type === 'ms' ? 1 : 1000);

                        value = `<span class="hljs-number" title="${raw} ${type}">${this.msToTime(actual)}</span>`;
                    } else {
                        value += `<span class="text-gray-400 ml-0.5">${type}</span>`;
                    }
                } else if (raw === null) {
                    value = `<span class="font-semibold hljs-number">null</span>`;
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
