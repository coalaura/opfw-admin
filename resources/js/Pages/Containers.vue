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
                    <td class="px-2 py-1" :title="$moment.utc(container.paid_until * 1000).fromNow()">{{ container.paid_until * 1000 | formatTime(false) }}</td>
                    <td class="px-2 py-1">x{{ containerItems(container.container_id) }}</td>
                    <td class="px-2 py-1 pr-3">
                        <a href="#" @click.prevent="copyLocation(container.container_id)" class="text-indigo-700 dark:text-indigo-200" :title="t('containers.copy_loc')">
                            <i class="fas fa-map-marked-alt"></i>
                        </a>
                    </td>
                </tr>

                <tr v-if="containers.length === 0" class="text-center">
                    <td class="px-3 py-1 italic" colspan="5">{{ t('containers.empty') }}</td>
                </tr>
            </table>
        </div>

    </div>
</template>

<script>
import Layout from './../Layouts/App';
import VSection from './../Components/Section';

import locations from './../data/containers.json';

export default {
    layout: Layout,
    components: {
        VSection
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
    methods: {
        containerItems(id) {
            const inventoryName = `container-${id}`;

            return this.items.find(count => count.inventory_name === inventoryName)?.count || 0;
        },
        copyLocation(id) {
            const location = locations[id];

            this.copyToClipboard(`/tp_coords ${location[0]} ${location[1]} ${location[2]}`);
        }
    }
}
</script>
