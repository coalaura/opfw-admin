<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t("settings.title") }}
            </h1>
            <p>
                {{ t("settings.description") }}
            </p>
        </portal>

        <div class="mt-14">
            <h3 class="mb-5 dark:text-white">
                {{ t('settings.sessions') }}
            </h3>

            <div class="flex flex-wrap justify-between gap-4">
                <div v-for="session in list" :key="session.key" class="bg-gray-200 dark:bg-gray-700 border-gray-500 px-4 py-2 rounded-sm shadow-sm relative">
                    <div>
                        <b>{{ session.ip_address ? session.ip_address : 'Unknown IP' }}</b>
                    </div>

                    <div class="mt-1 pt-1 border-t border-gray-500">
                        <b>{{ session.browser }}</b> on <b>{{ session.os }}</b>
                    </div>

                    <div>
                        Last accessed {{ formatTimestamp(session.last_accessed) }}
                    </div>

                    <div class="text-sm mt-1 pt-1 border-t border-gray-500">
                        Viewed <a :href="session.last_viewed" target="_blank" class="text-blue-600 dark:text-blue-400">{{ session.last_viewed }}</a>
                    </div>

                    <div class="italic text-green-800 dark:text-green-200 text-sm" v-if="session.key === active">
                        This is your current session
                    </div>

                    <inertia-link
                        class="p-1 mt-2 text-sm font-bold leading-4 text-center w-full rounded-sm border-red-400 bg-secondary dark:bg-dark-secondary border-2 block"
                        :href="'/settings/' + session.key"
                        method="DELETE"
                    >
                        {{ t('settings.delete_session') }}
                    </inertia-link>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import Layout from './../Layouts/App';
import Card from './../Components/Card';

export default {
    layout: Layout,
    components: {
        Card
    },
    props: {
        active: {
            type: String,
            required: true
        },
        sessions: {
            type: Array,
            required: true
        }
    },
    data() {
        const sessions = this.sessions.map(session => {
            const ua = this.parseUserAgent(session.user_agent);

            session.last_viewed = '/' + session.last_viewed;

            return {
                ...session,
                browser: ua.browser,
                os: ua.os
            }
        });

        return {
            list: sessions
        }
    },
    methods: {
        formatTimestamp(timestamp) {
            return this.$options.filters.formatTime(timestamp * 1000);
        }
    }
}
</script>
