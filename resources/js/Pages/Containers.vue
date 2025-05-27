<template>
    <div>
        <portal to="title">
            <h1 class="dark:text-white">
                {{ t('containers.title') }}
            </h1>
            <p>
                {{ t('containers.description') }}
            </p>
        </portal>

        <div class="rounded-lg shadow bg-secondary dark:bg-dark-secondary max-w-6xl px-8 py-6">
            <h2 class="mb-3">{{ t('containers.rented_containers') }}</h2>

            <table class="w-full bg-gray-300 dark:bg-gray-600">
                <tr class="border-b-2 border-gray-500 text-left">
                    <th class="px-1 py-1 pl-3">{{ t('containers.container_id') }}</th>
                    <th class="px-2 py-1">{{ t('containers.renter') }}</th>
                    <th class="px-2 py-1">{{ t('containers.paid_until') }}</th>
                    <th class="px-2 py-1">{{ t('containers.items') }}</th>
                    <th class="px-2 py-1 pr-3">&nbsp;</th>
                </tr>

                <tr v-for="(container, id) in containers" :key="id" class="border-t border-gray-500">
                    <td class="px-1 py-1 pl-3">
                        <a :href="'/inventory/container-' + container.container_id" target="_blank" class="text-indigo-700 dark:text-indigo-200 text-sm mr-1" :title="t('inventories.show_inv')">
                            <i class="fas fa-dolly-flatbed"></i>
                        </a>
                        {{ t('containers.container') }} #{{ container.container_id }}
                    </td>
                    <td class="px-2 py-1">
                        <a :href="'/players/' + container.license_identifier + '/characters/' + container.character_id" target="_blank" class="text-indigo-700 dark:text-indigo-200">
                            {{ container.first_name }} {{ container.last_name }}
                            #{{ container.character_id }}
                        </a>
                    </td>
                    <td class="px-2 py-1" :title="dayjs.utc(container.paid_until * 1000).fromNow()">{{ container.paid_until * 1000 | formatTime(false) }}</td>
                    <td class="px-2 py-1">x{{ containerItems(container.container_id) }}</td>
                    <td class="px-2 py-1 pr-3">
                        <a href="#" @click.prevent="copyLocation(container.container_id)" class="text-indigo-700 dark:text-indigo-200" :title="t('containers.copy_loc')">
                            <i class="fas fa-map-marked-alt"></i>
                        </a>
                        <a href="#" @click.prevent="viewContainer(container.container_id)" class="text-indigo-700 dark:text-indigo-200 ml-2" :title="t('containers.view_access')" v-if="$page.auth.player.isSeniorStaff">
                            <i class="fas fa-key"></i>
                        </a>
                    </td>
                </tr>

                <tr v-if="containers.length === 0" class="text-center">
                    <td class="px-3 py-1 italic" colspan="5">{{ t('containers.empty') }}</td>
                </tr>
            </table>
        </div>

        <modal :show="viewingContainer">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('containers.container') }} #{{ viewingContainer }}
                </h1>
            </template>

            <template #default>
                <div class="flex justify-center p-4" v-if="isLoading">
                    <i class="fas fa-spinner animate-spin"></i>
                </div>
                <div class="flex justify-center p-4" v-else-if="!containerAccess">
                    {{ t('containers.failed_load') }}
                </div>
                <div v-else>
                    <table class="whitespace-nowrap w-full">
                        <tr class="sticky top-0 bg-gray-300 dark:bg-gray-700 no-alpha">
                            <th class="font-semibold px-2 py-0.5 text-left">{{ t('containers.player') }}</th>
                            <th class="font-semibold px-2 py-0.5 text-left">{{ t('containers.character_id') }}</th>
                            <th class="font-semibold px-2 py-0.5 text-left">{{ t('containers.name') }}</th>
                            <th class="font-semibold px-2 py-0.5 text-left">{{ t('containers.access') }}</th>
                        </tr>

                        <tr class="border-t border-gray-500" v-for="(access, index) in containerAccess.access" :key="index" v-accent="containerAccess.owner === access.character_id ? 1 : 0">
                            <td class="px-2 py-0.5">
                                <div class="truncate max-w-56">
                                    <a :href="'/players/' + access.license_identifier" class="text-blue-800 dark:text-blue-200">
                                        {{ access.player_name }}
                                    </a>
                                </div>
                            </td>
                            <td class="px-2 py-0.5">
                                <a :href="'/players/' + access.license_identifier + '/characters/' + access.character_id" class="text-blue-800 dark:text-blue-200">
                                    #{{ access.character_id }}
                                </a>
                            </td>
                            <td class="px-2 py-0.5">
                                {{ access.full_name }}
                            </td>
                            <td class="px-2 py-0.5 italic">
                                <span v-if="containerAccess.owner === access.character_id">{{ t('containers.owner') }}</span>
                                <span v-else>{{ t('containers.access') }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="viewingContainer = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

    </div>
</template>

<script>
import Layout from './../Layouts/App.vue';
import VSection from './../Components/Section.vue';
import Modal from './../Components/Modal.vue';

import locations from './../data/containers.json';

export default {
    layout: Layout,
    components: {
        VSection,
        Modal,
    },
    props: {
        containers: {
            type: Array,
            required: true
        },
        items: {
            type: Array,
            required: true
        }
    },
    data() {
        return {
            isLoading: false,
            viewingContainer: false,
            containerAccess: false
        };
    },
    methods: {
        containerItems(id) {
            const inventoryName = `container-${id}`;

            return this.items.find(count => count.inventory_name === inventoryName)?.count || 0;
        },
        copyLocation(id) {
            const location = locations[id - 1]; // -1 cause lua has 1 based indexing 🤓

            this.copyToClipboard(`/tp_coords ${location[0]} ${location[1]} ${location[2]}`);
        },
        async viewContainer(id) {
            if (this.isLoading) return;

            this.isLoading = true;
            this.viewingContainer = id;
            this.containerAccess = false;

            const data = await _get(`/containers/${id}/access`);

            if (data?.status) {
                this.containerAccess = data.data;
            }

            this.isLoading = false;
        }
    }
}
</script>
