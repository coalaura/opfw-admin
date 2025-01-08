<template>
    <div class="w-52 xl:w-96 h-max transition-all overflow-hidden flex flex-col justify-between" :class="{ '!w-0 opacity-0': !active }">
        <div class="w-full italic text-xxs text-yellow-700 dark:text-yellow-400 px-1 py-0.5" v-if="connecting">
            <i class="fas fa-spinner animate-spin mr-1"></i>
            {{ t('global.connecting') }}
        </div>

        <div class="w-full italic text-xxs text-lime-700 dark:text-lime-400 px-1 py-0.5" v-else-if="connected">
            <i class="fas fa-wifi mr-1"></i>
            {{ t('global.connected') }}
        </div>

        <div class="w-full italic text-xxs text-red-700 dark:text-red-400 px-1 py-0.5" v-else>
            <i class="fas fa-exclamation-triangle mr-1"></i>
            {{ t('global.disconnected') }}
        </div>

        <div class="w-full h-full overflow-y-auto" ref="chat">
            <div v-for="message in messages" :key="message.id" class="relative group dark:odd:bg-gray-500/10 px-1 py-0.5">
                <div class="font-semibold max-w-40 truncate inline pr-1" :title="message.name">{{ message.name }}</div>
                <div class="text-gray-700 dark:text-gray-300 inline break-words">{{ message.text }}</div>

                <div class="absolute top-0 right-0 opacity-0 group-hover:opacity-100 text-xxs pointer-events-none italic text-gray-600 dark:text-gray-400 bg-gray-400/20 dark:bg-gray-600/20 backdrop-filter backdrop-blur-md px-1 py-0.5">
                    {{ $moment.unix(message.time).fromNow() }}
                </div>
            </div>
        </div>

        <div class="w-full relative bg-gray-300 dark:bg-gray-600">
            <input class="block w-full text-sm px-2 py-1 bg-transparent pr-8" v-model="message" minlength="1" maxlength="256" @keyup.enter="send" />
            <i class="fas fa-paper-plane cursor-pointer absolute right-2 top-1/2 transform -translate-y-1/2" @click="send"></i>
        </div>
    </div>
</template>

<script>
import { io } from "socket.io-client";
import { pack, unpack } from "msgpackr";

export default {
    name: 'PanelChat',
    inheritAttrs: true,
    props: {
        active: Boolean,
    },
    data() {
        return {
            socket: null,

            timeout: false,
            connecting: false,
            connected: false,

            message: '',
            messages: []
        };
    },
    watch: {
        active() {
            if (this.active) {
                this.connect();
            } else {
                this.disconnect();
            }
        }
    },
    methods: {
        connect() {
            clearTimeout(this.timeout);

            if (this.socket) {
                return;
            }

            this.connecting = true;
            this.connected = false;

            const isDev = window.location.hostname === 'localhost',
                url = isDev ? 'ws://localhost:9999' : `wss://${window.location.host}`;

            this.socket = io(url, {
                reconnectionDelayMax: 5000,
                path: "/panel_chat",
                query: {
                    token: this.$page.auth.token,
                    server: this.$page.serverName,
                    license: this.$page.auth.player.licenseIdentifier
                }
            });

            this.socket.on("chat", async (compressed) => {
                console.log(`Received socket "chat" event.`);

                const message = unpack(compressed);

                this.addMessage(message);
            });

            this.socket.on("history", async (compressed) => {
                console.log(`Received socket "history" event.`);

                const messages = unpack(compressed);

                console.log(unpack(compressed));

                this.messages = messages;

                this.scroll();
            });

            this.socket.on("disconnect", async () => {
                console.log(`Received socket "disconnect" event.`);

                this.disconnect();

                this.timeout = setTimeout(() => this.connect(), 2500);
            });

            this.socket.on("connect", () => {
                console.log(`Received socket "connect" event.`);

                this.connecting = false;
                this.connected = true;
            });
        },

        disconnect() {
            if (!this.socket) {
                return;
            }

            this.connecting = false;
            this.connected = false;

            this.socket.close();

            this.socket = null;
        },

        send() {
            const text = this.message.trim();

            if (!this.socket || !text) return;

            this.message = '';
            this.socket.emit("chat", pack(text));
        },

        addMessage(message) {
            this.messages.push(message);

            if (this.messages.length > 100) {
                this.messages.shift();
            }

            this.scroll();
        },

        scroll() {
            const chat = this.$refs.chat;

            if (!chat) return;

            this.$nextTick(() => {
                chat.scrollTo({
                    top: chat.scrollHeight,
                    behavior: "smooth"
                });
            });
        }
    },
    mounted() {
        if (this.active) {
            this.connect();
        }
    }
}
</script>
