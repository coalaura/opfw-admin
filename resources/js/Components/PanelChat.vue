<template>
    <div class="overflow-hidden flex flex-col justify-between" :class="classes" :style="height ? `height:${height}` : ''">
        <div class="w-full italic text-xxs text-yellow-700 dark:text-yellow-400 px-1 py-0.5 select-none" v-if="connecting">
            <i class="fas fa-spinner animate-spin mr-1"></i>
            {{ t('global.connecting') }}
        </div>

        <div class="w-full italic text-xxs text-lime-700 dark:text-lime-400 px-1 py-0.5 group relative select-none" v-else-if="connected">
            <div class="flex justify-between items-center">
                <div>
                    <i class="fas fa-wifi mr-1"></i>
                    {{ t('global.connected') }}
                </div>

                <i :class="`fas fa-volume-${muted ? 'mute' : 'up'} cursor-pointer`" @click="toggleMute"></i>
            </div>

            <div class="absolute top-full left-0 right-0 text-xxs flex flex-wrap gap-1 items-center px-1 py-0.5 opacity-0 group-hover:opacity-100 pointer-events-none text-blue-700 dark:text-blue-300 bg-gray-400/20 dark:bg-gray-600/20 backdrop-filter backdrop-blur-md z-10">
                <i class="fas fa-users"></i>
                {{ names.join(', ') }}
            </div>
        </div>

        <div class="w-full italic text-xxs text-red-700 dark:text-red-400 px-1 py-0.5 select-none" v-else>
            <i class="fas fa-exclamation-triangle mr-1"></i>
            {{ t('global.disconnected') }}
        </div>

        <div class="w-full h-full overflow-y-auto" ref="chat">
            <div v-for="message in messages" :key="message.id" class="relative group dark:odd:bg-gray-500/10 px-1 py-0.5">
                <div class="font-semibold max-w-40 truncate inline pr-1" :title="message.name" v-if="!message.system">{{ message.name }}</div>
                <div class="inline break-words" :class="getMessageColor(message)">{{ message.text }}</div>

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
        dimensions: String,
        height: String | Boolean,
    },
    data() {
        return {
            socket: null,

            timeout: false,
            connecting: false,
            connected: false,
            muted: false,

            message: '',
            messages: [],
            users: [],

            debounce: false
        };
    },
    watch: {
        active() {
            if (this.active) {
                this.connect();
            } else {
                this.disconnect();
            }
        },

        height() {
            clearTimeout(this.debounce);

            this.debounce = setTimeout(this.scroll, 250);
        }
    },
    computed: {
        classes() {
            const list = [
                this.dimensions || 'w-52 xl:w-96 h-max'
            ];

            if (!this.active) {
                list.push('!w-0 opacity-0');
            }

            return list.join(' ');
        },
        names() {
            // Remove duplicate discord ids
            const unique = {};

            return this.users.filter(user => !unique[user.discord] && (unique[user.discord] = true)).map(user => user.name);
        },
    },
    methods: {
        toggleMute() {
            this.muted = !this.muted;

            if (this.muted) {
                localStorage.setItem('panel_chat_muted', 'yes');
            } else {
                localStorage.removeItem('panel_chat_muted');
            }
        },
        getMessageColor(message) {
            if (message.system) {
                return 'text-gray-600 dark:text-gray-400';
            }

            return 'text-gray-700 dark:text-gray-300';
        },
        connect() {
            clearTimeout(this.timeout);

            if (this.socket) {
                return;
            }

            this.messages = [];
            this.users = [];

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

            this.socket.on("chat", compressed => {
                console.log(`Received socket "chat" event.`);

                this.addMessage(unpack(compressed));
            });

            this.socket.on("history", compressed => {
                console.log(`Received socket "history" event.`);

                this.messages = unpack(compressed);

                this.scroll();
            });

            this.socket.on("join", compressed => {
                console.log(`Received socket "join" event.`);

                this.users.push(unpack(compressed));
            });

            this.socket.on("left", compressed => {
                console.log(`Received socket "left" event.`);

                const id = unpack(compressed);

                this.users = this.users.filter(user => user.id !== id);
            });

            this.socket.on("users", compressed => {
                console.log(`Received socket "users" event.`);

                this.users = unpack(compressed);
            });

            this.socket.on("disconnect", () => {
                console.log(`Received socket "disconnect" event.`);

                this.disconnect();

                this.timeout = setTimeout(() => this.connect(), 2500);
            });

            this.socket.on("connect", () => {
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
            this.notify();
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
        },

        notify() {
            if (this.muted) return;

            const audio = new Audio("/images/notification_pop3.ogg");

            audio.volume = 0.3;

            audio.play();
        },
    },
    mounted() {
        if (this.active) {
            this.connect();
        }

        this.muted = !!localStorage.getItem("panel_chat_muted");

        // preload
        fetch("/images/notification_pop3.ogg");
    }
}
</script>
