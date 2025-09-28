<template>
    <div>
        <portal to="title">
            <h1 class="dark:text-white">
                {{ t('stocks.title') }}
            </h1>
            <p>
                {{ t('stocks.description') }}
            </p>
        </portal>

        <div class="mb-10 rounded-lg shadow bg-secondary dark:bg-dark-secondary max-w-6xl" v-for="(company, id) in companies" :key="id">
            <header class="flex items-center gap-6 border-b-2 border-gray-500 bg-gray-300 dark:bg-gray-600 relative">
                <img :src="company.logo" class="w-32 h-32 rounded-tl-lg" v-handle-error="'/images/realty_image_broken.png'" />

                <h2>
                    {{ company.name }}
                </h2>

                <span class="bg-rose-600 text-white py-0.5 px-1.5 text-xs rounded-sm shadow-sm absolute top-1.5 right-1.5" v-if="company.bankrupt">{{ t("stocks.bankrupt") }}</span>
            </header>

            <div class="px-8 py-3 italic border-b-2 border-gray-500 bg-gray-300 dark:bg-gray-700 text-sm">{{ company.description }}</div>

            <div class="px-8 py-3 border-b-2 border-gray-500">
                <h3 class="mb-1">{{ t('stocks.employees') }}</h3>

                <div class="max-h-48 overflow-y-auto">
                    <table class="w-full bg-gray-300 dark:bg-gray-600 text-sm">
                        <tr class="border-b-2 border-gray-500 text-left">
                            <th class="px-1 py-1 pl-3">{{ t('stocks.permissions') }}</th>
                            <th class="px-2 py-1">{{ t('stocks.employee') }}</th>
                            <th class="px-2 py-1">{{ t('stocks.position') }}</th>
                            <th class="px-2 py-1 pr-3">{{ t('stocks.salary') }}</th>
                        </tr>

                        <tr v-for="(employee, id) in company.employees" :key="id" class="border-t border-gray-500">
                            <td class="px-1 py-1 pl-3">{{ t('stocks.permission_' + employee.permissions) }}</td>
                            <td class="px-2 py-1">{{ employee.name }}</td>
                            <td class="px-2 py-1 italic">{{ employee.position }}</td>
                            <td class="px-2 py-1 pr-3">{{ numberFormat(employee.salary, 0, true) }}</td>
                        </tr>

                        <tr v-if="Object.keys(company.employees).length === 0" class="text-center">
                            <td class="px-3 py-1 italic" colspan="5">{{ t('stocks.empty') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="px-8 py-3">
                <h3 class="mb-1">
                    {{ t('stocks.properties') }}

                    <sup v-if="company.filled_properties === 0" class="text-sm">0%</sup>
                    <sup v-else-if="company.empty_properties === 0" class="text-sm">100%</sup>
                    <sup v-else-if="company.empty_properties > 0 && company.filled_properties > 0" class="text-sm">{{ Math.round(company.filled_properties / (company.filled_properties + company.empty_properties) * 100) }}%</sup>
                </h3>

                <div class="max-h-48 overflow-y-auto">
                    <table class="w-full bg-gray-300 dark:bg-gray-600 text-sm">
                        <tr class="border-b-2 border-gray-500 text-left">
                            <th class="px-1 pl-3" v-if="hasActions">&nbsp;</th>

                            <th class="px-1 py-1" :class="{ 'pl-3': !hasActions }">{{ t('stocks.interior') }}</th>
                            <th class="px-1 py-1">{{ t('stocks.address') }}</th>
                            <th class="px-2 py-1">{{ t('stocks.renter') }}</th>
                            <th class="px-2 py-1">{{ t('stocks.rent') }}</th>
                            <th class="px-2 py-1 pr-3">{{ t('stocks.last_pay') }}</th>
                        </tr>

                        <tr v-for="(property, id) in company.properties" :key="id" class="border-t border-gray-500" :class="{ 'text-lime-800 dark:text-lime-200': !property.renter }">
                            <th class="px-1 pl-3" v-if="hasActions">
                                <div class="flex gap-2">
                                    <i class="fas fa-key cursor-pointer" @click="showProperty(id)" v-if="$page.auth.player.isSeniorStaff"></i>
                                    <i class="fas fa-tools cursor-pointer" @click="editProperty(id, property)" v-if="canEdit"></i>
                                </div>
                            </th>

                            <td class="px-1 py-1" :class="{ 'pl-3': !hasActions }">{{ t('stocks.type_' + property.type) }}</td>
                            <td class="px-1 py-1">{{ property.address }}</td>

                            <template v-if="property.renter">
                                <td class="px-2 py-1">{{ property.renter }}</td>
                                <td class="px-2 py-1">{{ numberFormat(property.income, 0, true) }}</td>
                                <td class="px-2 py-1 pr-3">{{ property.last_pay * 1000 | formatTime(false) }}</td>
                            </template>
                            <template v-else>
                                <td class="px-2 py-1 italic">{{ t('stocks.empty') }}</td>
                                <td class="px-2 py-1 italic">{{ t('stocks.empty') }}</td>
                                <td class="px-2 py-1 pr-3 italic">{{ t('stocks.empty') }}</td>
                            </template>
                        </tr>

                        <tr v-if="Object.keys(company.properties).length === 0" class="text-center">
                            <td class="px-3 py-1 italic" :colspan="hasActions ? 6 : 5">{{ t('stocks.empty') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <modal :show="isShowingProperty">
            <template #header>
                <h1 class="dark:text-white" v-if="propertyData">
                    {{ propertyData.address }} #{{ propertyData.id }}
                </h1>
                <h1 class="dark:text-white" v-else>
                    {{ t('players.properties.property') }}
                </h1>
            </template>

            <template #default>
                <div class="flex justify-center p-4" v-if="isLoadingProperty">
                    <i class="fas fa-spinner animate-spin"></i>
                </div>
                <div class="flex justify-center p-4" v-else-if="!propertyData">
                    {{ t('players.properties.failed_load') }}
                </div>
                <table class="whitespace-nowrap w-full" v-else>
                    <tr class="sticky top-0 bg-gray-300 dark:bg-gray-700 no-alpha">
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('players.properties.player') }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('players.properties.character_id') }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('players.properties.name') }}</th>
                        <th class="font-semibold px-2 py-0.5 text-left">{{ t('players.properties.access') }}</th>
                    </tr>

                    <tr class="border-t border-gray-500" v-for="(access, index) in propertyData.access" :key="index">
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
                            <span v-if="propertyData.renter === access.character_id">{{ t('players.properties.owner') }}</span>
                            <span v-else>{{ t('players.properties.level', access.level) }}</span>
                        </td>
                    </tr>
                </table>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="isShowingProperty = false">
                    {{ t('global.close') }}
                </button>
            </template>
        </modal>

        <modal :show.sync="isEditingProperty">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('stocks.property_edit') }}
                </h1>
            </template>

            <template #default>
                <div class="w-full mb-3 flex">
                    <div class="w-1/3 px-3">
                        <label class="block mb-2 font-semibold" for="renter">
                            {{ t('stocks.renter_cid') }}
                        </label>
                        <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="renter" name="renter" placeholder="12345" min="1" v-model="editingProperty.renter" />
                    </div>

                    <div class="w-1/3 px-3">
                        <label class="block mb-2 font-semibold" for="income">
                            {{ t('stocks.rent') }}
                        </label>
                        <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="income" name="income" placeholder="2000" min="10" max="50000" v-model="editingProperty.income" />
                    </div>

                    <div class="w-1/3 px-3">
                        <label class="block mb-2 font-semibold" for="last_pay">
                            {{ t('stocks.last_pay') }}
                        </label>
                        <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" type="date" id="last_pay" name="last_pay" :min="editingPropertyMinDate" :max="maxDate" v-model="editingProperty.last_pay" />
                    </div>
                </div>

                <div class="border-t border-gray-500"></div>

                <div class="w-full mt-3">
                    <h2 class="font-semibold border-b border-gray-500 border-dashed flex justify-between items-center mb-2 text-lg">
                        {{ t('stocks.shared_keys') }}
                    </h2>

                    <div class="text-sm bg-gray-200 dark:bg-gray-700 flex" v-for="(key, index) in editingProperty.keys" :key="index">
                        <input class="px-1 py-0.5 block bg-gray-200 dark:bg-gray-800 text-sm w-32 border-r-0 focus:outline-none" placeholder="12345" min="1" v-model="key.cid" @change="updateCharacterName(key)" />

                        <input class="px-1 py-0.5 block bg-gray-200 dark:bg-gray-800 text-sm w-32 border-r-0 focus:outline-none" placeholder="John Doe" :value="key.name" readonly />

                        <select v-model="key.level" class="px-1 py-0.5 block bg-gray-200 dark:bg-gray-800 text-sm w-full">
                            <option value="1">{{ t('stocks.level_1') }}</option>
                            <option value="2">{{ t('stocks.level_2') }}</option>
                            <option value="3">{{ t('stocks.level_3') }}</option>
                        </select>

                        <button class="p-0.5 w-8 flex items-center justify-center bg-gray-200 dark:bg-gray-800 border border-input border-l-0" v-if="!key.empty || key.cid">
                            <i class="fas fa-plus cursor-pointer" @click="addSharedKey()" v-if="key.empty"></i>
                            <i class="fas fa-minus cursor-pointer" @click="editingProperty.keys.splice(index, 1)" v-else></i>
                        </button>
                    </div>

                    <div class="text-sm bg-gray-200 dark:bg-gray-700" v-if="editingProperty.keys.length === 0">
                        {{ t('stocks.no_shared_keys') }}
                    </div>
                </div>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="isEditingProperty = false">
                    {{ t('global.cancel') }}
                </button>
                <button type="submit" class="px-5 py-2 text-white bg-indigo-600 rounded dark:bg-indigo-400" @click="updateProperty">
                    <span v-if="!isLoading">
                        <i class="mr-1 fa fa-pencil-alt"></i>
                        {{ t('stocks.update_property') }}
                    </span>
                    <span v-else>
                        <i class="fas fa-cog animate-spin"></i>
                        {{ t('global.loading') }}
                    </span>
                </button>
            </template>
        </modal>

    </div>
