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
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-12 items-end gap-x-6 gap-y-4 mb-4">
                    <div class="min-w-0 sm:col-span-2 xl:col-span-5">
                        <label class="block mb-2" for="license">{{ t('suspicious.license_identifier') }}</label>
                        <input class="w-full h-12 px-4 py-3 bg-gray-200 dark:bg-gray-600 border rounded" id="license" v-model="form.license" type="text" maxlength="50" placeholder="license:..." />
                    </div>

                    <div class="min-w-0 sm:col-span-2 xl:col-span-3">
                        <label class="block mb-2" for="explosion_type">{{ t('explosion_events.type') }}</label>
                        <select class="w-full h-12 px-4 py-3 bg-gray-200 dark:bg-gray-600 border rounded" id="explosion_type" v-model="form.explosion_type">
                            <option value="">{{ t('global.any') }}</option>
                            <option v-if="form.explosion_type !== '' && !explosionTypes[form.explosion_type]" :value="form.explosion_type">{{ typeLabel(form.explosion_type) }}</option>
                            <option v-for="type in typeOptions" :key="type.id" :value="type.id">{{ type.name }} ({{ type.id }})</option>
                        </select>
                    </div>

                    <div class="min-w-0 xl:col-span-2">
                        <label class="block mb-2" for="minigame">{{ t('explosion_events.minigame') }}</label>
                        <select class="w-full h-12 px-4 py-3 bg-gray-200 dark:bg-gray-600 border rounded" id="minigame" v-model="form.minigame">
                            <option value="">{{ t('global.any') }}</option>
                            <option value="none">{{ t('logs.minigame_none') }}</option>
                            <option v-for="minigame in minigames" :key="minigame" :value="minigame">{{ t(`logs.minigame_${minigame}`) }}</option>
                        </select>
                    </div>

                    <div class="min-w-0 xl:col-span-2">
                        <label class="block mb-2" for="weapon_hash">{{ t('explosion_events.weapon_hash') }}</label>
                        <input class="w-full h-12 px-4 py-3 bg-gray-200 dark:bg-gray-600 border rounded" id="weapon_hash" v-model="form.weapon_hash" type="number" step="1" />
                    </div>

                    <div class="min-w-0 xl:col-span-4">
                        <label class="block mb-2" for="after">{{ t('explosion_events.after') }}</label>
                        <input class="w-full min-w-0 h-12 px-4 py-3 bg-gray-200 dark:bg-gray-600 border rounded" id="after" type="datetime-local" step="0.001" v-model="after" />
                    </div>
                    <div class="min-w-0 xl:col-span-4">
                        <label class="block mb-2" for="before">{{ t('explosion_events.before') }}</label>
                        <input class="w-full min-w-0 h-12 px-4 py-3 bg-gray-200 dark:bg-gray-600 border rounded" id="before" type="datetime-local" step="0.001" v-model="before" />
                    </div>

                    <div v-for="key in ['min_distance', 'max_distance']" :key="key" class="min-w-0 xl:col-span-2">
                        <label class="block mb-2" :for="key">{{ t(`explosion_events.${key}`) }}</label>
                        <input class="w-full h-12 px-4 py-3 bg-gray-200 dark:bg-gray-600 border rounded" :id="key" v-model="form[key]" type="number" min="0" step="any" />
                    </div>

                    <div v-for="key in ['canceled', 'no_damage']" :key="key" class="min-w-0 xl:col-span-2">
                        <label class="block mb-2" :for="key">{{ t(`explosion_events.${key}`) }}</label>
                        <select class="w-full h-12 px-4 py-3 bg-gray-200 dark:bg-gray-600 border rounded" :id="key" v-model="form[key]">
                            <option value="">{{ t('global.any') }}</option>
                            <option value="1">{{ t('global.yes') }}</option>
                            <option value="0">{{ t('global.no') }}</option>
                        </select>
                    </div>

                    <fieldset class="min-w-0 sm:col-span-2 xl:col-span-8">
                        <legend class="mb-2">{{ t('explosion_events.quick_filters.title') }}</legend>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="filter in quickFilters" :key="filter" type="button" class="flex-1 h-12 px-3 text-sm font-semibold border rounded whitespace-nowrap" :class="form.category === filter ? 'bg-indigo-600 border-indigo-600 text-white dark:bg-indigo-400 dark:border-indigo-400' : 'bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500'" :aria-pressed="form.category === filter" :title="t(`explosion_events.quick_filters.${filter || 'all'}_description`)" @click="form.category = form.category === filter ? '' : filter">
                                {{ t(`explosion_events.quick_filters.${filter || 'all'}`) }}
                            </button>
                        </div>
                    </fieldset>

                    <div v-if="Object.keys(errors).length" class="col-span-full text-red-600 dark:text-red-400" role="alert">
                        <p v-for="(messages, key) in errors" :key="key">{{ messages.join(' ') }}</p>
                    </div>
                    <p v-if="requestError" class="col-span-full text-red-600 dark:text-red-400" role="alert">{{ requestError }}</p>

                    <div class="col-span-full">
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
                        <th class="p-3">{{ t('explosion_events.position') }}</th>
                        <th class="p-3">{{ t('suspicious.time') }}</th>
                        <th class="p-3 pr-8">{{ t('explosion_events.details') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="log in logs" :key="log.id" class="border-t border-gray-300 dark:border-gray-500" :class="rowClass(log)">
                        <td class="p-3 pl-8 font-mono whitespace-nowrap">
                            <i v-if="log.minigame" class="mr-2 text-purple-800 dark:text-purple-200 fas fa-gamepad" :title="log.minigame" :aria-label="`${t('explosion_events.minigame')}: ${log.minigame}`"></i>
                            {{ log.id }}
                        </td>
                        <td class="p-3 max-w-56">
                            <inertia-link v-if="log.license_identifier" class="block px-4 py-2 font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400 truncate" :href="`/players/${log.license_identifier}`" :title="log.license_identifier">
                                {{ playerMap[log.license_identifier] || log.license_identifier }}
                            </inertia-link>
                            <span v-else>{{ t('global.unknown') }}</span>
                        </td>
                        <td class="p-3">
                            <div class="font-mono text-sm">{{ typeLabel(log.explosion_type) }}</div>
                            <span v-if="typeCategory(log.explosion_type) !== 'other'" class="inline-block px-2 py-1 mt-1 text-xs font-semibold rounded whitespace-nowrap" :class="categoryClass(log.explosion_type)">
                                <i v-if="typeCategory(log.explosion_type) === 'dangerous'" class="mr-1 fas fa-exclamation-triangle" aria-hidden="true"></i>
                                {{ t(`explosion_events.categories.${typeCategory(log.explosion_type)}`) }}
                            </span>
                        </td>
                        <td class="p-3">
                            <span v-if="log.canceled" class="inline-block px-2 py-1 text-xs font-semibold rounded whitespace-nowrap bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                <i class="mr-1 fas fa-ban" aria-hidden="true"></i>
                                {{ t('explosion_events.canceled') }}
                            </span>
                            <span v-else>{{ booleanLabel(log.canceled) }}</span>
                        </td>
                        <td class="p-3 whitespace-nowrap">{{ formatNumber(log.distance) }} m</td>
                        <td class="p-3 whitespace-nowrap font-mono text-sm">
                            {{ formatNumber(log.explosion_pos_x) }}, {{ formatNumber(log.explosion_pos_y) }}, {{ formatNumber(log.explosion_pos_z) }}
                        </td>
                        <td class="p-3 whitespace-nowrap text-sm">{{ formatTimestamp(log.timestamp) }}</td>
                        <td class="p-3 pr-8">
                            <button class="px-4 py-2 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400 whitespace-nowrap" @click="showDetails(log)">
                                {{ t('logs.metadata.show') }}
                            </button>
                        </td>
                    </tr>
                    <tr v-if="logs.length === 0" class="border-t border-gray-300 dark:border-gray-500">
                        <td class="px-8 py-3 text-center" colspan="8">{{ t('explosion_events.no_logs') }}</td>
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
            <p v-if="selectedEvent" class="mb-3 font-mono text-sm">{{ typeLabel(selectedEvent.explosion_type) }}</p>
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
        explosionTypes: { type: Object, required: true },
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
            quickFilters: ['', 'hide_ignorable', 'hide_common', 'dangerous'],
            minigames: ['arena', 'battle_royale', 'zombie_pill', 'training'],
        };
    },
    computed: {
        typeOptions() {
            return Object.entries(this.explosionTypes).map(([id, type]) => ({ id: Number(id), name: type.name })).sort((first, second) => first.id - second.id);
        },
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
                    category: filters.category ?? '',
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
        typeLabel(id) {
            const name = this.explosionTypes[id]?.name ?? this.t('global.unknown');

            return id == null ? name : `${name} (${id})`;
        },
        typeCategory(id) {
            return this.explosionTypes[id]?.category ?? 'other';
        },
        categoryClass(id) {
            switch (this.typeCategory(id)) {
                case 'dangerous':
                    return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                case 'common':
                    return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
                default:
                    return 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200';
            }
        },
        rowClass(log) {
            if (log.minigame) {
                return 'bg-purple-500 !bg-opacity-20 hover:!bg-opacity-40';
            }

            if (this.typeCategory(log.explosion_type) === 'dangerous') {
                return 'bg-red-500 !bg-opacity-20 hover:!bg-opacity-40';
            }

            if (log.canceled) {
                return 'bg-yellow-500 !bg-opacity-20 hover:!bg-opacity-40';
            }

            return 'hover:bg-gray-200 dark:hover:bg-gray-600';
        },
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
