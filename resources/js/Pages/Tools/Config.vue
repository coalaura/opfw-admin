<template>
    <div>
        <portal to="title">
            <h1 class="dark:text-white">
                {{ t('tools.config.title') }}
            </h1>
            <p>
                {{ t('tools.config.description') }}
            </p>
        </portal>

        <div class="rounded-lg shadow bg-secondary dark:bg-dark-secondary max-w-6xl px-8 py-6 mt-14">
            <div class="flex items-center gap-3 mb-4 pb-4 border-b-2 border-dashed border-gray-400">
                <label for="type" class="font-semibold">{{ t('tools.config.type') }}:</label>
                <select class="block w-full px-3 py-1 bg-gray-200 border rounded dark:bg-gray-600" id="type" v-model="type">
                    <option value="job_override">
                        {{ t('tools.config.job_override') }}
                    </option>
                    <option value="store_override">
                        {{ t('tools.config.store_override') }}
                    </option>
                </select>
            </div>

            <JobOverrides :jobs="jobs" v-if="type === 'JobOverride'" />
            <StoreOverrides :items="items" v-if="type === 'StoreOverride'" />
        </div>

    </div>
</template>

<script>
import Layout from './../../Layouts/App.vue';
import JobOverrides from './../../Components/Config/JobOverrides.vue';
import StoreOverrides from './../../Components/Config/StoreOverrides.vue';

export default {
    layout: Layout,
    components: {
        JobOverrides,
        StoreOverrides
    },
    props: {
        jobs: {
            type: Object | Array,
            required: true
        },
        items: {
            type: Object | Array,
            required: true
        }
    },
    data() {
        return {
            type: "JobOverride"
        };
    },
}
</script>
