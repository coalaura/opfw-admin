<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                <i class="mr-3 fas fa-unlock-alt" :title="perm.restriction(perm.PERM_PHONE_LOGS)"></i>

                {{ t('phone_call.title') }}
            </h1>
            <p>
                {{ t('phone_call.description') }}
            </p>
        </portal>

        <div class="flex gap-5 max-h-lg max-w-6xl">
            <!-- Querying -->
            <v-section :noFooter="true" :noHeader="true" class="w-1/3">
                <template>
                    <form @submit.prevent autocomplete="off">
                        <input autocomplete="false" name="hidden" type="text" class="hidden" />

                        <div class="flex flex-wrap">
                            <!-- Participant -->
                            <div class="w-full px-3 mb-4">
                                <label class="block mb-2" for="number">
                                    {{ t('phone_call.number') }}
                                </label>
                                <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="number" placeholder="123-4567" v-model="filters.number">
                            </div>

                            <!-- After -->
                            <div class="w-full px-3 mb-4">
                                <label class="block mb-2" for="after">
                                    {{ t('phone_call.after') }}
                                </label>
                                <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="after" type="datetime-local" step="1" v-model="after">
                            </div>

                            <!-- Before -->
                            <div class="w-full px-3 mb-4">
                                <label class="block mb-2" for="before">
                                    {{ t('phone_call.before') }}
                                </label>
                                <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="before" type="datetime-local" step="1" v-model="before">
                            </div>
                        </div>

                        <!-- Search button -->
                        <div class="w-full px-3 mt-6">
                            <button class="px-5 py-2 font-semibold text-white bg-success dark:bg-dark-success rounded hover:shadow-lg w-full" @click="refresh">
                                <span v-if="!isLoading">
                                    <i class="fas fa-search"></i>
                                    {{ t('logs.search') }}
                                </span>
                                <span v-else>
                                    <i class="fas fa-spinner animate-spin"></i>
                                    {{ t('global.loading') }}
                                </span>
                            </button>
                        </div>
                    </form>
                </template>
            </v-section>

            <!-- Table -->
            <div class="p-8 mb-10 rounded-lg shadow relative w-2/3 overflow-hidden bg-secondary dark:bg-dark-secondary">
                <div class="overflow-y-auto px-5 -mx-5 max-h-full">
                    <div class="flex flex-col gap-6">
                        <div v-for="call in calls" :key="call.id" class="flex flex-wrap" v-if="calls.length > 0">
                            <div>
                                <div class="w-full flex items-center mb-1 gap-2">
                                    <div class="px-3 py-1 border-2 rounded bg-opacity-50 min-w-call text-center" :class="badgeColor(call.caller_number)">{{ call.caller_number }}</div>

                                    <div class="font-semibold px-2">
                                        <i class="fas fa-phone-alt"></i>
                                    </div>

                                    <div class="px-2 flex flex-col justify-center items-center min-w-call">
                                        <div class="font-semibold" :class="{ 'text-green-500': call.accepted, 'line-through text-red-500': !call.accepted }">{{ call.reason }}</div>
                                        <div v-if="call.accepted" class="text-xxs leading-1 italic">{{ t('phone_call.after_time', formatSeconds(call.duration, "hms")) }}</div>
                                    </div>

                                    <div class="font-semibold px-2">
                                        <i class="fas fa-phone" v-if="call.accepted"></i>
                                        <i class="fas fa-phone-slash" v-else></i>
                                    </div>

                                    <div class="px-3 py-1 border-2 rounded bg-opacity-50 min-w-call text-center" :class="badgeColor(call.receiver_number)">{{ call.receiver_number }}</div>
                                </div>

                                <div class="w-full text-xs leading-1 flex justify-between text-gray-500 dark:text-gray-400 items-center gap-3">
                                    <div class="italic flex-shrink-0" @click="copyToClipboard(call.timestamp - call.duration)">{{ (call.timestamp - call.duration) * 1000 | formatTime(true) }}</div>

                                    <div class="w-full h-px bg-gray-500 dark:bg-gray-400"></div>

                                    <div class="italic flex-shrink-0" @click="copyToClipboard(call.timestamp)">{{ call.timestamp * 1000 | formatTime(true) }}</div>
                                </div>
                            </div>
                        </div>

                        <template v-if="!isLoading">
                            <div class="flex justify-center" v-if="hasMore">
                                <button class="px-3 py-1 border-2 rounded bg-opacity-50 bg-gray-300 dark:bg-gray-800 border-gray-500 font-semibold" @click="more">
                                    <i class="fas fa-plus mr-1"></i>
                                    {{ t('phone_call.more') }}
                                </button>
                            </div>

                            <div class="flex justify-center" v-else-if="calls.length === 0">
                                <div class="px-3 py-1 border-2 rounded bg-opacity-50 bg-gray-300 dark:bg-gray-800 border-gray-500 font-semibold">{{ t('phone_call.no_calls') }}</div>
                            </div>
                        </template>

                        <div v-else class="text-center px-3 py-1"><i class="fas fa-spinner fa-spin text-xl"></i></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import Layout from './../../Layouts/App.vue';
