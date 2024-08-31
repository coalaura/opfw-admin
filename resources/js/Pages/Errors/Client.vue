<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t('errors.client.title') }}
            </h1>
            <p>
                {{ t('errors.client.description') }}
            </p>
        </portal>

        <portal to="actions">
            <button class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" type="button" @click="refresh">
                <span v-if="!isLoading">
                    <i class="fa fa-redo-alt mr-1"></i>
                    {{ t('global.refresh') }}
                </span>
                <span v-else>
                    <i class="fas fa-spinner animate-spin mr-1"></i>
                    {{ t('global.loading') }}
                </span>
            </button>
        </portal>

        <!-- Querying -->
        <v-section :noFooter="true">
            <template #header>
                <h2>
                    {{ t('logs.filter') }}
                </h2>
            </template>

            <template>
                <form @submit.prevent>
                    <div class="flex flex-wrap mb-4">
                        <!-- Details -->
                        <div class="w-1/2 px-3">
                            <label class="block mb-3 mt-3" for="trace">
                                {{ t('errors.trace') }} <sup class="text-muted dark:text-dark-muted">*</sup>
                            </label>
                            <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="trace" placeholder="attempted to index a nil value" v-model="filters.trace">
                        </div>

                        <!-- Version -->
                        <div class="w-1/2 px-3">
                            <label class="block mb-3 mt-3">
                                {{ t('errors.server_version') }}
                            </label>
                            <select v-model="filters.server_version" class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600">
                                <option :value="null">{{ t('errors.no_version_filter') }}</option>
                                <option :value="version.server_version" v-for="version in versions">{{ version.server_version }} - {{ version.timestamp * 1000 | formatTime(true) }}</option>
                            </select>
                        </div>
                    </div>
                    <!-- Description -->
                    <div class="w-full px-3 mt-3">
                        <small class="text-muted dark:text-dark-muted mt-1 leading-4 block" v-html="t('global.search.custom')"></small>
                    </div>
                    <!-- Search button -->
                    <div class="w-full px-3 mt-3">
                        <button class="px-5 py-2 font-semibold text-white bg-success dark:bg-dark-success rounded hover:shadow-lg" @click="refresh">
                            <span v-if="!isLoading">
                                <i class="fas fa-search"></i>
                                {{ t('errors.search') }}
                            </span>
                            <span v-else>
                                <i class="fas fa-cog animate-spin"></i>
                                {{ t('global.loading') }}
                            </span>
                        </button>
                    </div>
                </form>
            </template>
        </v-section>

        <!-- Table -->
        <v-section class="overflow-x-auto">
            <template #header>
                <h2>
                    {{ t('errors.errors') }}
                </h2>
                <p class="text-muted dark:text-dark-muted text-xs">
                    {{ t('global.results', time) }}
                </p>
            </template>

            <template>
                <table class="w-full">
                    <tr class="font-semibold text-left mobile:hidden">
                        <th class="p-3 pl-8">{{ t('errors.player') }}</th>
                        <th class="p-3">{{ t('errors.location') }}</th>
                        <th class="p-3">{{ t('errors.trace') }}</th>
                        <th class="p-3">{{ t('errors.occurrences') }}</th>
                        <th class="p-3">{{ t('errors.server_version') }}</th>
                        <th class="p-3 pr-8">{{ t('errors.timestamp') }}</th>
                    </tr>
                    <tr class="border-t border-gray-300 dark:border-gray-500 hover:bg-gray-200 dark:hover:bg-gray-600" :class="{ 'bg-pink-500 bg-opacity-20': error.error_feedback }" v-for="error in parsedErrors" :key="error.error_id">
                        <td class="p-3 pl-8 mobile:block">
                            <inertia-link class="block px-4 py-2 font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="'/players/' + error.license_identifier">
                                {{ playerName(error.license_identifier) }} [{{ error.server_id }}]
                            </inertia-link>
                        </td>
                        <td class="p-3 mobile:block whitespace-nowrap font-mono">{{ getErrorLocation(error) }}</td>
                        <td class="p-3 mobile:block font-mono text-sm cursor-pointer whitespace-pre-line" @click="showError(error)" v-html="cleanupTrace(error.error_trace)"></td>
                        <td class="p-3 mobile:block">{{ error.occurrences }}</td>
                        <td class="p-3 mobile:block whitespace-nowrap">{{ error.server_version || "N/A" }}</td>
                        <td class="p-3 pr-8 mobile:block whitespace-nowrap">{{ error.timestamp * 1000 | formatTime(true) }}</td>
                    </tr>
                    <tr v-if="errors.length === 0" class="border-t border-gray-300 dark:border-gray-500">
                        <td class="px-8 py-3 text-center" colspan="100%">
                            {{ t('errors.no_errors') }}
                        </td>
                    </tr>
                </table>
            </template>

            <template #footer>
                <div class="flex items-center justify-between mt-6 mb-1">

                    <!-- Navigation -->
                    <div class="flex flex-wrap">
                        <inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="links.prev" v-if="page >= 2">
                            <i class="mr-1 fas fa-arrow-left"></i>
                            {{ t("pagination.previous") }}
                        </inertia-link>
                        <inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" v-if="errors.length === 15" :href="links.next">
                            {{ t("pagination.next") }}
                            <i class="ml-1 fas fa-arrow-right"></i>
                        </inertia-link>
                    </div>

                    <!-- Meta -->
                    <div class="font-semibold">
                        {{ t("pagination.page", page) }}
                    </div>

                </div>
            </template>
        </v-section>

        <modal :show.sync="showErrorDetail">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('errors.detail') }}
                    <sup :title="t('errors.occurrences')">{{ errorDetail.occurrences }}</sup>
                </h1>
            </template>

            <template #default>
                <pre class="text-lg block mb-4 pb-4 border-gray-500 border-dashed border-b-2 font-bold whitespace-pre-line">
                    {{ getErrorLocation(errorDetail) }}
                </pre>
                <pre class="block mb-4 pb-4 border-gray-500 border-dashed border-b-2 text-sm whitespace-pre-line break-words" v-html="formatChatColors(errorDetail.error_trace)"></pre>
                <div class="text-lg mb-4 pb-4 border-gray-500 border-dashed border-b-2" v-if="errorDetail.full_trace">
                    <pre class="block whitespace-pre-line break-words lines" v-html="lineNumbers(errorDetail.full_trace)"></pre>
                </div>
                <p class="m-0 mb-2 font-bold">{{ t('errors.feedback') }}:</p>
                <pre class="block mb-4 text-sm whitespace-pre-line break-words">
                    {{ errorDetail.error_feedback || "N/A" }}
                </pre>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="showErrorDetail = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

    </div>
