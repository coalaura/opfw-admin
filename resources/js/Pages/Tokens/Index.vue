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
                <div v-for="token in list" :key="token.id" class="bg-gray-200 dark:bg-gray-700 border-gray-500 px-4 py-2 rounded-sm shadow-sm relative w-80" :class="{ '!bg-blue-500 !bg-opacity-20 pb-6': token.disabled }">
                    <div class="flex justify-between gap-3 items-center">
                        <input v-model="token.note" class="px-1.5 py-0.5 block bg-gray-200 dark:bg-gray-800 text-sm w-full" :placeholder="t('tokens.note_placeholder')" @input="token.changed = true" v-if="token.id === editingNameId" autofocus />
                        <b class="cursor-pointer block" @click="editingNameId = token.id" v-else>{{ token.note ? token.note : `Token #${token.id}` }}</b>

                        <div class="flex gap-2">
                            <i class="fas fa-receipt cursor-pointer" @click="viewLogs(token.id)"></i>
                            <i class="fas fa-copy cursor-pointer" @click="copyToken(token)"></i>
                        </div>
                    </div>

                    <div class="text-sm text-gray-600 dark:text-gray-300 font-mono italic mt-1 pt-1 border-t border-gray-500" :class="{ '!border-blue-400': token.disabled }">
                        Token: {{ token.token.substring(0, 3) }}{{ ".".repeat(token.token.length - 5) }}{{ token.token.substring(token.token.length - 3) }}
                    </div>

                    <div class="mt-1 pt-1 border-t border-gray-500 w-full text-sm" :class="{ '!border-blue-400': token.disabled }">
                        <template v-if="token.requests > 0">
                            <span class="italic">{{ numberFormat(token.requests, 0, false) }}</span> total requests.<br>
                            Last used <span class="italic">{{ token.lastRequest * 1000 | formatTime }}</span>
                        </template>

                        <template v-else>{{ t('tokens.not_used') }}</template>
                    </div>

                    <div class="mt-1 pt-1 border-t border-gray-500 flex flex-col gap-1" :class="{ '!border-blue-400': token.disabled }">
                        <div class="font-semibold text-sm border-b border-gray-500 border-dashed flex justify-between items-center" :class="{ '!border-blue-400': token.disabled }">
                            {{ t('tokens.permissions') }}

                            <i class="fas fa-plus cursor-pointer" @click="addEmptyPermission(token)" v-if="!token.disabled"></i>
                        </div>

                        <div class="text-sm bg-gray-200 dark:bg-gray-700 flex" v-for="(permission, index) in token.permissions" :key="index">
                            <select v-model="permission.method" class="px-1 py-0.5 block bg-gray-200 dark:bg-gray-800 text-sm w-32 border-r-0" @change="token.changed = true" :disabled="token.disabled" :class="{ '!bg-blue-500 !bg-opacity-20 border-blue-400': token.disabled }">
                                <option v-for="method in methods" :value="method">{{ method }}</option>
                            </select>

                            <select v-model="permission.path" class="px-1 py-0.5 block bg-gray-200 dark:bg-gray-800 text-sm w-full" @change="token.changed = true" :disabled="token.disabled" :class="{ '!bg-blue-500 !bg-opacity-20 border-blue-400': token.disabled }">
                                <option value="*">*</option>

                                <option v-for="path in routes[permission.method]" :value="path">{{ path }}</option>
                            </select>

                            <button class="p-0.5 w-8 flex items-center justify-center bg-gray-200 dark:bg-gray-800 border border-input border-l-0" v-if="!token.disabled">
                                <i class="fas fa-minus cursor-pointer" @click="token.permissions.splice(index, 1)"></i>
                            </button>
                        </div>

                        <div class="text-sm bg-gray-200 dark:bg-gray-700" v-if="token.permissions.length === 0">
                            {{ t('tokens.no_permissions') }}
                        </div>
                    </div>

                    <div class="absolute bottom-0.5 left-1.5 text-xs italic" v-if="token.disabled">
                        {{ t('tokens.panel_token') }}
                    </div>

                    <div class="flex gap-2 mt-1 pt-1 border-t border-gray-500" v-if="!token.disabled">
                        <button class="p-1 mt-2 text-sm font-bold leading-4 text-center w-full rounded-sm border-red-400 bg-secondary dark:bg-dark-secondary border-2 block" @click="deleteToken(token.id)">
                            {{ t('tokens.delete') }}
                        </button>

                        <button class="p-1 mt-2 text-sm font-bold leading-4 text-center w-full rounded-sm border-lime-400 bg-secondary dark:bg-dark-secondary border-2 block" @click="saveChanges(token)" v-if="token.changed">
                            {{ t('tokens.save') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <modal :show.sync="showingLogs">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('tokens.api_logs') }}
                </h1>
                <p class="!not-italic font-semibold">
                    {{ getLogTokenNote(logTokenId) }}
                </p>
                <p class="text-sm" v-if="logInfo">
                    {{ t('tokens.per_second', logInfo.rps, logInfo.count) }}
                </p>
            </template>

            <template #default>
                <div class="max-h-96 overflow-y-auto border-gray-500 border" ref="logs">
                    <div class="text-sm bg-gray-200 dark:bg-gray-700 flex border-b border-gray-500" v-for="log in logs" :key="log.id">
                        <div class="px-1 py-0.5 w-10 flex-shrink-0 bg-blue-600 text-white" v-if="log.status_code >= 100 && log.status_code < 200">{{ log.status_code }}</div>
                        <div class="px-1 py-0.5 w-10 flex-shrink-0 bg-lime-600 text-white" v-else-if="log.status_code >= 200 && log.status_code < 300">{{ log.status_code }}</div>
                        <div class="px-1 py-0.5 w-10 flex-shrink-0 bg-yellow-600 text-white" v-else-if="log.status_code >= 300 && log.status_code < 400">{{ log.status_code }}</div>
                        <div class="px-1 py-0.5 w-10 flex-shrink-0 bg-purple-600 text-white" v-else-if="log.status_code >= 400 && log.status_code < 500">{{ log.status_code }}</div>
                        <div class="px-1 py-0.5 w-10 flex-shrink-0 bg-red-600 text-white" v-else-if="log.status_code >= 500 && log.status_code < 600">{{ log.status_code }}</div>
                        <div class="px-1 py-0.5 w-10 flex-shrink-0 bg-gray-800 text-white" v-else>{{ log.status_code }}</div>

                        <div class="px-1 py-0.5 w-28 truncate flex-shrink-0">{{ log.ip_address.replace(/:\d+$/m, '') }}</div>
                        <div class="px-1 py-0.5 w-12 flex-shrink-0 font-semibold">{{ log.method }}</div>
                        <div class="px-1 py-0.5 w-full truncate">{{ log.path }}</div>
                        <div class="px-1 py-0.5 w-48 flex-shrink-0 text-right">{{ log.timestamp * 1000 | formatTime(true) }}</div>
                    </div>

                    <div class="px-1 py-0.5 text-sm bg-gray-200 dark:bg-gray-700 flex justify-center" v-if="isLoadingLogs">
                        {{ t('tokens.loading_logs') }}
                    </div>
                    <div class="px-1 py-0.5 text-sm bg-gray-200 dark:bg-gray-700 flex justify-center" v-else-if="logs.length === 0">{{ t('tokens.no_logs') }}</div>
                    <div class="px-1 py-0.5 text-sm bg-gray-200 dark:bg-gray-700 flex justify-center" v-else-if="logs.length > 0 && !moreLogs">{{ t('tokens.no_more_logs') }}</div>
                </div>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="showingLogs = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

    </div>
