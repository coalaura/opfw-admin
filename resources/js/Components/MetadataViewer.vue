<template>
    <modal :show="show">
        <template #header>
            <h1 class="dark:text-white">
                {{ title }}
            </h1>
        </template>

        <template #default>
            <video v-if="imageURL && (['mp4', 'webm'].includes(imageURL.split('.').pop()))" :src="imageURL" controls class="w-full mb-3"></video>
            <img :src="imageURL" v-else-if="imageURL" class="w-full mb-3" />

            <slot></slot>

            <hashResolver>
                <template #default>
                    <div class="mt-4" v-for="meta in metadataJSON">
                        <p class="font-semibold mb-1 font-mono">{{ meta.key }}</p>
                        <pre class="text-xs whitespace-pre-wrap py-2 px-3 bg-gray-200 dark:bg-gray-800 rounded-sm hljs" v-html="meta.value" v-if="meta.value"></pre>
                        <pre class="text-xs whitespace-pre-wrap py-2 px-3 bg-gray-200 dark:bg-gray-800 rounded-sm italic" v-else>empty</pre>
                    </div>
                </template>
            </hashResolver>
        </template>

        <template #actions>
            <button type="button"
                    class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400"
                    @click="close">
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
    "allowedSpeed": "m/s"
};

export default {
    name: 'MetadataViewer',
    props: {
        title: String,
        image: String,
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
            imageURL: null
        };
    },
    mounted() {
        this.updateMetadata();
    },
    methods: {
        updateMetadata() {
            const metadataJSON = [];

            if (this.metadata) {
                const metadata = this.cleanupObject(this.metadata);

                for (const key in metadata) {
                    let value = metadata[key];

                    if (key === "trace" || key === "modifications") {
                        value = value.split("\n");
                    }

                    if (typeof value === "object") {
                        metadataJSON.push({
                            key: `metadata.${key}`,
                            value: this.highlightJSON(value)
                        });

                        delete metadata[key];
                    }
                }

                metadataJSON.unshift({
                    key: 'metadata',
                    value: this.highlightJSON(metadata)
                });
            }

            this.metadataJSON = metadataJSON;
            this.imageURL = this.image || this.metadata?.screenshotURL;
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
        highlightJSON(object) {
            const isArray = Array.isArray(object),
                maxLine = Object.keys(object).map(k => k.length).reduce((a, b) => Math.max(a, b), 0);

            const lines = [];

            for (const key in object) {
                let value = JSON.stringify(object[key])
                    .replace(/{"x":(-?\d+\.\d+),"y":(-?\d+\.\d+)}/gm, 'vector2($1, $2)') // vector2
                    .replace(/{"x":(-?\d+\.\d+),"y":(-?\d+\.\d+),"z":(-?\d+\.\d+)}/gm, 'vector3($1, $2, $3)') // vector3
                    .replace(/{"x":(-?\d+\.\d+),"y":(-?\d+\.\d+),"z":(-?\d+\.\d+),"w":(-?\d+\.\d+)}/gm, 'vector4($1, $2, $3, $4)') // vector4
                    .replace(/(?<="):(?! |$)|,(?=")/gm, '$& ');

                value = hljs.highlight(value, {language: 'json'}).value;

                let line = isArray ? value : `<b>${key.padEnd(maxLine, " ")}</b>: ${value}`;

                if (key in KnownTypes && object[key] !== false) {
                    line += `<span class="text-gray-400 ml-0.5">${KnownTypes[key]}</span>`;
                }

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