</template>

<script>
import Layout from './../../Layouts/App';
import VSection from './../../Components/Section';
import Pagination from './../../Components/Pagination';
import Modal from './../../Components/Modal';

export default {
    layout: Layout,
    components: {
        Pagination,
        Modal,
        VSection,
    },
    props: {
        errors: {
            type: Array,
            required: true,
        },
        versions: {
            type: Array,
            required: true,
        },
        filters: {
            trace: String,
            server_version: String,
        },
        playerMap: {
            type: Object,
            required: true,
        },
        links: {
            type: Object,
            required: true,
        },
        page: {
            type: Number,
            required: true,
        },
        time: {
            type: Number,
            required: true,
        }
    },
    data() {
        return {
            isLoading: false,
            isCreatingCycle: false,
            showErrorDetail: false,
            errorDetail: null
        };
    },
    computed: {
        parsedErrors() {
            return this.errors.map(error => {
                try {
                    error.full_trace = JSON.parse(error.full_trace);
                } catch (e) { }

                return error;
            })
        }
    },
    methods: {
        refresh: async function () {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;

            try {
                await this.$inertia.replace('/errors/client', {
                    data: this.filters,
                    preserveState: true,
                    preserveScroll: true,
                    only: ['errors', 'versions', 'playerMap', 'time', 'links', 'page'],
                });
            } catch (e) {
            }

            this.isLoading = false;
        },
        cleanupTrace(trace) {
            if (!trace) return 'no trace';

            const cleaned = trace.replace(/^.+:\d+: /gm, '').trim();

            return this.formatChatColors(cleaned ? cleaned : trace);
        },
        lineNumbers(fullTrace) {
            const padSize = (fullTrace.length % 10) + 1;

            const cleanMatch = part => {
                return part ? part.replace(/^[/:@]/gm, match => {
                    return `<span style="opacity:0.6;font-style:normal">${match}</span>`;
                }) : '';
            };

            const formatLine = entry => {
                if (entry.startsWith("event@")) {
                    const eventName = entry.replace(/^event@/gm, '');

                    return `<span style="color:#B4BEFE">event<span style="opacity:0.6;font-style:normal">@</span></span><span style="color:#A6E3A1">${eventName}</span>`;
                } else if (entry.startsWith("callback@")) {
                    const callbackData = entry.replace(/^callback@/gm, '').split("("),
                        callbackName = callbackData.shift(),
                        callbackArgs = callbackData.join("(").replace(/\)$/, '');

                    const argRegex = /\b(?:function|vec3)\([^)]*\)|"(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*'|\S+/g,
                        args = [];

                    let match;

                    while (match = argRegex.exec(callbackArgs)) {
                        const arg = match[0].replace(/,$/, '');

                        if (arg) {
                            args.push(arg);
                        }
                    }

                    const argString = args.map(arg => `<span style="color:#BEE4ED;font-weight:600">${arg}</span>`).join(`<span style="color:#F38BA8;opacity:0.6;font-style:normal">,</span> `);

                    return [
                        `<span style="color:#B4BEFE">callback<span style="opacity:0.6;font-style:normal">@</span></span>`,
                        `<span style="color:#F38BA8">${callbackName}<span style="opacity:0.6;font-style:normal">(</span></span>`,
                        `${argString}`,
                        `<span style="color:#F38BA8;opacity:0.6;font-style:normal">)</span>`,
                    ].join("");
                }

                const match = entry.matchAll(/^(.+?)(\/.+?)?(:\d+)?(@.+)?$/gm).next().value;
                if (!match) {
                    return entry;
                }

                const [_, resource, file, line, method] = match;

                return [
                    `<span style="color:#B4BEFE">${resource}</span>`,
                    `<span style="color:#A6E3A1">${cleanMatch(file)}</span>`,
                    `<span style="color:#FAB387">${cleanMatch(line)}</span>`,
                    `<span style="color:#F38BA8">${cleanMatch(method)}</span>`,
                ].join("");
            };

            return fullTrace.map((line, index) => {
                return `<span><span class="line-number">${(index + 1).toString().padStart(padSize, '0')}</span>${formatLine(line)}</span>`;
            }).join("\n");
        },
        getErrorLocation(error) {
            const fullTrace = error.full_trace;

            if (fullTrace) {
                for (const entry of fullTrace) {
                    if (entry.match(/^(C|citizen)[:@]/gm)) {
                        continue;
                    }

                    const match = entry.match(/(?<=^\[.+?] |^)([\w_-]+?\/){1,2}[\w_-]+/m);

                    if (match && match.length > 0) {
                        const location = match[0].match(/[\w_-]+\/[\w_-]+$/m);

                        return location[0];
                    }

                    const oldMatch = entry.match(/(?<=\^0\(\^4)\w+\/\w+(?=:)/);

                    if (oldMatch && oldMatch.length > 0) {
                        return oldMatch[0];
                    }
                }
            }

            return error.error_location;
        },
        showError(error) {
            this.showErrorDetail = true;
            this.errorDetail = error;
        },
        playerName(licenseIdentifier) {
            return licenseIdentifier in this.playerMap ? this.playerMap[licenseIdentifier] : licenseIdentifier;
        },
        trim(str, length) {
            if (str.length > length) {
                return str.substr(0, length) + '...';
            }
            return str;
        },
        formatChatColors(text) {
            if (!text) return 'no trace';

            const colors = {
                "^1": "#FD4343",
                "^2": "#99CC00",
                "^3": "#F8B633",
                "^4": "#0393C3",
                "^5": "#33AFDD",
                "^6": "#A363C3",
                // ^7 is white (reset)
                "^8": "#B90606",
                "^9": "#B90661"
            };

            let matches = 0;

            function reset() {
                if (matches === 0) return '';

                const open = matches;

                matches = 0;

                return '</span>'.repeat(open);
            }

            text = text.replace(/\^[1-9]/gm, (match) => {
                if (match === "^7") return reset();

                matches++;
                return '<span style="color:' + colors[match] + '">';
            });

            text += reset();

            return text;
        }
    }
};
</script>
