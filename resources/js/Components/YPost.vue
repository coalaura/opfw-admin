<template>
    <div class="flex pt-3 pb-3 border-t w-full border-gray-400 dark:border-gray-500 px-2 relative hover:bg-gray-100 dark:hover:bg-gray-700">
        <div v-if="dontLink" class="mr-2 flex-shrink-0">
            <img class="block w-12 h-12 rounded-full object-cover" :src="user.avatar_url" @error="avatarError" />
            <span class="block text-xs text-center mt-2 text-gray-500 dark:text-gray-400">
                <i class="fas fa-heart text-red-800 dark:text-red-500"></i> {{ post.likes }}
            </span>
        </div>
        <inertia-link class="block mr-2 flex-shrink-0" :href="'/y/' + post.authorId" v-else>
            <img class="block w-12 h-12 rounded-full object-cover" :src="user.avatar_url" @error="avatarError" />
            <span class="block text-xs text-center mt-2 text-gray-500 dark:text-gray-400">
                <i class="fas fa-heart text-red-600 dark:text-red-500"></i> {{ post.likes }}
            </span>
        </inertia-link>

        <div>
            <div v-if="dontLink" class="block mb-2 font-bold">
                {{ user.username }}
                <span class="verified" v-if="user.is_verified">&nbsp;</span>
                <span :title="post.time | formatTime(true)" class="text-gray-400 dark:text-gray-500 font-normal">- {{ formatDate(post.time) }}</span>
            </div>
            <inertia-link :href="'/y/' + post.authorId" class="block mb-2 font-bold" v-else>
                <span class="hover:underline">{{ user.username }}</span>
                <span class="verified" v-if="user.is_verified">&nbsp;</span>
                <span :title="post.time | formatTime(true)" class="text-gray-400 dark:text-gray-500 font-normal">- {{ formatDate(post.time) }}</span>
            </inertia-link>

            <div class="text-sm block" v-html="formatBody(post.message)"></div>
        </div>

        <div class="absolute top-1 right-1 flex gap-1 items-center">
            <button class="text-red-500 dark:text-red-400 no-underline drop-shadow-sm leading-none" @click="deletePost()" v-if="canSeeDelete()" :title="t('y.delete_quick')">
                <i class="fas fa-trash-alt"></i>
            </button>

            <button class="text-yellow-500 dark:text-yellow-400 no-underline drop-shadow-sm leading-none" @click="editingPost = true" v-if="canSeeEdit()" :title="t('y.edit_post_title')">
                <i class="fas fa-pen-square"></i>
            </button>

            <input type="checkbox" class="!outline-none drop-shadow-sm" @change="selectionChange($event, post.id)" v-if="selectionChange && canSeeDelete()" :title="t('y.delete_mark')" />
        </div>

        <modal :show="editingPost">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('y.edit_post') }}
                </h1>
            </template>

            <template #default>
                <div class="w-full p-3 flex justify-between px-0">
                    <label class="mr-4 block w-1/4 pt-2 font-bold">
                        {{ t('y.likes') }}
                        <span v-if="hasEdited('likes')">*</span>
                    </label>
                    <input class="w-3/4 px-4 py-2 bg-gray-200 dark:bg-gray-600 border-2 border-gray-500 rounded" :class="{ 'border-lime-500': hasEdited('likes') }" id="likes" type="number" step="1" min="0" max="10000000" v-model="edit.likes" />
                </div>

                <div class="w-full p-3 flex justify-between px-0">
                    <label class="mr-4 block w-1/4 pt-2 font-bold">
                        {{ t('y.message') }}
                        <span v-if="hasEdited('message')">*</span>
                    </label>
                    <textarea class="block w-3/4 py-2 bg-gray-200 dark:bg-gray-600 border-2 border-gray-500 rounded" :class="{ 'border-lime-500': hasEdited('message') }" id="message" placeholder="meow :)" rows="4" v-model="edit.message"></textarea>
                </div>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-blue-200 dark:bg-blue-600 dark:hover:bg-blue-400" @click="updatePost()">
                    {{ t('y.save') }}
                </button>

                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="editingPost = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>
    </div>
</template>

<script>
import Modal from './Modal.vue';

export default {
    name: 'YPost',
    components: {
        Modal
    },
    props: {
        post: {
            type: Object,
            required: true
        },
        user: {
            type: Object,
            required: true
        },
        dontLink: {
            type: Boolean,
            default: false
        },
        selectionChange: {
            type: Function
        }
    },
    data() {
        return {
            isLoading: false,
            editingPost: false,

            edit: {
                message: this.post.message,
                likes: this.post.likes
            }
        };
    },
    methods: {
        canSeeEdit() {
            return this.perm.check(this.perm.PERM_Y_EDIT);
        },
        canSeeDelete() {
            return this.perm.check(this.perm.PERM_Y);
        },
        hasEdited(field) {
            return this.edit[field] !== this.post[field];
        },
        async deletePost() {
            if (!confirm(this.t('y.delete_confirm'))) return;

            if (this.isLoading) return;

            this.isLoading = true;

            await this.$inertia.post('/yells/delete', {
                ids: [this.post.id],
            }, {
                preserveState: true,
                preserveScroll: true
            });

            this.isLoading = false;
        },
        async updatePost() {
            const data = {};

            if (this.hasEdited('message')) {
                data.message = this.edit.message.trim();
            }

            if (this.hasEdited('likes')) {
                data.likes = this.edit.likes;
            }

            // Nothing changed lol
            if (Object.keys(data).length === 0) return;

            if (this.isLoading) return;

            this.isLoading = true;
            this.editingPost = false;

            await this.$inertia.post(`/yells/edit/${this.post.id}`, data, {
                preserveState: true,
                preserveScroll: true
            });

            this.isLoading = false;
        },
        formatDate(date) {
            const d = dayjs.utc(date).local(),
                day = d.format('DD-MM-YYYY'),
                today = dayjs().format('DD-MM-YYYY'),
                yesterday = dayjs().subtract(1, 'days').format('DD-MM-YYYY'),
                time = d.format('h:mm A');

            if (day === today) {
                return `Today at ${time}`;
            } else if (day === yesterday) {
                return `Yesterday at ${time}`;
            }

            return d.format('MM/DD/YYYY');
        },
        avatarError(e) {
            // Replace with default
            e.target.src = '/images/default_profile.png';
        },
        formatBody(body) {
            body = this.escapeHtml(body.trim());

            return body.replace(/https?:\/\/[^\s]+?\.(png|jpe?g|gif|bmp|webp)(\?[^\s]*)?/gi, match => {
                return `<a href="${match}" target="_blank" class="block max-w-full w-y-img h-y-img overflow-hidden rounded-xl border border-gray-500"><img src="${match}" class="block w-full h-full object-cover translate hover:scale-105" /></a>`;
            });
        }
    },
}
</script>
