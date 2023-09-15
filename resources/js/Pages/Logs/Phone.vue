<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t('phone.title') }}
            </h1>
            <p>
                {{ t('phone.description') }}
            </p>
        </portal>

        <div class="flex gap-5 max-h-lg">
            <!-- Querying -->
            <v-section :noFooter="true" :noHeader="true" class="w-1/3">
                <template>
                    <form @submit.prevent autocomplete="off">
                        <input autocomplete="false" name="hidden" type="text" class="hidden" />

                        <div class="flex flex-wrap">
                            <!-- Participant 1 -->
                            <div class="w-full px-3 mb-4">
                                <label class="block mb-2" for="number1">
                                    {{ t('phone.number1') }} <sup class="text-muted dark:text-dark-muted">*, C</sup>
                                </label>
                                <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="number1" placeholder="123-4567" v-model="filters.number1">
                            </div>

                            <!-- Participant 2 -->
                            <div class="w-full px-3 mb-4">
                                <label class="block mb-2" for="number2">
                                    {{ t('phone.number2') }} <sup class="text-muted dark:text-dark-muted">*, C</sup>
                                </label>
                                <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="number2" placeholder="987-6543" v-model="filters.number2">
                            </div>

                            <!-- Message -->
                            <div class="w-full px-3 mb-4">
                                <label class="block mb-2" for="message">
                                    {{ t('phone.message') }} <sup class="text-muted dark:text-dark-muted">**</sup>
                                </label>
                                <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="message" placeholder="Some text message" v-model="filters.message">
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
                                    <i class="fas fa-cog animate-spin"></i>
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
                        <div v-for="message in messages" :key="message.id" class="flex flex-wrap" :class="{ 'justify-end': !message.left, 'justify-start': message.left }" v-if="messages.length > 0">
                            <div class="w-full text-xs leading-1 flex justify-end" v-if="participants == 2">
                                <div class="italic" :class="numbers[message.sender_number].text">{{ message.sender_number }}</div>
                            </div>

                            <div class="w-full text-xs leading-1 flex justify-end" v-else>
                                <div class="italic" :class="numbers[message.sender_number].text">{{ message.sender_number }}</div>

                                <div class="font-semibold px-2">🠚</div>

                                <div class="italic" :class="numbers[message.receiver_number].text">{{ message.receiver_number }}</div>
                            </div>

                            <div class="my-1 px-3 py-1 border-2 rounded bg-opacity-50" :class="numbers[message.sender_number].badge">{{ message.message }}</div>

                            <div class="w-full text-right text-xs leading-1">
                                <div class="italic text-gray-500 dark:text-gray-400">{{ message.timestamp * 1000 | formatTime(true) }}</div>
                            </div>
                        </div>

                        <div class="flex justify-end" v-if="isLoading" ref="loading">
                            <div class="px-3 py-1 border-2 rounded bg-opacity-50 bg-gray-300 dark:bg-gray-800 border-gray-500">{{ t('global.loading') }}</div>
                        </div>

                        <div class="flex justify-end" v-else-if="hasMore">
                            <button class="px-3 py-1 border-2 rounded bg-opacity-50 bg-gray-300 dark:bg-gray-800 border-gray-500" @click="more">{{ t('phone.more') }}</button>
                        </div>

                        <div class="flex justify-end" v-else-if="messages.length === 0">
                            <div class="px-3 py-1 border-2 rounded bg-opacity-50 bg-gray-300 dark:bg-gray-800 border-gray-500">{{ t('phone.no_messages') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import Layout from './../../Layouts/App';
import VSection from './../../Components/Section';
import Pagination from './../../Components/Pagination';

// bg-red-300 dark:bg-red-900 border-red-500
// bg-yellow-300 dark:bg-yellow-900 border-yellow-500
// bg-green-300 dark:bg-green-900 border-green-500
// bg-blue-300 dark:bg-blue-900 border-blue-500
// bg-purple-300 dark:bg-purple-900 border-purple-500
// bg-pink-300 dark:bg-pink-900 border-pink-500
// bg-lime-300 dark:bg-lime-900 border-lime-500
const colors = ['red', 'yellow', 'green', 'blue', 'purple', 'pink', 'lime'];

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
            page: 1,
            hasMore: false,
            participants: 0,

            numbers: {},
            index: 0
        };
    },
    methods: {
        color(messages) {
            const find = number => {
                if (this.numbers[number]) return;

                const color = colors[this.index];

                this.numbers[number] = {
                    badge: `bg-${color}-300 dark:bg-${color}-900 border-${color}-500`,
                    text: `text-${color}-500`
                };

                this.index = (this.index + 1) % colors.length;
            };

            messages = messages.map(message => {
                find(message.sender_number);
                find(message.receiver_number);

                message.left = this.filters.number1?.includes(message.sender_number);

                return message;
            });

            this.messages.push(...messages);

            this.participants = this.messages.filter((message, index, self) => {
                return self.findIndex(m => m.sender_number === message.sender_number) === index;
            }).length;
        },
        async fetch() {
            if (this.isLoading) return;

            this.isLoading = true;

            try {
                const data = await axios.get('/phoneLogs/get', {
                    params: {
                        page: this.page,
                        ...this.filters
                    }
                });

                if (data.data && data.data.status) {
                    this.isLoading = false;

                    return data.data.data;
                }
            } catch (e) {
            }

            this.isLoading = false;

            return false;
        },
        async refresh() {
            this.page = 1;

            const messages = await this.fetch();

            if (!messages) return;

            this.hasMore = messages.length === 30;

            this.messages = [];
            this.numbers = {};
            this.index = 0;

            this.color(messages);
        },
        async more() {
            this.isLoading = true;
            this.scrollLoading();

            this.page++;

            const messages = await this.fetch();

            if (!messages) return;

            this.hasMore = messages.length === 30;

            this.color(messages);
        },
        scrollLoading() {
            this.$nextTick(() => {
                if (!this.$refs.loading) return;

                this.$refs.loading.scrollIntoView({
                    behavior: "smooth"
                });
            });
        }
    },
    mounted() {
        this.refresh();
    }
};
</script>
