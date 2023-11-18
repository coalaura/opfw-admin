<template>
    <div class="flex pt-3 pb-3 border-t w-full border-gray-400 dark:border-gray-500 px-2 relative hover:bg-gray-100 dark:hover:bg-gray-700">
        <div v-if="dontLink" class="mr-2 flex-shrink-0">
            <img class="block w-12 h-12 rounded-full" :src="user.avatar_url" @error="avatarError" />
            <span class="block text-xs text-center mt-2 text-gray-500 dark:text-gray-400">
                <i class="fas fa-heart text-red-800 dark:text-red-500"></i> {{ post.likes }}
            </span>
        </div>
        <inertia-link class="block mr-2 flex-shrink-0" :href="'/twitter/' + post.authorId" v-else>
            <img class="block w-12 h-12 rounded-full" :src="user.avatar_url" @error="avatarError" />
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
            <inertia-link :href="'/twitter/' + post.authorId" class="block mb-2 font-bold" v-else>
                <span class="hover:underline">{{ user.username }}</span>
                <span class="verified" v-if="user.is_verified">&nbsp;</span>
                <span :title="post.time | formatTime(true)" class="text-gray-400 dark:text-gray-500 font-normal">- {{ formatDate(post.time) }}</span>
            </inertia-link>

            <div class="text-sm block" v-html="formatBody(post.message)"></div>
        </div>

        <input type="checkbox" class="absolute top-1 right-1 !outline-none" @change="selectionChange($event, post.id)" v-if="selectionChange && canSeeDelete()" />
    </div>
</template>
<script>
export default {
    name: 'TwitterPost',
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
    methods: {
        canSeeDelete() {
            return this.perm.check(this.perm.PERM_TWITTER);
        },
        formatDate(date) {
            const d = this.$moment.utc(date).local(),
                day = d.format('DD-MM-YYYY'),
                today = this.$moment().format('DD-MM-YYYY'),
                yesterday = this.$moment().subtract(1, 'days').format('DD-MM-YYYY'),
                time = d.format('h:mm A');

            if (day === today) {
                return 'Today at ' + time;
            } else if (day === yesterday) {
                return 'Yesterday at ' + time;
            }

            return d.format('MM/DD/YYYY');
        },
        avatarError(e) {
            // Replace with default
            e.target.src = '/images/default_profile.png';
        },
        formatBody(body) {
            body = body.trim();

            if (body.match(/^https?:\/\/[^\s]+?\.(png|jpe?g|gif|bmp|webp)(\?[^\s]*)?$/i)) {
                return '<div class="max-w-screen-sm"><img src="' + body + '" class="block max-w-full max-h-img" /></div>';
            }

            return body;
        }
    },
}
</script>
