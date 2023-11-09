<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white" id="queueTitle">
                <i class="mr-3 fas fa-unlock-alt" :title="perm.restriction(perm.PERM_VIEW_QUEUE)"></i>

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
                        <th class="p-3 pl-8">{{ t('queue.queuePosition') }}</th>
                        <th class="p-3">{{ t('queue.licenseIdentifier') }}</th>
                        <th class="p-3">{{ t('queue.consoleName') }}</th>
                        <th class="p-3">{{ t('queue.priorityName') }}</th>
                        <th class="p-3 pr-8">{{ t('queue.queueTime') }}</th>
                    </tr>
                    <tr class="border-t border-gray-300 dark:border-gray-500 hover:bg-gray-200 dark:hover:bg-gray-600" v-for="(player, index) in queue" :key="player.licenseIdentifier">
                        <td class="p-3 pl-8 mobile:block">{{ index + 1 }}.</td>

                        <td class="p-3 mobile:block">
                            <inertia-link class="block px-4 py-2 font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="'/players/' + player.licenseIdentifier">
                                {{ playerName(player.licenseIdentifier) }}
                            </inertia-link>
                        </td>

                        <td class="p-3 mobile:block">{{ player.consoleName }}</td>
                        <td class="p-3 mobile:block">{{ player.priorityName || t('queue.no_prio') }}</td>
                        <td class="p-3 pr-8 mobile:block">{{ formatSeconds(Math.floor(player.queueTime / 1000)) }}</td>
                    </tr>
                    <tr v-if="queue.length === 0" class="border-t border-gray-300 dark:border-gray-500">
                        <td class="px-8 py-3 text-center mobile:block" colspan="100%" v-if="isLoading">
                            {{ t('global.loading') }}
                        </td>
                        <td class="px-8 py-3 text-center mobile:block" colspan="100%" v-else>
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
            } catch (e) { }

            this.isLoading = false;
        },
        formatSeconds(sec) {
            return this.$moment.duration(sec, 'seconds').format('d[d] h[h] m[m] s[s]');
        },
        sleep(ms) {
            return new Promise(function (resolve) {
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

        // Delay loading of extra data since it blocks other resources from loading
        setTimeout(() => {
            update();
        }, 500);
    }
}
</script>