</template>

<script>
import Layout from './../../Layouts/App';
import Modal from './../../Components/Modal';

export default {
    layout: Layout,
    components: {
        Modal
    },
    props: {
        panel: {
            type: String
        },
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
                token.disabled = this.panel === token.token;

                token.changed = false;

                return token;
            }),

            controller: new AbortController(),
            showingLogs: false,
            logTokenId: false,
            isLoadingLogs: false,
            moreLogs: false,
            logs: [],
            logInfo: false
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
        },
        async loadMoreLogs() {
            this.isLoadingLogs = true;

            const lastId = this.logs.length ? this.logs[this.logs.length - 1].id : 0,
                query = [`id=${this.logTokenId}`];

            if (lastId) {
                query.push(`before=${lastId}`);
            }

            try {
                const result = await axios.get('/tokens/logs?' + query.join('&'), {
                    signal: this.controller.signal
                });

                if (result.data && result.data.status) {
                    const logs = result.data.data;

                    this.logs = this.logs.concat(logs);

                    this.moreLogs = logs.length === 50;
                    this.logsLoaded = true;
                }
            } catch (e) { }

            this.isLoadingLogs = false;
        },
        async loadLogInfo() {
            try {
                const result = await axios.get(`/tokens/rps?id=${this.logTokenId}`, {
                    signal: this.controller.signal
                });

                if (result.data && result.data.status) {
                    this.logInfo = result.data.data;
                }
            } catch (e) { }
        },
        getLogTokenNote(tokenId) {
            const token = this.list.find(token => token.id === tokenId);

            return token ? token.note : `Token #${tokenId}`;
        },
        viewLogs(tokenId) {
            this.controller.abort();

            this.showingLogs = true;
            this.logTokenId = tokenId;
            this.logs = [];
            this.moreLogs = false;

            this.loadLogInfo();
            this.loadMoreLogs();

            this.$nextTick(() => {
                // Infinite scroll, pog
                this.$refs.logs.addEventListener('scroll', () => {
                    if (this.isLoadingLogs || !this.moreLogs) return;

                    if (this.$refs.logs.scrollTop + this.$refs.logs.clientHeight >= this.$refs.logs.scrollHeight) {
                        this.loadMoreLogs();
                    }
                });
            });
        }
    }
}
</script>
