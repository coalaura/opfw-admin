<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white" id="queueTitle">
                {{ t('queue.title') }}
            </h1>
            <p>
                {{ t('queue.description') }}
            </p>
        </portal>

        <v-section class="overflow-x-auto" :noFooter="true">
            <template #header>
                <h2>
                    {{ server }}
                </h2>
                <p v-if="responseLabel" :class="'text-base font-bold ' + (responseIsError ? 'text-danger dark:text-dark-danger' : 'text-success dark:text-success-danger')" id="responseLabel">
                    {{ responseLabel }}
                </p>
            </template>

            <template>
                <table class="w-full whitespace-no-wrap">
                    <tr class="font-semibold text-left mobile:hidden">
                        <th class="px-6 py-4">{{ t('queue.queuePosition') }}</th>
                        <th class="px-6 py-4">{{ t('queue.licenseIdentifier') }}</th>
                        <th class="px-6 py-4">{{ t('queue.consoleName') }}</th>
                        <th class="px-6 py-4">{{ t('queue.priorityName') }}</th>
                        <th class="px-6 py-4">{{ t('queue.queueTime') }}</th>
                    </tr>
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-600 mobile:border-b-4" v-for="(player, index) in queue" :key="player.licenseIdentifier">
                        <td class="px-6 py-3 border-t mobile:block">{{ index+1 }}.</td>

                        <td class="px-6 py-3 border-t mobile:block">
                            <inertia-link class="block px-4 py-2 font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="'/players/' + player.licenseIdentifier">
                                {{ playerName(player.licenseIdentifier) }}
                            </inertia-link>
                        </td>

                        <td class="px-6 py-3 border-t mobile:block">{{ player.consoleName }}</td>
                        <td class="px-6 py-3 border-t mobile:block">{{ player.priorityName || t('queue.no_prio') }}</td>
                        <td class="px-6 py-3 border-t mobile:block">{{ formatSeconds(player.queueTime) }}</td>
                    </tr>
                    <tr v-if="queue.length === 0">
                        <td class="px-6 py-6 text-center border-t mobile:block" colspan="100%" v-if="isLoading">
                            {{ t('global.loading') }}
                        </td>
                        <td class="px-6 py-6 text-center border-t mobile:block" colspan="100%" v-else>
                            {{ t('queue.none') }}
                        </td>
                    </tr>
                </table>
            </template>
        </v-section>
    </div>
</template>

<script>
import Layout from './../../Layouts/App';
import VSection from './../../Components/Section';
import Badge from './../../Components/Badge';
import Pagination from './../../Components/Pagination';

export default {
    layout: Layout,
    components: {
        VSection,
        Badge,
        Pagination,
    },
    props: {
        server: {
            type: String,
            required: true,
        }
    },
    data() {
        return {
            isLoading: false,
            isSkipping: false,

            responseLabel: '',
            responseIsError: '',

            queue: [],
            playerMap: {},

            responseTimeout: false
        };
    },
    methods: {
        async refresh() {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;
            try {
                const data = await axios.get('/api/queue/' + this.server);

                if (data.data && data.data.status) {
                    this.queue = data.data.data.queue;
                    this.playerMap = data.data.data.playerMap;
                }
            } catch(e) {}

            this.isLoading = false;
        },
        formatSeconds(sec) {
            return this.$moment.duration(sec, 'seconds').format('d[d] h[h] m[m] s[s]');
        },
        sleep(ms) {
            return new Promise(function(resolve) {
                setTimeout(resolve, ms);
            });
        },
        playerName(licenseIdentifier) {
            return licenseIdentifier in this.playerMap ? this.playerMap[licenseIdentifier] : licenseIdentifier;
        }
    },
    mounted() {
        const _this = this;

        async function update() {
            await _this.refresh();

            setTimeout(update, 3000);
        }

        this.$nextTick(function () {
            update();
        });
    }
}
</script>
