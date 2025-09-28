<template>
    <div>
        <portal to="title">
            <h1>@{{ user.username }}</h1>
        </portal>

        <template>
            <div class="w-full items-center flex flex-wrap mb-6 max-w-screen-md m-auto relative">
                <div class="mr-3">
                    <img class="block w-24 h-24 rounded-full object-cover" :src="user.avatar_url" v-handle-error="'/images/default_profile.png'" />
                </div>
                <div>
                    <h2 class="text-xl dark:text-white">
                        @{{ user.username }}
                        <span class="verified" v-if="user.is_verified">&nbsp;</span>
                    </h2>

                    <inertia-link :href="'/players/' + character.licenseIdentifier + '/characters/' + character.id" class="hover:underline text-base text-gray-500 dark:text-gray-400" v-if="character">
                        {{ character.name }} #{{ character.id }}
                    </inertia-link>

                    <div class="text-base text-gray-500 dark:text-gray-400 italic" v-else>{{ t('y.unknown_character') }}</div>
                </div>

                <button @click="toggleVerify" class="p-1 top-1 right-1 absolute font-semibold flex items-center justify-center drop-shadow" :title="user.is_verified ? t('y.un_verify') : t('y.verify')" v-if="this.perm.check(this.perm.PERM_Y_VERIFY)">
                    <img v-if="user.is_verified" class="w-7" :src="'/images/un_verify.png'" />
                    <img v-else class="w-7" :src="'/images/verify.png'" />
                </button>
            </div>
        </template>

        <template>
            <div class="w-full flex flex-wrap max-w-2xl m-auto">
                <div v-if="yells.length === 0" class="p-2 italic">{{ t('y.no_yells') }}</div>

                <YPost v-for="post in yells" :key="post.id" :post="post" :user="user" :dont-link="true" :selectionChange="selectPost" v-else />

                <div class="mt-3 flex justify-end w-full" v-if="selectedPosts.length > 0">
                    <button class="px-3 py-1 font-semibold text-sm text-white bg-danger dark:bg-dark-danger rounded hover:shadow-lg" @click="deleteSelected">
                        <i class="fas fa-trash"></i>
                        {{ t('y.delete_selected') }}
                    </button>
                </div>
            </div>
        </template>

        <template v-if="yells.length === 15 || page > 1">
            <div class="flex items-center justify-between mt-6 mb-1">

                <!-- Navigation -->
                <div class="flex flex-wrap">
                    <inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="links.prev" v-if="page >= 2">
                        <i class="mr-1 fas fa-arrow-left"></i>
                        {{ t("pagination.previous") }}
                    </inertia-link>
                    <inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" v-if="yells.length === 30" :href="links.next">
                        {{ t("pagination.next") }}
                        <i class="ml-1 fas fa-arrow-right"></i>
                    </inertia-link>
                </div>

                <!-- Meta -->
                <div class="font-semibold">
                    {{ t("pagination.page", page) }}
                </div>

            </div>
        </template>

        <scoped-style>
            header {
                display: none !important;
            }
        </scoped-style>
    </div>
</template>

<script>
import Layout from './../../Layouts/App.vue';
import VSection from './../../Components/Section.vue';
import Pagination from "../../Components/Pagination.vue";
import YPost from "../../Components/YPost.vue";

export default {
    layout: Layout,
    components: {
        VSection,
        Pagination,
        YPost,
    },
    data() {
        return {
            isLoading: false,

            selectedPosts: []
        }
    },
    methods: {
        async deleteSelected() {
            if (this.isLoading) {
                return;
            }

            if (!confirm(this.t('y.delete_selected_confirm'))) {
                return;
            }

            this.isLoading = true;

            try {
                await this.$inertia.post('/yells/delete', {
                    ids: this.selectedPosts,
                }, {
                    preserveState: true,
                    preserveScroll: true
                });

                this.selectedPosts = [];
            } catch (e) { }

            this.isLoading = false;
        },
        async toggleVerify() {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;

            try {
                await this.$inertia.post(`/y/${this.user.id}/verify`, {}, {
                    preserveState: true,
                    preserveScroll: true
                });
            } catch (e) { }

            this.isLoading = false;
        },
        selectPost($event, id) {
            if ($event.target.checked) {
                this.selectedPosts.push(id);
            } else {
                this.selectedPosts = this.selectedPosts.filter(postId => postId !== id);
            }
        },
    },
    props: {
        yells: {
            type: Array,
            required: true,
        },
        user: {
            type: Object,
            required: true,
        },
        character: {
            type: Object,
        },
        links: {
            type: Object,
            required: true,
        },
        page: {
            type: Number,
            required: true,
        }
    },
}
</script>