</template>

<script>
import Layout from './../../Layouts/App.vue';
import VSection from './../../Components/Section.vue';
import Modal from './../../Components/Modal.vue';

export default {
    layout: Layout,
    components: {
        VSection,
        Modal
    },
    props: {
        companies: {
            type: Object,
            required: true
        }
    },
    computed: {
        canEdit() {
            return this.perm.check(this.perm.PERM_REALTY_EDIT);
        },

        maxDate() {
            return dayjs().add(1, 'year').format('YYYY-MM-DD');
        },

        hasActions() {
            return this.canEdit || this.$page.auth.player.isSeniorStaff;
        }
    },
    data() {
        return {
            isLoading: false,

            isEditingProperty: false,
            editingPropertyId: false,
            editingProperty: false,
            editingPropertyMinDate: false,

            isShowingProperty: false,
            isLoadingProperty: false,
            propertyData: false
        };
    },
    methods: {
        editProperty(propertyId, property) {
            this.isEditingProperty = true;

            const lastPay = dayjs(property.last_pay * 1000).format('YYYY-MM-DD');

            this.editingPropertyId = propertyId;
            this.editingProperty = {
                renter: property.renter_cid,
                income: property.income,
                last_pay: lastPay,
                keys: property.keys || []
            };
            this.editingPropertyMinDate = lastPay;

            this.addSharedKey();
        },
        async showProperty(propertyId) {
            if (this.isShowingProperty) return;

            this.isShowingProperty = true;
            this.isLoadingProperty = true;
            this.propertyData = false;

            try {
                const data = await _get(`/stocks/property/${propertyId}`);

                if (data?.status) {
                    this.propertyData = data.data;
                }
            } catch (e) {
            }

            this.isLoadingProperty = false;
        },
        addSharedKey() {
            if (!this.editingProperty) return;

            this.editingProperty.keys = this.editingProperty.keys.filter(key => key.cid).map(key => {
                key.empty = false;

                return key;
            });

            this.editingProperty.keys.push({
                cid: '',
                name: '-',
                level: 1,

                empty: true
            });
        },
        async updateCharacterName(key) {
            key.name = '-';

            if (!key.cid) return;

            try {
                const data = await _get(`/api/character/${key.cid}`);

                if (data?.status) {
                    key.name = `${data.data.first_name} ${data.data.last_name}`;
                }
            } catch (e) {
            }
        },
        async updateProperty() {
            if (this.isLoading) return;

            this.isLoading = true;

            this.editingProperty.keys = this.editingProperty.keys.filter(key => key.cid);

            await this.$inertia.post(`/stocks/property/${this.editingPropertyId}`, this.editingProperty);

            this.isLoading = false;
            this.isEditingProperty = false;
        }
    }
}
</script>
