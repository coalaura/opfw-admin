<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t("statistics.title") }}
            </h1>
            <p>
                {{ t("statistics.description") }}
            </p>
        </portal>

        <template>
            <div class="bg-gray-100 p-6 rounded shadow-lg max-w-full w-map -mt-7 dark:bg-gray-700">
            </div>
        </template>

    </div>
</template>

<script>
import Layout from './../../Layouts/App';

export default {
    layout: Layout,
    props: {
    },
}
</script>
