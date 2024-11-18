<template>
    <div>
        <portal to="title">
            <h1 class="dark:text-white">
                {{ t('roles.title') }}
            </h1>
            <p>
                {{ t('roles.description') }}
                <span v-if="readonly">({{ t('roles.readonly') }})</span>
            </p>
        </portal>

        <template>
            <div class="bg-gray-100 p-6 rounded shadow-lg max-w-full dark:bg-gray-600">
                <table class="whitespace-nowrap w-full">
                    <tr class="bg-gray-400 dark:bg-gray-800 no-alpha">
                        <th class="font-bold px-4 py-1.5 text-left cursor-pointer" @click="sortBy('name')">{{ t('roles.player') }}</th>
                        <th class="font-bold px-4 py-1.5 text-left cursor-pointer" @click="sortBy(role)" v-for="role in roleList" :key="role">{{ t('roles.' + role) }}</th>
                        <th class="font-bold px-4 py-1.5 text-left" v-if="!readonly">&nbsp;</th>
                    </tr>

                    <tr v-for="player in playerList" :key="player.license" class="odd:bg-gray-200 dark:odd:bg-gray-500 hover:bg-gray-300 dark:hover:bg-gray-700">
                        <td class="italic px-4 py-1.5">
                            <a :href="`/players/${player.license}`" target="_blank">
                                {{ player.name }}
                                <sup v-if="player.hasOverrides">*</sup>
                            </a>
                        </td>

                        <td class="italic px-4 py-1.5 text-xl relative" v-for="role in roleList" :key="role" :class="{'opacity-50': !canEditRole(role)}">
                            <i class="fas fa-toggle-on cursor-pointer text-lime-600 dark:text-lime-400" :class="{'cursor-not-allowed': !canEditRole(role)}" @click="toggleOverride(player, role)" v-if="player.overrides[role]"></i>
                            <i class="fas fa-toggle-off cursor-pointer text-red-600 dark:text-red-400" :class="{'cursor-not-allowed': !canEditRole(role)}" v-else @click="toggleOverride(player, role)"></i>
                        </td>

                        <td class="italic px-4 py-1.5 w-24" v-if="!readonly">
                            <button class="px-2 py-0.5 rounded bg-danger dark:bg-dark-danger" @click="resetOverrides(player)" :title="t('roles.reset')" :disabled="!player.hasOverrides">
                                <i class="fas fa-trash-alt"></i>
                            </button>

                            <button class="px-2 py-0.5 rounded bg-success dark:bg-dark-success" @click="saveOverrides(player)" :title="t('roles.save')" :disabled="!player.hasOverrides">
                                <i class="fas fa-save"></i>
                            </button>
                        </td>
                    </tr>

                    <tr class="odd:bg-gray-200 dark:odd:bg-gray-500 border-t-4 border-gray-300" v-if="!readonly">
                        <td class="italic px-4 py-1.5" colspan="6">
                            <input class="block w-full text-sm bg-transparent py-1 px-2 border-0 border-b-2 border-red-600 dark:border-red-400" :class="{ '!border-lime-600 !dark:border-lime-400': addedLicenseValid }" v-model="adding" placeholder="license:2ced2cabd90f1208e..." />
                        </td>

                        <td class="italic px-4 py-1.5 w-24">
                            <div class="flex justify-end">
                                <div class="px-2 py-0.5 rounded bg-secondary dark:bg-dark-secondary" v-if="isAdding">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>

                                <button class="px-2 py-0.5 rounded bg-success dark:bg-dark-success" :title="t('roles.add')" :disabled="!addedLicenseValid" @click="addNewPlayer()" v-else>
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </template>

    </div>
</template>

<script>
import axios from 'axios';
import Layout from './../../Layouts/App';

export default {
    layout: Layout,
    props: {
        players: {
            type: Array,
            required: true
        },
        roles: {
            type: Object,
            required: true
        },
        readonly: {
            type: Boolean,
            required: true
        }
    },
    data() {
        const roles = Object.keys(this.roles).toSorted((a, b) => {
            return this.getRolePriority(a) - this.getRolePriority(b);
        });

        const players = this.players.map(player => this.createPlayer(player));

        return {
            isAdding: false,
            adding: "",

            roleList: roles,
            playerList: players
        };
    },
    computed: {
        addedLicenseValid() {
            if (!this.adding || !this.adding.match(/^license:[a-f0-9]{40}$/m)) return false;

            return !this.playerList.find(player => player.license === this.adding);
        }
    },
    methods: {
        createPlayer(player) {
            const overrides = {};

            for (const role in this.roles) {
                overrides[role] = !!player[role];
            }

            return {
                overrides: overrides,
                ...player,

                hasOverrides: false,
                isLoading: false
            };
        },
        canEditRole(role) {
            if (this.readonly) return false;

            if (!this.roles[role]) return true;

            return this.$page.auth.player.isRoot;
        },
        getRolePriority(role) {
            switch (role) {
                case "is_super_admin":
                    return 0;
                case "is_senior_staff":
                    return 1;
                case "is_staff":
                    return 2;
                case "is_trusted":
                    return 3;
                case "is_debugger":
                    return 4;
            }

            return 9;
        },
        sortBy(key) {
            this.playerList.sort((a, b) => {
                const aVal = a[key],
                    bVal = b[key];

                if (key === "name" || aVal === bVal) {
                    return a.name < b.name ? -1 : 1;
                }

                return aVal > bVal ? -1 : 1;
            });
        },
        toggleOverride(player, role) {
            if (player.isLoading || !this.canEditRole(role)) return;

            player.overrides[role] = !player.overrides[role];

            player.hasOverrides = !!Object.entries(player.overrides).find(([key, value]) => !!player[key] !== value);
        },
        resetOverrides(player) {
            if (player.isLoading || !player.hasOverrides) return;

            for (const role in player.overrides) {
                player.overrides[role] = !!player[role];
            }

            player.hasOverrides = false;
        },
        async saveOverrides(player) {
            if (player.isLoading || !player.hasOverrides) return;

            player.isLoading = true;

            const overrides = {};

            for (const role in player.overrides) {
                const original = !!player[role],
                    value = player.overrides[role];

                if (original !== value) {
                    overrides[role] = value;
                }
            }

            try {
                const response = await axios.post('/roles/' + player.license, overrides),
                    data = response.data;

                if (data.status) {
                    for (const role in player.overrides) {
                        player[role] = player.overrides[role];
                    }

                    player.hasOverrides = false;
                }
            } catch (error) {
                console.error(error);
            }

            player.isLoading = false;
        },
        async addNewPlayer() {
            if (this.isAdding || !this.addedLicenseValid) return;

            this.isAdding = true;

            try {
                const response = await axios.get('/roles/' + this.adding),
                    data = response.data;

                if (data.status) {
                    const player = data.data;

                    this.playerList.push(this.createPlayer(player));
                }
            } catch (error) {
                console.error(error);
            }

            this.adding = "";
            this.isAdding = false;
        }
    }
}
</script>
