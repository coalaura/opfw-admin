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
            <div v-for="message in messages" :key="message.id" class="relative group dark:odd:bg-gray-500/10 px-1 py-0.5" :class="{ 'italic text-xs': message.system }">
                <div class="font-semibold max-w-40 truncate inline pr-1" :title="message.name" v-if="!message.system">{{ message.name }}</div>
                <div class="inline break-words" :class="getMessageColor(message)" v-html="getMessageHTML(message)"></div>

                <div class="absolute top-0 right-0 opacity-0 group-hover:opacity-100 text-xxs pointer-events-none italic text-gray-600 dark:text-gray-400 bg-gray-400/20 dark:bg-gray-600/20 backdrop-filter backdrop-blur-md px-1 py-0.5">
                    {{ $moment.unix(message.time).fromNow() }}
                </div>
            </div>
        </div>

        <div class="w-full relative bg-gray-300 dark:bg-gray-600" v-click-outside="hideEmotePicker">
            <input class="block w-full text-sm px-2 py-1 bg-transparent" :class="hasEmotes ? 'pr-13' : 'pr-8'" placeholder="Hi team..." v-model="message" minlength="1" maxlength="256" @keyup.enter="send" ref="input" />

            <i class="fas fa-smile-beam cursor-pointer absolute right-8 top-1/2 transform -translate-y-1/2" @click="showEmotes = !showEmotes" v-if="hasEmotes"></i>
            <i class="fas fa-paper-plane cursor-pointer absolute right-2 top-1/2 transform -translate-y-1/2" @click="send"></i>

            <div class="absolute bottom-full right-0 left-0 grid grid-cols-auto-6 gap-1 flex-wrap p-1 justify-between items-center bg-gray-300 dark:bg-gray-600 border-b border-gray-400 dark:border-gray-500" :class="{ 'hidden': !showEmotes }">
                <div class="w-6 h-6 cursor-pointer" v-for="(url, emote) in emotes" :key="emote" @click="insertEmote(emote)">
                    <img :src="url" :title="emote" class="w-full h-full object-contain">
                </div>
            </div>
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
        emotes: Object | Array
    },
    data() {
        return {
            socket: null,

            showEmotes: false,

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
        hasEmotes() {
            return this.emotes && Object.keys(this.emotes).length > 0;
        }
    },
    methods: {
        hideEmotePicker() {
            this.showEmotes = false;
        },
        toggleMute() {
            this.muted = !this.muted;

            if (this.muted) {
                localStorage.setItem('panel_chat_muted', 'yes');
            } else {
                localStorage.removeItem('panel_chat_muted');
            }
        },
		escapeHtml(unsafe) {
			return unsafe
				.replace(/&/g, "&amp;")
				.replace(/</g, "&lt;")
				.replace(/>/g, "&gt;")
				.replace(/"/g, "&quot;")
				.replace(/'/g, "&#039;");
		},
        getMessageColor(message) {
            if (message.system) {
                return 'text-gray-600 dark:text-gray-400';
            }

            return 'text-gray-700 dark:text-gray-300';
        },
        getMessageHTML(message) {
            let html = this.escapeHtml(message.text);

            // Emotes
            for (const emote in this.emotes) {
                const url = this.emotes[emote];

                html = html.replace(new RegExp(emote, 'g'), `<img src="${url}" title="${emote}" class="inline-block w-6 h-6" />`);
            }

            // Italic *text*
            html = html.replace(/\*([^\s][^*]+[^\s]|[^\s*]+)\*/g, '<i>$1</i>');

            // Image links
            html = html.replace(/(?<!")(https?:\/\/[^\s]+\.(png|jpe?g|webp|gif))/g, '<a href="$1" target="_blank"><img src="$1" class="inline-block max-w-full max-h-64" /></a>');

            return html;
        },
        insertEmote(emote) {
            this.showEmotes = false;

            const input = this.$refs.input,
                startPos = input.selectionStart,
                endPos = input.selectionEnd,
                newPos = startPos + emote.length + 1,
                text = this.message;

            const before = text.substring(0, startPos),
                after = text.substring(endPos);

            this.message = `${before}${emote} ${after}`

            input.setSelectionRange(newPos, newPos);

            input.focus();
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

                this.scrollInstant();
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

        scrollInstant() {
            const chat = this.$refs.chat;

            if (!chat) return;

            chat.scrollTop = chat.scrollHeight;
        },

        notify() {
            if (this.muted) return;

            const audio = new Audio("/images/notification_pop3.ogg");

            audio.volume = 0.3;

            audio.play();
        },

        handleKeypress(e) {
            // Tab, Space and Enter makes you focus the chat input
            if (["Tab", " ", "Enter"].includes(e.key)) {
                this.$refs.input?.focus();

                return;
            }
        }
    },
    created() {
        window.addEventListener("keyup", this.handleKeypress);
        window.addEventListener("focus", this.scrollInstant);
        window.addEventListener("fullscreenchange", this.scrollInstant);
    },
    destroyed() {
        window.removeEventListener("keyup", this.handleKeypress);
        window.removeEventListener("focus", this.scrollInstant);
        window.removeEventListener("fullscreenchange", this.scrollInstant);
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
