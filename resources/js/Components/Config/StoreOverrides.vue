<template>
    <div class="relative">
        <div class="absolute top-0 right-0 left-0 bottom-0 backdrop-blur-md flex justify-center items-center" v-if="isLoading">
            <i class="fas fa-spinner animate-spin text-xl"></i>
        </div>

        <div class="flex justify-between items-center mb-3">
            <div class="flex gap-3">
                <button class="font-semibold cursor-pointer text-sm" @click="addBlankOverride()">
                    <i class="fas fa-plus mr-1"></i>
                    {{ t('tools.config.add_override') }}
                </button>
                <button class="font-semibold cursor-pointer text-sm ml-2" @click="isReadingConfig = true">
                    <i class="fab fa-readme mr-1"></i>
                    {{ t('tools.config.read_config') }}
                </button>
                <button class="font-semibold cursor-pointer text-sm ml-2" @click="exportConfig()">
                    <i class="fas fa-cloud-download-alt mr-1"></i>
                    {{ t('tools.config.export_config') }}
                </button>
            </div>

            <div class="flex gap-3">
                <button class="font-semibold cursor-pointer text-sm text-red-600 dark:text-red-400" @click="overrides = []">
                    <i class="fas fa-trash-alt mr-1"></i>
                    {{ t('tools.config.clear_overrides') }}
                </button>
            </div>
        </div>

        <div class="flex flex-col gap-3">
            <table class="w-full bg-gray-300 dark:bg-gray-600 text-sm" v-for="(override, index) in overrides" :key="index">
                <tr class="border-b-2 border-gray-500 text-left">
                    <th class="px-2 py-1">
                        <button class="font-semibold cursor-pointer text-sm" @click="overrides.splice(index, 1)" :title="t('tools.config.remove_override')">
                            <i class="fas fa-minus"></i>
                        </button>
                    </th>
                    <th class="px-2 py-1">{{ t('tools.config.store_name') }}</th>
                    <th class="px-2 py-1">
                        <button class="font-semibold cursor-pointer text-sm mr-1" @click="addItem(override)">
                            <i class="fas fa-plus"></i>
                        </button>

                        {{ t('tools.config.items') }}
                    </th>
                </tr>

                <tr class="border-t border-gray-500">
                    <td class="px-2 py-1" colspan="2">
                        <input class="block w-full text-sm bg-transparent py-1 px-2 border-0 border-b-2 border-red-600 dark:border-red-400" :class="{ '!border-lime-600 !dark:border-lime-400': override.store }" v-model="override.store" placeholder="grocery_store" />
                    </td>

                    <td class="px-2 py-1">
                        <table class="w-full bg-gray-300 dark:bg-gray-600 text-sm border-collapse">
                            <tr v-for="(item, indexI) in override.items" :key="indexI" class="border border-gray-500">
                                <td class="px-2 py-1">
                                    <button class="font-semibold cursor-pointer text-sm" @click="override.items.splice(indexI, 1)" :title="t('tools.config.remove_override')">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </td>
                                <td class="px-2 py-1">
                                    <input class="block w-full text-sm bg-transparent py-1 px-2 border-0 border-b-2 border-red-600 dark:border-red-400" :class="{ '!border-lime-600 !dark:border-lime-400': item.valid }" v-model="item.name" placeholder="pepsi" @input="updateItem(item)" />
                                </td>
                                <td class="px-2 py-1">
                                    <div class="flex items-center py-1 px-2 border-b-2 border-red-600 dark:border-red-400 gap-1.5" :class="{ '!border-lime-600 !dark:border-lime-400': item.price }">
                                        <i class="fas fa-dollar-sign"></i>
                                        <input class="block w-full text-sm bg-transparent border-0 p-0" v-model="item.price" type="number" placeholder="12" @input="updateItem(item)" />
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <modal :show="isReadingConfig">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('tools.config.read_config') }}
                </h1>
            </template>

            <template #default>
                <label class="block mb-1 font-semibold" for="cluster">{{ t('tools.config.read_from_cluster') }}</label>
                <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" v-model="cluster" id="cluster" type="number" min="1" max="100" placeholder="3" />

                <label class="block mb-1 font-semibold mt-5 pt-5 border-t-2 border-dashed border-gray-500" for="cluster">{{ t('tools.config.or_read_text') }}</label>
                <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" v-model="reading" id="reading" placeholder="gun_store=pistol_ammo:42,sub_ammo:69..." />
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500" @click="isReadingConfig = false">
                    {{ t('global.close') }}
                </button>
                <button type="button" class="px-5 py-2 rounded bg-lime-200 hover:bg-lime-300 dark:bg-lime-600 dark:hover:bg-lime-500" @click="readConfig()" v-if="reading || cluster">
                    {{ t('tools.config.import') }}
                </button>
            </template>
        </modal>
    </div>
</template>

<script>
import Modal from '../Modal';
import ServerConfig from './ServerConfig.js';

export default {
    name: 'StoreOverrides',
    components: {
        Modal
    },
    props: {
        items: {
            type: Object | Array,
            required: true
        }
    },
    data() {
        return {
            isLoading: false,

            isReadingConfig: false,

            cluster: "",
            reading: "",

            overrides: []
        };
    },
    methods: {
        addBlankOverride() {
            this.overrides.push({
                store: "",
                items: [
                    {
                        name: "",
                        price: 0,
                        valid: false
                    }
                ]
            });
        },
        addItem(override) {
            override.items.push({
                name: "",
                price: 0,
                valid: false
            });
        },
        updateItem(item) {
            item.valid = !!this.items[item.name];
        },
        async readConfig() {
            if (this.isLoading) return;

            this.isReadingConfig = false;

            const cluster = parseInt(this.cluster);

            let reading = this.reading.trim();

            this.cluster = "";
            this.reading = "";

            if (cluster) {
                this.isLoading = true;

                try {
                    const response = await axios.get(`/api/config/${cluster}/store_overrides`),
                        data = response.data;

                    if (!data || !data.status) {
                        throw new Error("Config not found");
                    }

                    reading = data.data;
                } catch (e) {
                    console.error(e);
                }

                this.isLoading = false;
            }

            try {
                const cfg = new ServerConfig(reading),
                    overrides = cfg.mapMap();

                for (const [storeName, overrideItems] of Object.entries(overrides)) {
                    const newOverride = {
                        store: storeName,
                        items: []
                    };

                    for (const [itemName, price] of Object.entries(overrideItems)) {
                        newOverride.items.push({
                            name: itemName,
                            price: parseInt(price),
                            valid: !!this.items[itemName]
                        });
                    }

                    if (newOverride.items.length) {
                        this.overrides.push(newOverride);
                    }
                }
            } catch(e) {
                console.error(e);
            }
        },
        exportConfig() {
            const entries = [];

            for (const override of this.overrides) {
                const { store, items } = override;

                if (!store || !items.length) {
                    continue;
                }

                const itemsStr = items.map(item => `${item.name}:${item.price}`).join(",");

                entries.push(`${store}=${itemsStr}`);
            }

            const config = entries.join(";");

            this.copyToClipboard(`store_overrides = "${config}"`);
        }
    }
}
</script>
