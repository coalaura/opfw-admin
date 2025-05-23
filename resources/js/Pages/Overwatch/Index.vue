<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                <i class="mr-3 fas fa-unlock-alt" :title="perm.restriction(perm.PERM_SCREENSHOT)"></i>

                {{ t('overwatch.title') }}
            </h1>
            <p>
                {{ t('overwatch.description') }}
            </p>
        </portal>

        <v-section class="-mt-2 max-w-screen-lg" :noFooter="true" :noHeader="true">
            <div>
                <div class="flex justify-between">
                    <div class="flex">
                        <inertia-link class="px-5 py-2 font-semibold text-white mr-3 rounded bg-blue-600 dark:bg-blue-500" :href="'/players/' + screenshot.license" v-if="screenshot">
                            <i class="fas fa-user"></i>
                            {{ t("overwatch.profile", screenshot.id, screenshot.character.name, screenshot.character.id) }}
                        </inertia-link>

                        <badge class="border-blue-200 bg-blue-100 dark:bg-blue-700" v-if="screenshot">
                            <span class="font-semibold">{{ t('overwatch.server', screenshot.server) }}</span>
                        </badge>
                    </div>

                    <div class="flex gap-3">
                        <button class="px-5 py-2 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" :class="{ 'bg-green-600 rounded dark:bg-green-400': autoRefreshEnabled }" @click="autoRefresh" :title="t('overwatch.auto_refresh')">
                            <template v-if="!isLoading || !autoRefreshEnabled">
                                <i class="fa fa-magic mr-1"></i>
                                <span v-if="autoRefreshEnabled" class="font-mono">{{ Math.floor(autoRefreshTime) }}s</span>
                            </template>
                            <i class="fa fa-redo-alt animate-spin" v-else></i>
                        </button>

                        <button class="px-5 py-2 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" @click="refresh" :title="t('overwatch.refresh')" v-if="!autoRefreshEnabled">
                            <i class="fa fa-redo-alt" v-if="!isLoading"></i>
                            <i class="fa fa-redo-alt animate-spin" v-else></i>
                        </button>
                    </div>
                </div>

                <a :href="screenshot.url" class="mt-5 block" target="_blank" v-if="screenshot">
                    <img :src="screenshot.url" alt="Screenshot" class="block" />
                </a>
            </div>

            <p v-if="screenshotError" class="font-semibold text-danger dark:text-dark-danger m-0">{{ screenshotError }}</p>
        </v-section>

    </div>
</template>

<script>
import Layout from './../../Layouts/App.vue';
import VSection from './../../Components/Section.vue';
import Badge from './../../Components/Badge.vue';

export default {
    layout: Layout,
    components: {
        VSection,
        Badge,
    },
    data() {
        return {
            screenshot: null,
            screenshotError: null,
            isLoading: false,

            autoRefreshEnabled: false,
            autoRefreshTime: 0
        };
    },
    methods: {
        autoRefresh() {
            this.autoRefreshEnabled = !this.autoRefreshEnabled;
            this.autoRefreshTime = 0;

            if (this.autoRefreshEnabled) this.refresh();
        },
        wait(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },
        tick() {
            return new Promise(resolve => {
                this.$nextTick(() => resolve());
            });
        },
        async refresh() {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;

            try {
                const data = await _get('/api/random_screenshot');

                if (data?.status) {
                    this.screenshot = data.data;
                    this.screenshotError = null;
                } else {
                    this.screenshot = null;
                    this.screenshotError = data.message;
                }
            } catch (e) { }

            this.isLoading = false;

            for (this.autoRefreshTime = 10; this.autoRefreshTime > 0; this.autoRefreshTime -= 0.1) {
                if (!this.autoRefreshEnabled) return;

                await this.wait(100);
            }

            if (!this.autoRefreshEnabled) return;

            this.refresh();
        }
    },
    mounted() {
        this.refresh();

        $(document).on("visibilitychange", e => {
            if (document.visibilityState !== "visible") {
                this.autoRefreshEnabled = false;
            }
        });
    }
};
</script>