import VSection from './../../Components/Section.vue';
import Pagination from './../../Components/Pagination.vue';

// bg-red-300 dark:bg-red-900 border-red-500
// bg-orange-300 dark:bg-orange-900 border-rose-500
// bg-yellow-300 dark:bg-yellow-900 border-yellow-500
// bg-lime-300 dark:bg-lime-900 border-lime-500
// bg-emerald-300 dark:bg-emerald-900 border-emerald-500
// bg-sky-300 dark:bg-sky-900 border-sky-500
// bg-indigo-300 dark:bg-indigo-900 border-indigo-500
// bg-purple-300 dark:bg-purple-900 border-purple-500
// bg-pink-300 dark:bg-pink-900 border-pink-500
const colors = ['red', 'orange', 'lime', 'emerald', 'sky', 'indigo', 'purple', 'pink'],
    usedColors = {};

let index = 0;

function numberColor(number) {
    number = number.trim().toLowerCase();

    if (usedColors[number]) return usedColors[number];

    const color = colors[index];

    usedColors[number] =  `bg-${color}-300 dark:bg-${color}-900 border-${color}-500`;

    index++;

    if (index >= colors.length) index = 0;

    return usedColors[number];
}

export default {
    layout: Layout,
    components: {
        Pagination,
        VSection
    },
    props: {
        filters: {
            number: String,
            after: Number,
            before: Number,
        }
    },
    data() {
        return {
            isLoading: false,

            after: "",
            before: "",

            calls: [],
            hasMore: false
        };
    },
    methods: {
        badgeColor(number) {
            return numberColor(number);
        },
        addCalls(calls) {
            calls = calls.map(call => {
                find(call.caller_number);
                find(call.receiver_number);

                return call;
            });

            this.calls.push(...calls);
        },
        async fetch() {
            if (this.isLoading) return;

            this.isLoading = true;

            this.replaceState();

            try {
                const id = this.calls.length > 0 ? this.calls[this.calls.length - 1].id : 0;

                const data = await _get("/phone_call/get", {
                    id: id,
                    ...this.filters
                });

                if (data?.status) {
                    this.isLoading = false;

                    return data.data;
                }
            } catch (e) {
            }

            this.isLoading = false;

            return false;
        },
        async refresh() {
            this.calls = [];
            this.hasMore = false;

            const calls = await this.fetch();

            if (!calls) return;

            this.hasMore = calls.length === 30;

            this.addCalls(calls);
        },
        async more() {
            const calls = await this.fetch();

            if (!calls) return;

            this.hasMore = calls.length === 30;

            this.addCalls(calls);
        },
        replaceState() {
            if (this.after) {
                const d = new Date(this.after);

                if (!Number.isNaN(d)) {
                    this.filters.after = Math.round(d.getTime() / 1000);
                }
            } else if (this.filters.after) {
                const d = new Date(this.filters.after * 1000);

                if (!Number.isNaN(d)) {
                    this.after = new Date(d.getTime() + new Date().getTimezoneOffset() * -60 * 1000).toISOString().slice(0, 19);
                }
            }

            if (this.before) {
                const d = new Date(this.before);

                if (!Number.isNaN(d)) {
                    this.filters.before = Math.round(d.getTime() / 1000);
                }
            } else if (this.filters.before) {
                const d = new Date(this.filters.before * 1000);

                if (!Number.isNaN(d)) {
                    this.before = new Date(d.getTime() + new Date().getTimezoneOffset() * -60 * 1000).toISOString().slice(0, 19);
                }
            }

            const url = new URL(window.location.href);

            for (const key in this.filters) {
                if (this.filters[key]) {
                    url.searchParams.set(key, this.filters[key]);
                } else {
                    url.searchParams.delete(key);
                }
            }

            window.history.replaceState({}, '', url);
        }
    },
    mounted() {
        this.refresh();
    }
};
</script>
