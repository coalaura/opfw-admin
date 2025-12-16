<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                <i class="mr-3 fas fa-unlock-alt" :title="perm.restriction(perm.PERM_PHONE_LOGS)"></i>

                {{ t('phone.title') }}
            </h1>
            <p>
                {{ t('phone.description') }}
            </p>
        </portal>

        <div class="flex gap-5 max-h-lg max-w-6xl">
            <!-- Querying -->
            <v-section :noFooter="true" :noHeader="true" class="w-1/3">
                <template>
                    <form @submit.prevent autocomplete="off">
                        <input autocomplete="false" name="hidden" type="text" class="hidden" />

                        <div class="flex flex-wrap">
                            <!-- Participant 1 -->
                            <div class="w-full px-3 mb-4">
                                <label class="block mb-2" for="number1">
                                    {{ t('phone.number1') }}
                                </label>
                                <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="number1" placeholder="123-4567" v-model="filters.number1">
                            </div>

                            <!-- Participant 2 -->
                            <div class="w-full px-3 mb-4">
                                <label class="block mb-2" for="number2">
                                    {{ t('phone.number2') }}
                                </label>
                                <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="number2" placeholder="987-6543" v-model="filters.number2">
                            </div>

                            <!-- Message -->
                            <div class="w-full px-3 mb-4">
                                <label class="block mb-2" for="message">
                                    {{ t('phone.message') }} <sup class="text-muted dark:text-dark-muted">*</sup>
                                </label>
                                <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="message" placeholder="Some text message" v-model="filters.message" :title="previewQuery(filters.message)">
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="w-full px-3 mt-3">
                            <small class="text-muted dark:text-dark-muted mt-1 leading-4 block" v-html="t('global.search.custom')"></small>
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
                        <div v-for="message in messages" :key="message.id" class="flex flex-wrap" :class="message.justify" v-if="messages.length > 0">
                            <div class="w-full text-xs leading-1 flex" :class="message.justify">
                                <div class="italic" :class="textColor(message.sender_number)">{{ message.sender_number }}</div>

                                <div class="font-semibold px-2 cursor-pointer" @click="showConversation(message)">🠚</div>

                                <div class="italic" :class="textColor(message.receiver_number)">{{ message.receiver_number }}</div>
                            </div>

                            <div class="my-1 px-3 py-1 border-2 rounded bg-opacity-50" :class="badgeColor(message.sender_number)">
                                <a v-if="message.image" class="py-2 block" :href="message.image" target="_blank">
                                    <img :src="message.image" class="h-full max-w-full max-h-80 object-contain" v-handle-error />
                                </a>

                                <div v-else>
                                    {{ message.message }}
                                </div>
                            </div>

                            <div class="w-full text-xs leading-1 flex" :class="message.justify">
                                <div class="italic text-gray-500 dark:text-gray-400" @click="copyToClipboard(message.timestamp)">{{ message.timestamp * 1000 | formatTime(true) }}</div>
                            </div>
                        </div>

                        <template v-if="!isLoading">
                            <div class="flex justify-center" v-if="hasMore">
                                <button class="px-3 py-1 border-2 rounded bg-opacity-50 bg-gray-300 dark:bg-gray-800 border-gray-500 font-semibold" @click="more">
                                    <i class="fas fa-plus mr-1"></i>
                                    {{ t('phone.more') }}
                                </button>
                            </div>

                            <div class="flex justify-center" v-else-if="messages.length === 0">
                                <div class="px-3 py-1 border-2 rounded bg-opacity-50 bg-gray-300 dark:bg-gray-800 border-gray-500 font-semibold">{{ t('phone.no_messages') }}</div>
                            </div>
                        </template>
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

// bg-red-300 dark:bg-red-900 border-red-500 text-red-500
// bg-yellow-300 dark:bg-yellow-900 border-yellow-500 text-yellow-500
// bg-green-300 dark:bg-green-900 border-green-500 text-green-500
// bg-blue-300 dark:bg-blue-900 border-blue-500 text-blue-500
// bg-purple-300 dark:bg-purple-900 border-purple-500 text-purple-500
// bg-pink-300 dark:bg-pink-900 border-pink-500 text-pink-500
// bg-lime-300 dark:bg-lime-900 border-lime-500 text-lime-500
const colors = ['red', 'yellow', 'green', 'blue', 'purple', 'pink', 'lime'];
const usedColors = {};

let index = 0;

function numberColor(number) {
    number = number.trim().toLowerCase();

    if (usedColors[number]) return usedColors[number];

    const color = colors[index];

    usedColors[number] = {
        badge: `bg-${color}-300 dark:bg-${color}-900 border-${color}-500`,
        text: `text-${color}-500`
    };

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
            number1: String,
            number2: String,
            message: String,
        }
    },
    data() {
        return {
            isLoading: false,

            messages: [],
            hasMore: false
        };
    },
    methods: {
        textColor(number) {
            return numberColor(number).text;
        },
        badgeColor(number) {
            return numberColor(number).badge;
        },
        addMessages(messages) {
            messages = messages.map(message => {
                find(message.sender_number);
                find(message.receiver_number);

                message.justify = this.filters.number2?.includes(message.sender_number) ? 'justify-start' : 'justify-end';

                if (message.message.match(/^https?:\/\/[^\s]+?\.(png|jpe?g|gif|webp)(\?[\w&=%]*)?$/im)) {
                    message.image = message.message;
                }

                return message;
            });

            this.messages.push(...messages);
        },
        async fetch() {
            if (this.isLoading) return;

            this.isLoading = true;

            try {
                const before = this.messages.length > 0 ? this.messages[this.messages.length - 1].id : 0;

                const data = await _get("/phone_logs/get", {
                    before: before,
                    ...this.filters
                });

                if (data?.status) {
                    this.isLoading = false;

                    this.replaceState();

                    return data.data;
                }
            } catch (e) {
            }

            this.isLoading = false;

            return false;
        },
        async refresh() {
            this.messages = [];
            this.hasMore = false;

            const messages = await this.fetch();

            if (!messages) return;

            this.hasMore = messages.length === 30;

            this.addMessages(messages);
        },
        async more() {
            const messages = await this.fetch();

            if (!messages) return;

            this.hasMore = messages.length === 30;

            this.addMessages(messages);
        },
        replaceState() {
            const url = new URL(window.location.href);

            for (const key in this.filters) {
                if (this.filters[key]) {
                    url.searchParams.set(key, this.filters[key]);
                } else {
                    url.searchParams.delete(key);
                }
            }

            window.history.replaceState({}, '', url);
        },
        showConversation(message) {
            this.filters.number1 = message.sender_number;
            this.filters.number2 = message.receiver_number;

            this.refresh();
        }
    },
    mounted() {
        this.refresh();
    }
};
</script>
