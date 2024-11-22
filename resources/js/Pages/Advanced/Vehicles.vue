<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t("vehicles.title") }}
            </h1>
            <p>
                {{ t("vehicles.description") }}
            </p>
        </portal>

        <div class="flex -mt-6 max-w-screen-2xl mobile:flex-wrap">
            <input class="px-4 py-2 w-96 mr-2 bg-gray-200 dark:bg-gray-600 border rounded" id="search" name="search" placeholder="Panto" v-model="search" @input="refresh" />
        </div>

        <div class="mt-14" v-if="search">
            <h3 class="mb-2 dark:text-white">
                {{ t('vehicles.result') }}
            </h3>
            <table class="table-fixed max-w-screen-lg text-left">
                <template v-if="!result">
                    <div class="flex rounded bg-red-600 bg-opacity-60 text-white shadow-sm min-w-box">
                        <div class="flex flex-col justify-center p-5">
                            <h4 class="m-0 text-lg drop-shadow-sm leading-6">
                                {{ t('vehicles.no_result') }}
                            </h4>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <div class="flex rounded bg-indigo-600 dark:bg-indigo-800 text-white shadow-sm min-w-box border-2 border-indigo-700 relative">
                        <div class="block">
                            <img class="h-48" :src="`https://files.op-framework.com/files/models/${result.model}.png`" @error="modelImageFailed" />
                        </div>

                        <div class="flex flex-col justify-center px-8">
                            <h4 class="m-0 text-lg drop-shadow-sm leading-6">
                                {{ result.label }}
                            </h4>

                            <p class="m-0 text-sm">
                                {{ result.class }} - {{ result.model }}
                            </p>

                            <p class="m-0 text-sm" v-if="result.price">
                                {{ numberFormat(result.price, false, true) }}
                            </p>
                        </div>

                        <div class="absolute top-0.5 right-1 text-sm text-indigo-200 font-semibold cursor-help" :title="t('vehicles.distance')">{{ result.distance }}</div>
                    </div>
                </template>
            </table>
        </div>

    </div>
</template>

<script>
import Layout from '../../Layouts/App';

import levenshtein from 'fast-levenshtein';

export default {
    layout: Layout,
    props: {
        vehicles: {
            type: Array,
            required: true
        }
    },
    data() {
        return {
            result: false,

            search: ""
        };
    },
    methods: {
        modelImageFailed(event) {
            event.target.src = "/images/no_image.png";
        },
        refresh: async function () {
            this.search = this.search.trim();

            if (!this.search) return;

            const search = this.search.toLowerCase();

            const vehicles = this.vehicles.map(vehicle => {
                const modelDistance = levenshtein.get(search, vehicle.model);
                const labelDistance = levenshtein.get(search, vehicle.label.toLowerCase());

                return {
                    ...vehicle,
                    distance: Math.min(modelDistance, labelDistance)
                };
            });

            vehicles.sort((a, b) => a.distance - b.distance);

            this.result = vehicles.shift();
        }
    }
}
</script>
