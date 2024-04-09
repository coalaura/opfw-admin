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
                <img :src="company.logo" class="w-32 h-32 rounded-tl-lg" @error="company.logo = '/images/realty_image_broken.png'" />

                <h2>
                    {{ company.name }}
                </h2>

                <span class="bg-rose-600 text-white py-0.5 px-2 text-xs rounded-sm shadow-sm absolute top-1 right-1.5" v-if="company.bankrupt">{{ t("stocks.bankrupt") }}</span>
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
                    </table>
                </div>
            </div>

            <div class="px-8 py-3">
                <h3 class="mb-1">{{ t('stocks.properties') }}</h3>

                <div class="max-h-48 overflow-y-auto">
                    <table class="w-full bg-gray-300 dark:bg-gray-600 text-sm">
                        <tr class="border-b-2 border-gray-500 text-left">
                            <th class="px-1 py-1 pl-3">{{ t('stocks.address') }}</th>
                            <th class="px-2 py-1">{{ t('stocks.renter') }}</th>
                            <th class="px-2 py-1">{{ t('stocks.rent') }}</th>
                            <th class="px-2 py-1 pr-3">{{ t('stocks.last_pay') }}</th>
                        </tr>

                        <tr v-for="(property, id) in company.properties" :key="id" class="border-t border-gray-500">
                            <td class="px-1 py-1 pl-3">{{ property.address }}</td>

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
                    </table>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import Layout from './../../Layouts/App';
import VSection from './../../Components/Section';

export default {
    layout: Layout,
    components: {
        VSection,
    },
    props: {
        companies: {
            type: Array,
            required: true
        }
    }
}
</script>
