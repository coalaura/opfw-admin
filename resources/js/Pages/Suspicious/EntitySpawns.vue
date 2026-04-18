<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                <i class="mr-3 fas fa-ghost" :title="perm.restriction(perm.PERM_SUSPICIOUS_ENTITIES)"></i>

                {{ t('suspicious.entity_spawns') }}
            </h1>
            <p>
                {{ t('suspicious.entity_spawns_description') }}
            </p>
        </portal>

        <!-- Querying -->
        <v-section :noFooter="true">
            <template #header>
                <h2>
                    {{ t('suspicious.filter') }}
                </h2>
            </template>

            <template>
                <form @submit.prevent autocomplete="off">
                    <input autocomplete="false" name="hidden" type="text" class="hidden" />

                    <div class="flex flex-wrap mb-4">
                        <!-- License -->
                        <div class="w-1/2 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2" for="license">
                                {{ t('suspicious.license_identifier') }}
                            </label>
                            <input class="w-full px-4 py-3 bg-gray-200 dark:bg-gray-600 border rounded" id="license" v-model="filters.license" placeholder="license:2ced2cabd90f1208e7e056485d4704c7e1284196" />
                        </div>

                        <!-- Entity Types -->
                        <div class="w-1/2 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2">
                                {{ t('suspicious.entity_types') }}
                            </label>
                            <div class="relative" v-click-outside="() => entityDropdownOpen = false">
                                <div class="w-full px-4 py-3 bg-gray-200 dark:bg-gray-600 border border-input rounded cursor-pointer flex justify-between items-center" @click="entityDropdownOpen = !entityDropdownOpen">
                                    <span class="truncate">{{ selectedTypesText }}</span>
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </div>
                                <div v-if="entityDropdownOpen" class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-input rounded shadow-lg py-1">
                                    <label class="flex items-center gap-3 px-4 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                        <input type="checkbox" :value="1" v-model="filters.types" class="w-4 h-4">
                                        <span class="font-semibold">{{ t('suspicious.types.vehicle') }}</span>
                                    </label>
                                    <label class="flex items-center gap-3 px-4 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                        <input type="checkbox" :value="2" v-model="filters.types" class="w-4 h-4">
                                        <span class="font-semibold">{{ t('suspicious.types.ped') }}</span>
                                    </label>
                                    <label class="flex items-center gap-3 px-4 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                        <input type="checkbox" :value="3" v-model="filters.types" class="w-4 h-4">
                                        <span class="font-semibold">{{ t('suspicious.types.object') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- After Date -->
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3 mt-3">
                            <label class="block mb-2" for="after-date">
                                {{ t('logs.after-date') }}
                            </label>
                            <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="after-date" type="date" placeholder="">
                        </div>

                        <!-- After Time -->
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3 mt-3">
                            <label class="block mb-2" for="after-time">
                                {{ t('logs.after-time') }}
                            </label>
                            <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="after-time" type="time" placeholder="">
                        </div>

                        <!-- Before Date -->
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3 mt-3">
                            <label class="block mb-2" for="before-date">
                                {{ t('logs.before-date') }}
                            </label>
                            <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="before-date" type="date" placeholder="">
                        </div>

                        <!-- Before Time -->
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3 mt-3">
                            <label class="block mb-2" for="before-time">
                                {{ t('logs.before-time') }}
                            </label>
                            <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="before-time" type="time" placeholder="">
                        </div>

                        <!-- Search button -->
                        <div class="w-full px-3 mt-4">
                            <button class="px-5 block py-2 font-semibold text-white bg-success dark:bg-dark-success rounded hover:shadow-lg" @click="refresh">
                                <span v-if="!isLoading">
                                    <i class="fas fa-search"></i>
                                    {{ t('suspicious.search') }}
                                </span>
                                <span v-else>
                                    <i class="fas fa-cog animate-spin"></i>
                                    {{ t('global.loading') }}
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </template>
        </v-section>

        <!-- Table -->
        <v-section class="overflow-x-auto">
            <template #header>
                <h2>
                    {{ t('logs.logs') }}
                </h2>
                <p class="text-muted dark:text-dark-muted text-xs">
                    {{ t('global.entries', total, Math.ceil(total / 15)) }}
                </p>
            </template>

            <template>
                <HashResolver>
                    <table class="w-full">
                        <tr class="font-semibold text-left">
                            <th class="p-3 pl-8 max-w-56">{{ t('suspicious.player') }}</th>
                            <th class="p-3">{{ t('suspicious.model') }}</th>
                            <th class="p-3">{{ t('suspicious.type') }}</th>
                            <th class="p-3">{{ t('suspicious.distance') }}</th>
                            <th class="p-3">{{ t('suspicious.population') }}</th>
                            <th class="p-3">{{ t('suspicious.coordinates') }}</th>
                            <th class="p-3">{{ t('suspicious.flags') }}</th>
                            <th class="p-3">{{ t('suspicious.script') }}</th>
                            <th class="p-3 pr-8">{{ t('suspicious.time') }}</th>
                        </tr>

                        <!-- Items -->
                        <tr class="border-t border-gray-300 dark:border-gray-500 hover:bg-gray-200 dark:hover:bg-gray-600" v-for="log in logs" :key="log.id">
                            <td class="p-3 pl-8 mobile:block max-w-56">
                                <inertia-link class="block px-4 py-2 font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400 truncate" :href="'/players/' + log.license_identifier">
                                    {{ playerName(log.license_identifier) }}
                                </inertia-link>
                            </td>
                            <td class="p-3 mobile:block whitespace-nowrap">
                                <div class="font-mono cursor-help" :title="log.model">{{ log.model }}</div>
                                <div class="text-xs text-gray-500 mt-1" v-if="log.ped_weapon">
                                    W: <span class="font-mono cursor-help">{{ log.ped_weapon }}</span>
                                </div>
                            </td>
                            <td class="p-3 mobile:block whitespace-nowrap text-sm">
                                {{ typeName(log.type) }}
                            </td>
                            <td class="p-3 mobile:block whitespace-nowrap text-sm">
                                {{ log.distance !== null ? log.distance.toFixed(2) + 'm' : '?' }}
                            </td>
                            <td class="p-3 mobile:block whitespace-nowrap text-sm">
                                {{ populationName(log.population) }}
                            </td>
                            <td class="p-3 mobile:block whitespace-nowrap text-sm">
                                <div class="font-mono">vec4({{ log.coords_x !== null ? log.coords_x.toFixed(2) : '?' }}, {{ log.coords_y !== null ? log.coords_y.toFixed(2) : '?' }}, {{ log.coords_z !== null ? log.coords_z.toFixed(2) : '?' }}, {{ log.heading !== null ? log.heading.toFixed(2) : '?' }})</div>
                            </td>
                            <td class="p-3 mobile:block text-lg">
                                <div class="flex gap-2">
                                    <i v-if="log.is_invisible" class="fas fa-eye-slash" title="Invisible"></i>
                                    <i v-if="log.is_frozen" class="fas fa-snowflake" title="Frozen"></i>
                                    <i v-if="log.no_collisions" class="fas fa-ban" title="No Collisions"></i>
                                    <i v-if="log.is_attached" class="fas fa-link" title="Attached"></i>
                                    <span v-if="!log.is_invisible && !log.is_frozen && !log.no_collisions && !log.is_attached" class="text-sm text-gray-500 italic">None</span>
                                </div>
                            </td>
                            <td class="p-3 mobile:block text-sm break-words max-w-xs">
                                <span v-if="log.script" class="font-mono bg-black bg-opacity-10 dark:bg-white dark:bg-opacity-10 px-1.5 py-0.5 rounded">{{ log.script }}</span>
                                <span v-else class="text-gray-500 italic">None</span>
                            </td>
                            <td class="p-3 pr-8 mobile:block whitespace-nowrap text-sm">
                                {{ formatDateWithMs(log.timestamp) }}
                            </td>
                        </tr>

                        <!-- Nothing -->
                        <tr v-if="logs.length === 0" class="border-t border-gray-300 dark:border-gray-500">
                            <td class="px-8 py-3 text-center" colspan="100%">
                                {{ t('suspicious.no_logs') }}
                            </td>
                        </tr>
                    </table>
                </HashResolver>
            </template>

            <template #footer>
                <div class="flex items-center justify-between mt-6 mb-1">

                    <!-- Navigation -->
                    <div class="flex flex-wrap">
                        <inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="links.prev" v-if="page >= 2">
                            <i class="mr-1 fas fa-arrow-left"></i>
                            {{ t("pagination.previous") }}
                        </inertia-link>
                        <inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" v-if="logs.length === 15" :href="links.next">
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

    </div>
</template>

<script>
import Layout from './../../Layouts/App.vue';
import VSection from './../../Components/Section.vue';
import Pagination from './../../Components/Pagination.vue';
import HashResolver from './../../Components/HashResolver.vue';
import MultiSelector from './../../Components/MultiSelector.vue';

export default {
    layout: Layout,
    components: {
        Pagination,
        VSection,
        HashResolver,
        MultiSelector,
    },
    props: {
        logs: {
            type: Array,
            required: true,
        },
        playerMap: {
            type: Object,
            required: true,
        },
        filters: {
            license: String,
            after: [String, Number],
            before: [String, Number],
            types: Array,
        },
        links: {
            type: Object,
            required: true,
        },
        page: {
            type: Number,
            required: true,
        },
        total: {
            type: Number,
            required: true,
        }
    },
    data() {
        return {
            isLoading: false,
            entityDropdownOpen: false
        };
    },
    computed: {
        selectedTypesText() {
            if (!this.filters.types || this.filters.types.length === 0 || this.filters.types.length === 3) {
                return this.t('global.all');
            }

            const map = {
                1: this.t('suspicious.types.vehicle'),
                2: this.t('suspicious.types.ped'),
                3: this.t('suspicious.types.object')
            };

            return this.filters.types.map(t => map[t]).join(', ');
        }
    },
    methods: {
        refresh: async function () {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;

            const beforeDate = $('#before-date').val();
            const beforeTime = $('#before-time').val() || '00:00';
            const afterDate = $('#after-date').val();
            const afterTime = $('#after-time').val() || '23:59';

            if (beforeDate && beforeTime) {
                this.filters.before = (new Date(`${beforeDate} ${beforeTime}`)).getTime();

                if (Number.isNaN(this.filters.before)) {
                    this.filters.before = null;
                }
            }

            if (afterDate && afterTime) {
                this.filters.after = (new Date(`${afterDate} ${afterTime}`)).getTime();

                if (Number.isNaN(this.filters.after)) {
                    this.filters.after = null;
                }
            }

            try {
                await this.$inertia.replace('/suspicious/entity_spawns', {
                    data: this.filters,
                    preserveState: true,
                    preserveScroll: true,
                    only: ['logs', 'links', 'page', 'total', 'playerMap'],
                });
            } catch (e) {
            }

            this.isLoading = false;
        },
        playerName(licenseIdentifier) {
            return licenseIdentifier in this.playerMap ? this.playerMap[licenseIdentifier] : licenseIdentifier;
        },
        typeName(type) {
            switch(type) {
                case 1: return this.t('suspicious.types.vehicle') + ' (1)';
                case 2: return this.t('suspicious.types.ped') + ' (2)';
                case 3: return this.t('suspicious.types.object') + ' (3)';
                default: return 'Unknown (' + type + ')';
            }
        },
        populationName(pop) {
            if (pop === null || pop === undefined) return '?';
            
            const types = {
                0: 'Unknown',
                1: 'Random Permanent',
                2: 'Random Parked',
                3: 'Random Patrol',
                4: 'Random Scenario',
                5: 'Random Ambient',
                6: 'Permanent',
                7: 'Mission',
                8: 'Replay',
                9: 'Cache',
                10: 'Tool'
            };
            return types[pop] || `Unknown (${pop})`;
        },
        formatDateWithMs(timestamp) {
            return dayjs(timestamp).format('M/D/YYYY, h:mm:ss.SSS A');
        }
    },
    mounted() {
        if (this.filters.before) {
            const d = dayjs.utc(this.filters.before);

            $('#before-date').val(d.format('YYYY-MM-DD'));
            $('#before-time').val(d.format('HH:mm'));
        }

        if (this.filters.after) {
            const d = dayjs.utc(this.filters.after);

            $('#after-date').val(d.format('YYYY-MM-DD'));
            $('#after-time').val(d.format('HH:mm'));
        }
    }
};
</script>