<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t("steam.title") }}
            </h1>
            <p>
                {{ t("steam.description") }}
            </p>
        </portal>

        <div class="flex -mt-6 max-w-screen-2xl mobile:flex-wrap">
            <input class="px-4 py-2 w-96 mr-2 bg-gray-200 dark:bg-gray-600 border rounded" id="search" name="search" placeholder="https://steamcommunity.com/id/twooot/" v-model="search" />
            <button class="px-5 py-2 font-semibold text-white bg-success dark:bg-dark-success rounded hover:shadow-lg" @click="refresh">
                <span v-if="!isLoading">
                    <i class="fas fa-search"></i>
                    {{ t('steam.search') }}
                </span>
                <span v-else>
                    <i class="fas fa-cog animate-spin"></i>
                    {{ t('global.loading') }}
                </span>
            </button>
        </div>

        <div class="mt-14" v-if="!isLoading && result !== false">
            <h3 class="mb-2 dark:text-white">
                {{ t('steam.result') }}
            </h3>
            <table class="table-fixed max-w-screen-lg text-left">
                <template v-if="!result || result.error">
                    <tr>
                        <td class="px-6 py-4 text-center border-t" colspan="2">
                            {{ result ? result.error : t('steam.no_result') }}
                        </td>
                    </tr>
                </template>

                <template v-else>
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-600 border-b border-gray-500">
                        <th class="px-4 py-2 text-left font-semibold">{{ t('steam.steam_id') }}</th>
                        <td class="px-4 py-2 text-left">{{ result.steamId }}</td>
                    </tr>
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-600 border-b border-gray-500">
                        <th class="px-4 py-2 text-left font-semibold">{{ t('steam.steam2') }}</th>
                        <td class="px-4 py-2 text-left">{{ result.steam2 }}</td>
                    </tr>
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-600 border-b border-gray-500">
                        <th class="px-4 py-2 text-left font-semibold">{{ t('steam.steam3') }}</th>
                        <td class="px-4 py-2 text-left">{{ result.steam3 }}</td>
                    </tr>
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-600 border-b border-gray-500">
                        <th class="px-4 py-2 text-left font-semibold">{{ t('steam.steam_hex') }}</th>
                        <td class="px-4 py-2 text-left">
                            <a :href="'/players/' + result.steamHex" target="_blank" class="text-blue-600 dark:text-blue-400">{{ result.steamHex }}</a>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-600 border-b border-gray-500">
                        <th class="px-4 py-2 text-left font-semibold">{{ t('steam.steam_url') }}</th>
                        <td class="px-4 py-2 text-left">
                            <a :href="result.invite" target="_blank" class="text-blue-600 dark:text-blue-400">{{ result.invite }}</a>
                        </td>
                    </tr>
                </template>
            </table>
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
            result: false,

            search: ""
        };
    },
    methods: {
        refresh: async function () {
            if (this.isLoading) return;

            this.isLoading = true;

            try {
                const data = await _post('/steam', {
                    search: this.search
                });

                this.result = data;
            } catch (e) { }

            this.isLoading = false;
        }
    }
}
</script>
