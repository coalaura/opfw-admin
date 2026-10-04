<template>
    <div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-3 text-sm">
            <label>
                <span class="block mb-1 font-semibold">{{ t('stocks.interior') }}</span>
                <select class="w-full px-2 py-1 bg-gray-200 dark:bg-gray-600 border rounded" v-model="interior">
                    <option value="">{{ t('global.all') }}</option>
                    <option v-for="type in interiorTypes" :key="type" :value="type">{{ t(`stocks.type_${type}`) }}</option>
                </select>
            </label>

            <label>
                <span class="block mb-1 font-semibold">{{ t('stocks.address') }}</span>
                <input type="search" class="w-full px-2 py-1 bg-gray-200 dark:bg-gray-600 border rounded" v-model="address" :placeholder="t('global.search_placeholder')" />
            </label>

            <label>
                <span class="block mb-1 font-semibold">{{ t('stocks.renter') }}</span>
                <input type="search" class="w-full px-2 py-1 bg-gray-200 dark:bg-gray-600 border rounded" v-model="renter" :placeholder="t('global.search_placeholder')" />
            </label>

            <label>
                <span class="block mb-1 font-semibold">{{ t('stocks.status') }}</span>
                <select class="w-full px-2 py-1 bg-gray-200 dark:bg-gray-600 border rounded" v-model="status" :title="t('stocks.status_description')">
                    <option value="">{{ t('global.all') }}</option>
                    <option value="empty">{{ t('stocks.empty') }}</option>
                    <option value="paid">{{ t('stocks.paid') }}</option>
                    <option value="late">{{ t('stocks.late') }}</option>
                    <option value="evictable">{{ t('stocks.evictable') }}</option>
                </select>
            </label>
        </div>

        <button type="button" class="mb-3 text-sm text-blue-800 dark:text-blue-200" v-if="interior || address || renter || status" @click="clearFilters">
            {{ t('stocks.clear_filters') }}
        </button>

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

                <tr
                    v-for="(property, id) in filteredProperties"
                    :key="id"
                    class="border-t border-gray-500"
                    :class="{
                        'text-lime-800 dark:text-lime-200': propertyStatuses[id] === 'empty',
                        'text-yellow-800 dark:text-yellow-200': propertyStatuses[id] === 'late',
                        'text-red-800 dark:text-red-200': propertyStatuses[id] === 'evictable'
                    }"
                    :title="t(`stocks.${propertyStatuses[id]}`)"
                >
                    <th class="px-1 pl-3" v-if="hasActions">
                        <div class="flex gap-2">
                            <i class="fas fa-key cursor-pointer" @click="$emit('show', id)" v-if="canView"></i>
                            <i class="fas fa-tools cursor-pointer" @click="$emit('edit', id, property)" v-if="canEdit"></i>
                        </div>
                    </th>

                    <td class="px-1 py-1" :class="{ 'pl-3': !hasActions }">{{ t(`stocks.type_${property.type}`) }}</td>
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

                <tr v-if="Object.keys(filteredProperties).length === 0" class="text-center">
                    <td class="px-3 py-1 italic" :colspan="hasActions ? 6 : 5">{{ t('stocks.no_properties') }}</td>
                </tr>
            </table>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        company: {
            type: Object,
            required: true
        },
        canEdit: Boolean,
        canView: Boolean
    },
    data() {
        return {
            interior: '',
            address: '',
            renter: '',
            status: ''
        };
    },
    computed: {
        hasActions() {
            return this.canEdit || this.canView;
        },
        interiorTypes() {
            const types = new Set();

            for (const propertyId in this.company.properties) {
                types.add(String(this.company.properties[propertyId].type));
            }

            return Array.from(types).sort((first, second) => Number(first) - Number(second));
        },
        propertyStatuses() {
            const statuses = {};
            const now = Date.now() / 1000;
            const lateBefore = now - 7 * 24 * 60 * 60;
            const evictableBefore = now - 14 * 24 * 60 * 60;

            for (const propertyId in this.company.properties) {
                const property = this.company.properties[propertyId];

                if (!property.renter) {
                    statuses[propertyId] = 'empty';
                } else if (property.last_pay < evictableBefore) {
                    statuses[propertyId] = 'evictable';
                } else if (property.last_pay < lateBefore) {
                    statuses[propertyId] = 'late';
                } else {
                    statuses[propertyId] = 'paid';
                }
            }

            return statuses;
        },
        filteredProperties() {
            const address = this.address.trim().toLowerCase();
            const renter = this.renter.trim().toLowerCase();

            if (!this.interior && !address && !renter && !this.status) {
                return this.company.properties;
            }

            const properties = {};

            for (const propertyId in this.company.properties) {
                const property = this.company.properties[propertyId];

                if (this.interior && String(property.type) !== this.interior) {
                    continue;
                }

                if (address && !(property.address || '').toLowerCase().includes(address)) {
                    continue;
                }

                if (renter && !(property.renter || '').toLowerCase().includes(renter)) {
                    continue;
                }

                if (this.status && this.propertyStatuses[propertyId] !== this.status) {
                    continue;
                }

                properties[propertyId] = property;
            }

            return properties;
        }
    },
    methods: {
        clearFilters() {
            this.interior = '';
            this.address = '';
            this.renter = '';
            this.status = '';
        }
    }
};
</script>
