<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t('y.title') }}
            </h1>
            <p>
                {{ t('y.description') }}
            </p>
        </portal>

        <portal to="actions">
            <button class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" type="button" @click="refresh">
                <span v-if="!isLoading">
                    <i class="fa fa-redo-alt mr-1"></i>
                    {{ t('global.refresh') }}
                </span>
                <span v-else>
                    <i class="fas fa-spinner animate-spin mr-1"></i>
                    {{ t('global.loading') }}
                </span>
            </button>
        </portal>

        <!-- Querying -->
        <v-section :noFooter="true">
            <template #header>
                <h2>
                    {{ t('y.filter') }}
                </h2>
            </template>

            <template>
                <form @submit.prevent>
                    <div class="flex flex-wrap mb-4">
                        <!-- Details -->
                        <div class="w-3/12 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-3" for="username">
                                {{ t('y.account') }} <sup class="text-muted dark:text-dark-muted">*</sup>
                            </label>
                            <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="username" :placeholder="t('y.placeholder_username')" v-model="filters.username" :title="previewQuery(filters.username)">
                        </div>
                        <!-- Details -->
                        <div class="w-7/12 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-3" for="message">
                                {{ t('y.message') }} <sup class="text-muted dark:text-dark-muted">*</sup>
                            </label>
                            <input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="message" :placeholder="t('y.placeholder_message')" v-model="filters.message" :title="previewQuery(filters.message)">
                        </div>
                        <!-- Top Yells -->
                        <div class="w-2/12 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-3" for="top">
                                {{ t('y.top') }}
                            </label>
                            <select class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="top" v-model="filters.top">
                                <option :value="null">{{ t('global.no') }}</option>
                                <option :value="1">{{ t('global.yes') }}</option>
                            </select>
                        </div>
                    </div>
                    <!-- Description -->
                    <div class="w-full px-3 mt-3">
                        <small class="text-muted dark:text-dark-muted mt-1 leading-4 block" v-html="t('global.search.custom')"></small>
                    </div>
                    <!-- Search button -->
                    <div class="w-full px-3 mt-3">
                        <button class="px-5 py-2 font-semibold text-white bg-success dark:bg-dark-success rounded hover:shadow-lg" @click="refresh">
                            <span v-if="!isLoading">
                                <i class="fas fa-search"></i>
                                {{ t('y.search') }}
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

        <template>
            <h2 class="mb-4 max-w-2xl m-auto text-2xl">{{ t('y.title') }}</h2>

            <div class="w-full flex flex-wrap max-w-2xl m-auto">
                <YPost v-for="post in posts" :key="post.id" :post="post" :user="post" :selectionChange="selectPost" />

                <div class="mt-3" v-if="selectedPosts.length > 0">
                    <button class="px-5 py-2 font-semibold text-white bg-danger dark:bg-dark-danger rounded hover:shadow-lg" @click="deleteSelected">
                        <i class="fas fa-trash"></i>
                        {{ t('y.delete_selected') }}
                    </button>
                </div>
            </div>
        </template>

        <template>
            <div class="flex items-center justify-between mt-6 mb-1">

                <!-- Navigation -->
                <div class="flex flex-wrap">
                    <inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="links.prev" v-if="page >= 2">
                        <i class="mr-1 fas fa-arrow-left"></i>
                        {{ t("pagination.previous") }}
                    </inertia-link>
                    <inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" v-if="posts.length === 30" :href="links.next">
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
    props: {
        posts: {
            type: Array,
            required: true,
        },
        filters: {
            username: String,
            message: String,
            top: Number,
        },
        links: {
            type: Object,
            required: true,
        },
        page: {
            type: Number,
            required: true,
        },
        time: {
            type: Number,
            required: true,
        }
    },
    data() {
        return {
            isLoading: false,

            selectedPosts: []
        };
    },
    methods: {
        selectPost($event, id) {
            if ($event.target.checked) {
                this.selectedPosts.push(id);
            } else {
                this.selectedPosts = this.selectedPosts.filter(postId => postId !== id);
            }
        },
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
        refresh: async function () {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;

            try {
                await this.$inertia.replace('/y', {
                    data: this.filters,
                    preserveState: true,
                    preserveScroll: true,
                    only: ['posts', 'userMap', 'time', 'links', 'page']
                });

                this.selectedPosts = [];
            } catch (e) { }

            this.isLoading = false;
        },
    },
}
</script>
