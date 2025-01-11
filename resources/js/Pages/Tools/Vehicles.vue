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
            <input class="px-4 py-2 w-96 mr-2 bg-gray-200 dark:bg-gray-600 border rounded" placeholder="Panto" v-model="search" @input="refresh" />
            <select class="px-4 py-2 w-96 mr-2 bg-gray-200 dark:bg-gray-600 border rounded" v-model="resource" @change="refresh">
                <option value="">{{ t('global.all') }}</option>
                <option v-for="resource in resources" :value="resource">{{ resourceLabel(resource) }}</option>
            </select>
        </div>

        <div class="mt-14" v-if="search">
            <h2 class="mb-2 dark:text-white">
                {{ t('vehicles.results') }} <sup class="text-muted dark:text-dark-muted text-sm">{{ results.length }}</sup>
            </h2>

            <div class="flex flex-wrap justify-between gap-3">
                <div class="flex rounded bg-red-600 bg-opacity-60 text-white shadow-sm min-w-box" v-if="!results.length">
                    <div class="flex flex-col justify-center p-5">
                        <h4 class="m-0 text-lg drop-shadow-sm leading-6">
                            {{ t('vehicles.no_result') }}
                        </h4>
                    </div>
                </div>

                <div v-for="result in results" class="flex rounded text-black dark:text-white shadow-sm border-2 relative p-5 max-w-520 max-w-full" :class="resourceColor(result.resource)" v-else>
                    <img class="h-32 w-48 object-contain flex-shrink-0" :src="`https://files.op-framework.com/files/models/${result.model}.png`" @error="modelImageFailed" loading="lazy" />

                    <div class="flex flex-col justify-center px-8">
                        <h4 class="m-0 text-lg drop-shadow-sm leading-6">
                            {{ result.label }}
                        </h4>

                        <p class="m-0 text-sm">
                            {{ result.model }}
                        </p>
                    </div>

                    <div class="absolute top-0.5 left-1 text-xs text-indigo-200 font-medium italic">{{ result.resource }}</div>
                    <div class="absolute top-0.5 right-1 text-xs text-indigo-200 font-semibold cursor-help" :title="t('vehicles.distance')">{{ result.distance }}</div>
                </div>
            </div>
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
            type: Object,
            required: true
        }
    },
    data() {
        return {
            resource: "",
            search: ""
        };
    },
    computed: {
        resources() {
            const resources = [],
                list = Object.keys(this.vehicles);

            for (let resource of list) {
                resource = resource.replace(/_\d+$/m, "");

                if (!resources.includes(resource)) {
                    resources.push(resource);
                }
            }

            return resources;
        },
        results() {
            const search = this.search.trim().toLowerCase(),
                resource = this.resource;

            if (!search) return [];

            const results = [];

            for (const res in this.vehicles) {
                if (resource && res !== resource) {
                    continue;
                }

                const vehicles = this.vehicles[res];

                for (const vehicle of vehicles) {
                    const label = vehicle.label.toLowerCase(),
                        model = vehicle.model;

                    if (!label.includes(search) && !model.includes(search)) {
                        continue;
                    }

                    const modelDistance = levenshtein.get(search, model),
                        labelDistance = levenshtein.get(search, label);

                    results.push({
                        ...vehicle,
                        resource: res,
                        distance: Math.min(modelDistance, labelDistance)
                    });
                }
            }

            results.sort((a, b) => {
                if (a.distance === b.distance) {
                    return a.label < b.label ? -1 : 1;
                }

                return a.distance - b.distance;
            });

            return results;
        }
    },
    methods: {
        resourceLabel(resource) {
            const known = {
                "native": "Basegame",
                "vehicles/aircraft": "Aircraft",
                "vehicles/civilian": "EDM",
                "vehicles/casino": "Casino",
                "vehicles/ems": "EMS & Fire",
                "vehicles/police": "Police",
                "vehicles/fib": "Police",
                "vehicles/heli": "Helicopters",
                "vehicles/random": "1of1's & Random",
                "vehicles/service": "Service"
            };

            const alias = known[resource];

            return alias ? `${resource} (${alias})` : resource;
        },
        resourceColor(resource) {
            switch (resource) {
                case "native":
                    return "bg-lime-300 dark:bg-lime-900 border-lime-500";
                case "vehicles/aircraft":
                case "vehicles/heli":
                    return "bg-teal-300 dark:bg-teal-900 border-teal-500";
                case "vehicles/police":
                case "vehicles/fib":
                    return "bg-blue-300 dark:bg-blue-900 border-blue-500";
                case "vehicles/ems":
                    return "bg-rose-300 dark:bg-rose-900 border-rose-500";
                case "vehicles/casino":
                    return "bg-purple-300 dark:bg-purple-900 border-purple-500";
                case "vehicles/random":
                    return "bg-yellow-300 dark:bg-yellow-900 border-yellow-500";
            }

            return "bg-gray-300 dark:bg-gray-850 border-gray-500";
        },
        modelImageFailed(event) {
            event.target.src = "/images/no_image_ped.webp";
        }
    }
}
</script>
