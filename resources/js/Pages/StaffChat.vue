<template>
    <div>

        <button @click="notificationSound = !notificationSound" class="text-lg text-white absolute top-4 right-4">
            <i class="fas fa-volume-up text-green-200" v-if="notificationSound"></i>
            <i class="fas fa-volume-mute text-red-200" v-else></i>
        </button>

        <div class="-mt-12">
            <div class="flex flex-wrap flex-row">
                <form class="mb-6 flex w-full" @submit.prevent="sendChat">
                    <input class="w-full px-4 py-2 mr-3 bg-gray-200 dark:bg-gray-600 border rounded !outline-none" maxlength="250" required placeholder="Hey gang!" v-model="staffMessage" @keypress="chatKeyPress($event)" :disabled="isSendingChat">

                    <button class="px-4 py-2 font-semibold text-white rounded hover:shadow-lg flex-shrink-0" type="submit" :class="{ 'bg-success dark:bg-dark-success': sentChatFlash, 'bg-warning dark:bg-dark-warning': isSendingChat, 'bg-primary dark:bg-dark-primary': !sentChatFlash && !isSendingChat }">
                        <span v-if="sentChatFlash">
                            <i class="fas fa-check"></i>
                        </span>
                        <span v-else-if="!isSendingChat">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <span v-else>
                            <i class="fas fa-cog animate-spin"></i>
                        </span>
                    </button>
                </form>

                <div class="w-full mb-3" v-if="isLoading">
                    <div class="badge border-blue-200 bg-blue-100 dark:bg-blue-900 inline-block px-4 leading-5 py-2 border-2 rounded">
                        <i class="fas fa-cog animate-spin mr-1"></i>
                        {{ t('staff_chat.connecting') }}
                    </div>
                </div>

                <div class="w-full mb-3" v-if="socketError">
                    <div class="badge border-red-200 bg-red-100 dark:bg-red-900 inline-block px-4 leading-5 py-2 border-2 rounded">
                        {{ t('staff_chat.failed') }}
                    </div>
                </div>

                <div class="w-full mb-3" v-if="!socketError && !isLoading && staffMessages.length === 0">
                    <div class="badge border-yellow-200 bg-yellow-100 dark:bg-yellow-900 inline-block px-4 leading-5 py-2 border-2 rounded">
                        {{ t('staff_chat.no_messages') }}
                    </div>
                </div>

                <div class="w-full mb-3" v-for="(message, index) in staffMessages" :key="index" v-else>
                    <!-- Report Message -->
                    <div :title="formatTimestamp(message.createdAt * 1000)" class="badge border-green-200 bg-green-100 dark:bg-green-900 inline-block px-4 leading-5 py-2 border-2 rounded" v-if="message.type === 'report'">
                        <a :href="'/players/' + message.user.licenseIdentifier" class="font-semibold text-black dark:text-white !no-underline">{{ message.user.playerName }}:</a> {{ message.message }}
                    </div>

                    <!-- Staff Chat Message -->
                    <div :title="formatTimestamp(message.createdAt * 1000)" class="badge border-purple-200 bg-purple-100 dark:bg-purple-900 inline-block px-4 leading-5 py-2 border-2 rounded" v-else>
                        <a :href="'/players/' + message.user.licenseIdentifier" class="font-semibold text-black dark:text-white !no-underline">{{ message.user.playerName }}:</a> {{ message.message }}
                    </div>
                </div>
            </div>

        </div>

    </div>
</template>

<script>
import Layout from "../Layouts/Plain";
import Badge from "../Components/Badge";
import DataCompressor from "./Map/DataCompressor";

import { io } from "socket.io-client";

export default {
    layout: Layout,
    components: {
        Badge
    },
    data() {
        return {
            isLoading: false,
            isInitialized: false,
            socketError: false,
            staffMessages: [],
            staffMessage: "",
            isSendingChat: false,

            sentChatFlash: false,
            sentFlashTimeout: false,

            notificationSound: false
        };
    },
    methods: {
        formatTimestamp(time) {
            return this.$options.filters.formatTime(time);
        },
        async sendChat() {
            if (this.isSendingChat) {
                return;
            }

            clearTimeout(this.sentFlashTimeout);

            this.isSendingChat = true;

            // Send request.
            await this.$inertia.post('/staffChat', {
                message: this.staffMessage
            });

            // Reset.
            this.isSendingChat = false;
            this.staffMessage = "";

            this.sentChatFlash = true;

            this.sentFlashTimeout = setTimeout(() => {
                this.sentChatFlash = false;
            }, 5000);
        },
        chatKeyPress(event) {
            if (event.key === 'Enter') {
                this.sendChat();
            }
        },
        initChat() {
            if (this.isInitialized) {
                return;
            }
            this.isInitialized = true;
            this.isLoading = true;

            this.socketError = false;

            const isDev = window.location.hostname === 'localhost';

            const token = this.$page.auth.token,
                server = this.$page.auth.server,
                socketUrl = isDev ? 'ws://localhost:9999' : 'wss://' + window.location.host;

            let socket = io(socketUrl, {
                reconnectionDelayMax: 5000,
                query: {
                    server: server,
                    token: token,
                    type: "staff",
                    license: this.$page.auth.player.licenseIdentifier
                }
            });

            socket.on("message", async (buffer) => {
                this.isLoading = false;

                try {
                    const unzipped = await DataCompressor.GUnZIP(buffer);

                    const messages = JSON.parse(unzipped).reverse();

                    if (messages[0]?.type === 'report' && messages[0]?.createdAt !== this.staffMessages[0]?.createdAt) {
                        this.notify();
                    }

                    this.staffMessages = messages;
                } catch (e) {
                    console.error('Failed to parse socket message ', e)
                }
            });

            socket.on("disconnect", () => {
                this.isLoading = false;
                this.isInitialized = false;

                socket.close();

                this.socketError = true;

                setTimeout(() => {
                    this.initChat();
                }, 3000);
            });
        },
        notify() {
            if (!this.notificationSound) {
                return;
            }

            const audio = new Audio("/images/notification_pop.ogg");

            audio.volume = 0.55;

            audio.play();
        }
    },
    mounted() {
        const _this = this;

        this.$nextTick(function () {
            _this.initChat();
        });
    },
    props: {}
}
</script>
