<template>
    <div class="flex flex-col items-center justify-center h-screen p-5 relative">
        <div class="fixed -inset-3 bg-cover bg-center blur-sm" :style="{ background: 'url(images/background.png)' }"></div>

        <div class="max-w-3xl p-16 bg-white shadow backdrop-blur-xl bg-opacity-60">

            <!-- Flash message -->
            <div>
                <flash-message />
            </div>

            <!-- Content -->
            <div class="mb-8 text-center">
                <span class="mb-6 text-6xl text-gray-800 fab fa-discord"></span>
                <h1 class="mb-6 text-3xl font-semibold">
                    {{ t('login.title') }}
                </h1>
                <p class="text-gray-700">
                    {{ t('login.description') }}
                </p>
            </div>

            <a class="block p-5 text-center text-white bg-indigo-600 hover:bg-orange-500" id="login-button" href="/auth/login">
                {{ t('login.login') }}
            </a>
        </div>
    </div>
</template>

<script>
import FlashMessage from './../Components/FlashMessage.vue';

export default {
    components: {
        FlashMessage,
    },
    beforeCreate() {
        this.loadLocale("en-US");
    }
};
</script>
