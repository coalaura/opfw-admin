<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t('y.top') }}
            </h1>
            <p>
                Most liked yells from the last 15 days.
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

        <template>
            <div class="w-full flex flex-wrap max-w-2xl m-auto mt-6">
                <YPost v-for="post in posts" :key="post.id" :post="post" :user="post" :selectionChange="selectPost" />

                <div class="mt-3 w-full" v-if="selectedPosts.length > 0">
                    <button class="px-5 py-2 font-semibold text-white bg-danger dark:bg-dark-danger rounded hover:shadow-lg" @click="deleteSelected">
                        <i class="fas fa-trash"></i>
                        {{ t('y.delete_selected') }}
                    </button>
                </div>

                <div class="w-full text-center py-8 text-gray-500 dark:text-gray-400" v-if="posts.length === 0">
                    {{ t('y.no_posts') }}
                </div>
            </div>
        </template>
    </div>
</template>

<script>
import Layout from './../../Layouts/App.vue';
import YPost from "../../Components/YPost.vue";

export default {
    layout: Layout,
    components: {
        YPost,
    },
    props: {
        posts: {
            type: Array,
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
                await this.$inertia.replace('/y/top', {
                    preserveState: true,
                    preserveScroll: true,
                    only: ['posts', 'time']
                });

                this.selectedPosts = [];
            } catch (e) { }

            this.isLoading = false;
        },
    }
}
</script>
