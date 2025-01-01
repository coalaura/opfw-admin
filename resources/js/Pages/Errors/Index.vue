<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t('errors.' + type + '.title') }}
            </h1>
            <p>
                {{ t('errors.' + type + '.description') }}
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

        <!-- Table -->
        <v-section class="overflow-x-auto !bg-code-background text-code-base">
            <template #header>
                <h2>
                    {{ t('errors.errors') }}
                </h2>
                <p class="text-code-muted text-xs">
                    {{ t('global.results', time) }}
                </p>
            </template>

            <template>
                <table class="w-full">
                    <tr class="border-t border-gray-300 dark:border-gray-500 hover:bg-gray-200 dark:hover:bg-gray-600 font-mono" :class="{ 'bg-pink-500 bg-opacity-20': error.error_feedback }" v-for="error in errors" :key="error.error_id">
                        <td class="px-2 py-1 mobile:block whitespace-nowrap italic" v-if="type === 'client'">
                            <a :href="`/players/${error.license_identifier}`" target="_blank">
                                {{ error.player_name }}
                            </a>
                        </td>
                        <td class="px-2 py-1 cursor-pointer mobile:block" @click="showError(error)" v-html="previewTrace(error)"></td>
                        <td class="px-2 py-1 mobile:block font-semibold whitespace-nowrap" :style="{ color: occurrenceColor(error.occurrences) }">{{ error.occurrences }}</td>
                        <td class="px-2 py-1 mobile:block whitespace-nowrap">{{ error.server_version || "N/A" }}</td>
                        <td class="px-2 py-1 mobile:block whitespace-nowrap text-right">{{ error.timestamp * 1000 | formatTime(true) }}</td>
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
                        <inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" v-if="errors.length === 50" :href="links.next">
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

        <modal :show.sync="isShowingDetails" extraClass="max-w-5xl">
            <template #header>
                <h1 class="dark:text-white">
                    {{ details.error_location }}

                    <div :title="t('errors.occurrences')" class="font-semibold absolute top-0.5 right-1.5 text-sm">{{ details.occurrences }}</div>
                </h1>
            </template>

            <template #default>
                <pre class="block text-sm whitespace-pre-line break-words bg-code-background text-code-base p-2" v-html="formatErrorTrace(details)"></pre>

                <template v-if="details.error_feedback">
                    <p class="m-0 mb-2 font-semibold mt-4 pt-4 border-gray-500 border-dashed border-t-2">{{ t('errors.feedback') }}:</p>

                    <pre class="block p-2 bg-code-background text-code-base text-sm whitespace-pre-line break-words">{{ details.error_feedback }}</pre>
                </template>
                <template v-else>
                    <p class="m-0 mb-2 font-semibold italic mt-4 pt-4 border-gray-500 border-dashed border-t-2">{{ t('errors.no_feedback') }}</p>
                </template>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="isShowingDetails = false">
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

import Rainbow from 'rainbowvis.js';

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
        },
        type: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            isLoading: false,

            isShowingDetails: false,
            details: false
        };
    },
    methods: {
        refresh: async function () {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;

            try {
                await this.$inertia.replace(`/errors/${this.type}`, {
                    data: this.filters,
                    preserveState: true,
                    preserveScroll: true,
                    only: ['errors', 'time', 'links', 'page'],
                });
            } catch (e) {
            }

            this.isLoading = false;
        },
        occurrenceColor(occurrences) {
            const severity = Math.min(100, Math.floor(occurrences / 20 * 100));
            const rainbow = new Rainbow();

            rainbow.setNumberRange(0, 100);
            rainbow.setSpectrum('#fcbab5', '#f8473a');

            return `#${rainbow.colourAt(severity)}`;
        },
        previewTrace(error) {
            return this.formatLine(error.error_trace.split("\n").shift());
        },
        showError(error) {
            this.isShowingDetails = true;
            this.details = error;
        },
        formatErrorTrace(error) {
            const lines = error.error_trace.split("\n");

            return lines.map((line, index) => {
                const number = (index + 1).toString();

                return `<div class="flex">
                    <div class="text-right mr-2 w-6 pr-1 text-code-muted flex-shrink-0 border-r border-code-border user-select-none">${number}</div>
                    <div>${this.formatLine(line)}</div>
                </div>`.replace(/\n\s+/g, '');
            }).join("");
        },
        formatLine(line) {
            line = line.trim();

            if (line === "(...tail calls...)") {
                return '<span class="text-code-muted italic">(...tail calls...)</span>';
            }if (line === "stack traceback:") {
                return '<span class="text-code-red font-semibold">stack traceback:</span>';
            }

            // Escape html
            line = line.replace(/</g, '&lt;').replace(/>/g, '&gt;');

            // Temporarily remove strings
            const strings = [];

            line = line.replace(/(?<!style=)(["'`])(.+?)\1(?!>)/g, match => {
                const index = strings.push(match) - 1;

                return `$STR${index}`;
            });

            // Remove chat colors
            line = line.replace(/\^[1-7]/g, '');

            // Wrapped in <>
            line = line.replace(/(&lt;)(.+?)(&gt;)/g, '<span class="text-code-green">$1$2$3</span>');

            // Numbers
            line = line.replace(/(<?!\$STR)\d+/g, '<span class="text-code-orange">$&</span>');

            // nil
            line = line.replace(/\bnil\b/g, '<span class="text-code-red">$&</span>');

            // Start till :
            line = line.replace(/^.+?:(?= |<)/m, '<span class="text-code-lightblue">$&</span>');

            // Return strings
            for (let i = 0; i < strings.length; i++) {
                line = line.replace(new RegExp(`\\$STR${i}`), `<span class="text-code-green">${strings[i]}</span>`);
            }

            return line;
        }
    }
};
</script>
