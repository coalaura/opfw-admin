<template>
    <div>
        <portal to="title">
            <h1 class="dark:text-white">
                <i class="mr-3 fas fa-bomb" :title="perm.restriction(perm.PERM_EXPLOSION_EVENTS)"></i>
                {{ t('explosion_events.title') }}
            </h1>
            <p>{{ t('explosion_events.description') }}</p>
        </portal>

        <portal to="actions">
            <button class="px-4 py-2 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" :disabled="isLoading" @click="refresh">
                <i class="mr-1 fas fa-sync" :class="{ 'animate-spin': isLoading }"></i>
                {{ t('global.refresh') }}
            </button>
        </portal>

        <v-section :noFooter="true">
            <template #header>
                <h2>{{ t('global.filter') }}</h2>
            </template>

            <form @submit.prevent="refresh" autocomplete="off">
                <div class="flex flex-wrap mb-4">
                    <div v-for="field in fields" :key="field.key" class="w-1/4 px-3 mb-3 mobile:w-full">
                        <label class="block mb-2" :for="field.key">{{ t(field.label) }}</label>
                        <input class="w-full px-4 py-3 bg-gray-200 dark:bg-gray-600 border rounded" :id="field.key" v-model="form[field.key]" :type="field.type" :min="field.min" :step="field.step" :maxlength="field.maxlength" :placeholder="field.placeholder" />
                    </div>

                    <div v-for="key in ['canceled', 'no_damage']" :key="key" class="w-1/4 px-3 mb-3 mobile:w-full">
                        <label class="block mb-2" :for="key">{{ t(`explosion_events.${key}`) }}</label>
                        <select class="w-full px-4 py-3 bg-gray-200 dark:bg-gray-600 border rounded" :id="key" v-model="form[key]">
                            <option value="">{{ t('global.any') }}</option>
                            <option value="1">{{ t('global.yes') }}</option>
                            <option value="0">{{ t('global.no') }}</option>
                        </select>
                    </div>

                    <div class="w-1/2 px-3 mb-3 mobile:w-full">
                        <label class="block mb-2" for="after">{{ t('explosion_events.after') }}</label>
                        <input class="w-full px-4 py-3 bg-gray-200 dark:bg-gray-600 border rounded" id="after" type="datetime-local" step="0.001" v-model="after" />
                    </div>
                    <div class="w-1/2 px-3 mb-3 mobile:w-full">
                        <label class="block mb-2" for="before">{{ t('explosion_events.before') }}</label>
                        <input class="w-full px-4 py-3 bg-gray-200 dark:bg-gray-600 border rounded" id="before" type="datetime-local" step="0.001" v-model="before" />
                    </div>

                    <div v-if="Object.keys(errors).length" class="w-full px-3 mb-3 text-red-600 dark:text-red-400" role="alert">
                        <p v-for="(messages, key) in errors" :key="key">{{ messages.join(' ') }}</p>
                    </div>
                    <p v-if="requestError" class="w-full px-3 mb-3 text-red-600 dark:text-red-400" role="alert">{{ requestError }}</p>

                    <div class="w-full px-3 mt-1">
                        <button type="submit" class="px-5 py-2 font-semibold text-white bg-success dark:bg-dark-success rounded hover:shadow-lg" :disabled="isLoading">
                            <i :class="isLoading ? 'fas fa-cog animate-spin' : 'fas fa-search'"></i>
                            {{ t(isLoading ? 'global.loading' : 'global.do_search') }}
                        </button>
                    </div>
                </div>
            </form>
        </v-section>

        <v-section class="overflow-x-auto">
            <template #header>
                <h2>{{ t('logs.logs') }}</h2>
            </template>

            <table class="w-full">
                <thead>
                    <tr class="font-semibold text-left">
                        <th class="p-3 pl-8">ID</th>
                        <th class="p-3">{{ t('suspicious.player') }}</th>
                        <th class="p-3">{{ t('explosion_events.type') }}</th>
                        <th class="p-3">{{ t('explosion_events.canceled') }}</th>
                        <th class="p-3">{{ t('suspicious.distance') }}</th>
                        <th class="p-3">{{ t('explosion_events.damage_scale') }}</th>
                        <th class="p-3">{{ t('explosion_events.position') }}</th>
                        <th class="p-3">{{ t('explosion_events.minigame') }}</th>
                        <th class="p-3">{{ t('suspicious.time') }}</th>
                        <th class="p-3 pr-8">{{ t('explosion_events.details') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="log in logs" :key="log.id" class="border-t border-gray-300 dark:border-gray-500 hover:bg-gray-200 dark:hover:bg-gray-600">
                        <td class="p-3 pl-8 font-mono">{{ log.id }}</td>
                        <td class="p-3 max-w-56">
                            <inertia-link v-if="log.license_identifier" class="block px-4 py-2 font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400 truncate" :href="`/players/${log.license_identifier}`" :title="log.license_identifier">
                                {{ playerMap[log.license_identifier] || log.license_identifier }}
                            </inertia-link>
                            <span v-else>{{ t('global.unknown') }}</span>
                        </td>
                        <td class="p-3">{{ log.explosion_type == null ? '?' : log.explosion_type }}</td>
                        <td class="p-3">{{ booleanLabel(log.canceled) }}</td>
                        <td class="p-3 whitespace-nowrap">{{ formatNumber(log.distance) }} m</td>
                        <td class="p-3">{{ formatNumber(log.damage_scale) }}</td>
                        <td class="p-3 whitespace-nowrap font-mono text-sm">
                            {{ formatNumber(log.explosion_pos_x) }}, {{ formatNumber(log.explosion_pos_y) }}, {{ formatNumber(log.explosion_pos_z) }}
                        </td>
                        <td class="p-3">{{ log.minigame == null ? t('global.unknown') : (log.minigame || t('global.none')) }}</td>
                        <td class="p-3 whitespace-nowrap text-sm">{{ formatTimestamp(log.timestamp) }}</td>
                        <td class="p-3 pr-8">
                            <button class="px-4 py-2 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400 whitespace-nowrap" @click="showDetails(log)">
                                {{ t('logs.metadata.show') }}
                            </button>
                        </td>
                    </tr>
                    <tr v-if="logs.length === 0" class="border-t border-gray-300 dark:border-gray-500">
                        <td class="px-8 py-3 text-center" colspan="10">{{ t('explosion_events.no_logs') }}</td>
                    </tr>
                </tbody>
            </table>

            <template #footer>
                <div class="flex items-center justify-between mt-6 mb-1">
                    <div class="flex flex-wrap">
                        <inertia-link v-if="links.prev" class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="links.prev">
                            <i class="mr-1 fas fa-arrow-left"></i>
                            {{ t('pagination.previous') }}
                        </inertia-link>
                        <inertia-link v-if="links.next" class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="links.next">
                            {{ t('pagination.next') }}
                            <i class="ml-1 fas fa-arrow-right"></i>
                        </inertia-link>
                    </div>
                    <div class="font-semibold">{{ t('pagination.page', page) }}</div>
                </div>
            </template>
        </v-section>

        <modal :show.sync="detailsOpen">
            <template #header>
                <h2 class="dark:text-white">{{ t('explosion_events.details') }} #{{ selectedEvent ? selectedEvent.id : '' }}</h2>
            </template>
            <pre class="text-sm whitespace-pre-wrap break-words">{{ eventDetails }}</pre>
            <template #actions>
                <button class="px-4 py-2 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" @click="detailsOpen = false">{{ t('global.close') }}</button>
            </template>
        </modal>
    </div>
</template>

<script>
import dayjs from 'dayjs';
import Layout from './../../Layouts/App.vue';
import VSection from './../../Components/Section.vue';
import Modal from './../../Components/Modal.vue';

export default {
    layout: Layout,
    components: {
        VSection: VSection,
        Modal: Modal,
    },
    props: {
        logs: { type: Array, required: true },
        filters: { type: Object, required: true },
        errors: { type: Object, required: true },
        links: { type: Object, required: true },
        page: { type: Number, required: true },
        playerMap: { type: Object, required: true },
    },
    data() {
        return {
            form: {},
            after: '',
            before: '',
            isLoading: false,
            requestError: '',
            detailsOpen: false,
            selectedEvent: null,
            fields: [
                { key: 'license', label: 'suspicious.license_identifier', type: 'text', maxlength: 50, placeholder: 'license:...' },
                { key: 'explosion_type', label: 'explosion_events.type', type: 'number', step: 1 },
                { key: 'minigame', label: 'explosion_events.minigame', type: 'text', maxlength: 32 },
                { key: 'weapon_hash', label: 'explosion_events.weapon_hash', type: 'number', step: 1 },
                { key: 'min_distance', label: 'explosion_events.min_distance', type: 'number', min: 0, step: 'any' },
                { key: 'max_distance', label: 'explosion_events.max_distance', type: 'number', min: 0, step: 'any' },
            ],
        };
    },
    computed: {
        eventDetails() {
            return this.selectedEvent ? JSON.stringify(this.selectedEvent, null, 2) : '';
        },
    },
    watch: {
        filters: {
            immediate: true,
            handler(filters) {
                this.form = {
                    license: filters.license ?? '',
                    explosion_type: filters.explosion_type ?? '',
                    minigame: filters.minigame ?? '',
                    weapon_hash: filters.weapon_hash ?? '',
                    min_distance: filters.min_distance ?? '',
                    max_distance: filters.max_distance ?? '',
                    canceled: filters.canceled ?? '',
                    no_damage: filters.no_damage ?? '',
                };
                this.after = this.localDate(filters.after);
                this.before = this.localDate(filters.before);
            },
        },
    },
    methods: {
        localDate(timestamp) {
            return timestamp == null || timestamp === '' ? '' : dayjs(Number(timestamp)).format('YYYY-MM-DDTHH:mm:ss.SSS');
        },
        formatTimestamp(timestamp) {
            return timestamp == null ? this.t('global.unknown') : dayjs(Number(timestamp)).format('M/D/YYYY, h:mm:ss.SSS A');
        },
        formatNumber(value) {
            return value == null ? '?' : Number(value).toFixed(2);
        },
        booleanLabel(value) {
            return this.t(value == null ? 'global.unknown' : (value ? 'global.yes' : 'global.no'));
        },
        showDetails(log) {
            this.selectedEvent = log;
            this.detailsOpen = true;
        },
        async refresh() {
            if (this.isLoading) {
                return;
            }

            this.requestError = '';
            const after = this.after ? new Date(this.after).getTime() : '';
            const before = this.before ? new Date(this.before).getTime() : '';

            if (Number.isNaN(after) || Number.isNaN(before) || (after !== '' && before !== '' && after > before)) {
                this.requestError = this.t('explosion_events.invalid_dates');
                return;
            }

            if (this.form.min_distance !== '' && this.form.max_distance !== '' && Number(this.form.min_distance) > Number(this.form.max_distance)) {
                this.requestError = this.t('explosion_events.invalid_distance');
                return;
            }

            this.isLoading = true;

            try {
                await this.$inertia.replace('/explosions', {
                    data: { ...this.form, after: after, before: before },
                    preserveState: true,
                    preserveScroll: true,
                    only: ['logs', 'filters', 'errors', 'links', 'page', 'playerMap'],
                });
            } catch (error) {
                this.requestError = this.t('explosion_events.load_failed');
            } finally {
                this.isLoading = false;
            }
        },
    },
};
</script>
