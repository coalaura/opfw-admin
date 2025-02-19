<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t("discord.title") }}
            </h1>
            <p>
                {{ t("discord.description") }}
            </p>
        </portal>

        <div class="flex -mt-6 max-w-screen-2xl mobile:flex-wrap">
            <input class="px-4 py-2 w-96 mr-2 bg-gray-200 dark:bg-gray-600 border rounded" id="search" name="search" placeholder="@coalaura" v-model="search" />
            <button class="px-5 py-2 font-semibold text-white bg-success dark:bg-dark-success rounded hover:shadow-lg" @click="refresh">
                <span v-if="!isLoading">
                    <i class="fas fa-search"></i>
                    {{ t('discord.search') }}
                </span>
                <span v-else>
                    <i class="fas fa-cog animate-spin"></i>
                    {{ t('global.loading') }}
                </span>
            </button>
        </div>

        <div class="mt-14" v-if="!isLoading && result !== false">
            <h3 class="mb-2 dark:text-white">
                {{ t('discord.result') }}
            </h3>
            <table class="table-fixed max-w-screen-lg text-left">
                <template v-if="!result || result.error">
                    <div class="flex rounded bg-red-600 bg-opacity-60 text-white shadow-sm min-w-box">
                        <div class="flex flex-col justify-center p-5">
                            <h4 class="m-0 text-lg drop-shadow-sm leading-6">
                                {{ result ? result.error : t('discord.no_result') }}
                            </h4>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <div class="flex rounded bg-discord text-white shadow-sm min-w-box">
                        <a class="block" :href="`https://cdn.discordapp.com/avatars/${result.user.id}/${result.user.avatar}.webp`" target="_blank">
                            <img class="w-28" :src="`https://cdn.discordapp.com/avatars/${result.user.id}/${result.user.avatar}.webp`" />
                        </a>

                        <div class="flex flex-col justify-center px-5">
                            <h4 class="m-0 text-lg drop-shadow-sm leading-6">
                                @{{ result.user.username }}{{ result.user.discriminator !== "0" ? "#" + result.user.discriminator : "" }}
                            </h4>

                            <p class="m-0 text-sm font-mono select-all">
                                {{ result.user.id }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-8">
                        <p class="m-0" v-if="result.players.length === 0">{{ t('discord.no_players') }}</p>

                        <template v-else>
                            <h4 class="m-0 text-lg dark:text-white w-full">{{ result.players.length }} {{ t('discord.linked_players') }}</h4>

                            <inertia-link class="block px-4 py-2 truncate font-semibold text-center text-white bg-discord rounded" :href="'/players/' + player.license_identifier" v-for="player in result.players" :key="player.license_identifier">
                                {{ player.player_name }}
                            </inertia-link>
                        </template>
                    </div>
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
                const data = await fetch('/discord', {
                    method: 'POST',
                    body: post_data({
                        search: this.search
                    })
                }).then(response => response.json());

                if (data) {
                    this.result = data;
                } else {
                    this.result = null;
                }
            } catch (e) { }

            this.isLoading = false;
        }
    }
}
</script>
