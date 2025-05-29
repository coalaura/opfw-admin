<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t("paintings.title") }}
            </h1>
            <p>
                {{ t("paintings.description") }}
                <a @click="refresh($event)" href="#" class="text-indigo-600 !no-underline dark:text-indigo-300 hover:text-yellow-500 dark:hover:text-yellow-300">
                    <i class="ml-1 mr-1 fa fa-redo-alt"></i> {{ t('global.refresh') }}
                </a>
            </p>
        </portal>

        <div class="flex -mt-6 justify-between max-w-screen-2xl mobile:flex-wrap">
            <div class="p-4 max-w-xl pl-6 italic border-l-4 border-gray-300 inline-block bg-gray-100 shadow-lg dark:border-gray-500 dark:bg-gray-700 dark:text-gray-100 mobile:w-full mobile:mb-3">
                <div v-if="isLoading">
                    <i class="mr-1 fa fa-redo-alt animate-spin font-normal text-xl"></i>
                </div>
                <div v-else-if="painting">
                    <div class="text-lg truncate font-semibold italic mb-2 text-center">{{ painting.name }}</div>

                    <img :src="painting.source" />

                    <div class="flex justify-between mt-2">
                        <inertia-link :href="'/inventory/' + painting.inventory" class="text-indigo-600 text-xs !no-underline dark:text-indigo-300 hover:!text-yellow-500 dark:hover:!text-yellow-300" :title="t('paintings.currently_in', painting.inventory)">
                            {{ painting.inventory }}
                        </inertia-link>

                        <inertia-link :href="'/players/' + painting.artist.license + '/characters/' + painting.artist.id" class="text-indigo-600 text-xs truncate max-w-56 !no-underline dark:text-indigo-300 hover:!text-yellow-500 dark:hover:!text-yellow-300" :title="t('paintings.created_by', painting.artist.name)" v-if="painting.artist">
                            {{ painting.artist.name }}
                        </inertia-link>
                        <span v-else>N/A</span>
                    </div>
                </div>
                <div v-else class="text-danger dark:text-dark-danger font-semibold">
                    {{ t('paintings.failed') }}
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import Layout from '../../Layouts/App.vue';

export default {
    layout: Layout,
    data() {
        return {
            isLoading: false,
            painting: false
        };
    },
    methods: {
        refresh: async function (e) {
            e?.preventDefault();

            if (this.isLoading) {
                return;
            }

            this.isLoading = true;

            try {
                const data = await _get('/api/painting');

                this.painting = data?.data;
            } catch { }

            this.isLoading = false;
        }
    },
    mounted() {
        this.refresh(null);
    }
}
</script>
