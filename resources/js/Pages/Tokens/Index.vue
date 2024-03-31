<template>
    <div>
        <portal to="title">
            <h1 class="dark:text-white">
                <i class="mr-3 fas fa-unlock-alt" :title="perm.restriction(perm.PERM_API_TOKENS)"></i>

                {{ t('tokens.title') }}

                <sup>
                    <i class="fa fa-refresh animate-spin text-lg" @click="addEmptyToken()" v-if="isLoading"></i>
                    <i class="fas fa-plus cursor-pointer text-lg" @click="addEmptyToken()" v-else></i>
                </sup>
            </h1>
            <p>
                {{ t('tokens.description') }}
            </p>
        </portal>

        <div class="mt-14">
            <div class="flex flex-wrap gap-4">
                <div v-for="token in list" :key="token.id" class="bg-gray-200 dark:bg-gray-700 border-gray-500 px-4 py-2 rounded-sm shadow-sm relative w-80">
                    <div class="flex justify-between gap-3 items-center">
                        <input v-model="token.note" class="px-1.5 py-0.5 block bg-gray-200 dark:bg-gray-800 text-sm w-full" :placeholder="t('tokens.note_placeholder')" @input="token.changed = true" v-if="token.id === editingNameId" />
                        <b class="cursor-pointer block" @click="editingNameId = token.id" v-else>{{ token.note ? token.note : `Token #${token.id}` }}</b>

                        <i class="fas fa-copy cursor-pointer" @click="copyToken(token)"></i>
                    </div>

                    <div class="mt-1 pt-1 border-t border-gray-500 w-full text-sm">
                        <template v-if="token.requests > 0">
                            <span class="italic">{{ numberFormat(token.requests, 0, false) }}</span> total requests.<br>
                            Last used <span class="italic">{{ token.lastRequest * 1000 | formatTime }}</span>
                        </template>

                        <template v-else>{{ t('tokens.not_used') }}</template>
                    </div>

                    <div class="mt-1 pt-1 border-t border-gray-500 flex flex-col gap-1">
                        <div class="font-semibold text-sm border-b border-gray-500 border-dashed flex justify-between items-center">
                            {{ t('tokens.permissions') }}

                            <i class="fas fa-plus cursor-pointer" @click="addEmptyPermission(token)"></i>
                        </div>

                        <div class="text-sm bg-gray-200 dark:bg-gray-700 flex" v-for="(permission, index) in token.permissions" :key="index">
                            <select v-model="permission.method" class="px-1 py-0.5 block bg-gray-200 dark:bg-gray-800 text-sm w-32 border-r-0" @change="token.changed = true">
                                <option v-for="method in methods" :value="method">{{ method }}</option>
                            </select>

                            <select v-model="permission.path" class="px-1 py-0.5 block bg-gray-200 dark:bg-gray-800 text-sm w-full" @change="token.changed = true">
                                <option value="*">*</option>

                                <option v-for="path in routes[permission.method]" :value="path">{{ path }}</option>
                            </select>

                            <button class="p-0.5 w-8 flex items-center justify-center bg-gray-200 dark:bg-gray-800 border border-input border-l-0">
                                <i class="fas fa-minus cursor-pointer" @click="token.permissions.splice(index, 1)"></i>
                            </button>
                        </div>

                        <div class="text-sm bg-gray-200 dark:bg-gray-700" v-if="token.permissions.length === 0">
                            {{ t('tokens.no_permissions') }}
                        </div>
                    </div>

                    <div class="flex gap-2 mt-1 pt-1 border-t border-gray-500">
                        <button class="p-1 mt-2 text-sm font-bold leading-4 text-center w-full rounded-sm border-red-400 bg-secondary dark:bg-dark-secondary border-2 block" @click="deleteToken(token.id)" v-if="token.id">
                            {{ t('tokens.delete') }}
                        </button>

                        <button class="p-1 mt-2 text-sm font-bold leading-4 text-center w-full rounded-sm border-lime-400 bg-secondary dark:bg-dark-secondary border-2 block" @click="saveChanges(token)" v-if="token.changed">
                            {{ t('tokens.save') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import Layout from './../../Layouts/App';

export default {
    layout: Layout,
    props: {
        tokens: {
            type: Array,
            required: true
        },
        methods: {
            type: Array,
            required: true
        },
        routes: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            isLoading: false,
            editingNameId: false,

            list: this.tokens.map(token => {
                token.changed = false;

                return token;
            })
        };
    },
    methods: {
        async deleteToken(id) {
            if (this.isLoading) return;

            if (!confirm(this.t('tokens.delete_confirm'))) return;

            this.isLoading = true;

            try {
                const result = await axios.delete(`/tokens/${id}`);

                if (result.data && result.data.status) {
                    this.list = this.list.filter(token => token.id !== id);
                }
            } catch (e) { }

            this.isLoading = false;
        },
        async saveChanges(token) {
            if (this.isLoading) return;

            this.isLoading = true;

            token.permissions = token.permissions.map(permission => {
                return {
                    method: permission.method,
                    path: permission.path.trim() || '*'
                };
            });

            const data = {
                note: token.note,
                permissions: token.permissions.map(permission => `${permission.method} ${permission.path}`).join(',')
            };

            try {
                const result = await axios.put(`/tokens/${token.id}`, data);

                if (result.data && result.data.status) {
                    token.changed = false;

                    if (token.id === this.editingNameId) {
                        this.editingNameId = false;
                    }
                }
            } catch (e) { }

            this.isLoading = false;
        },
        addEmptyPermission(token) {
            token.permissions.push({
                method: 'GET',
                path: ''
            });

            token.changed = true;
        },
        async addEmptyToken() {
            if (this.isLoading) return;

            this.isLoading = true;

            try {
                const result = await axios.post('/tokens');

                if (result.data && result.data.status) {
                    this.list.push(result.data.data);
                }
            } catch (e) { }

            this.isLoading = false;
        },
        copyToken(token) {
            this.copyToClipboard(token.token);
        }
    }
}
</script>
