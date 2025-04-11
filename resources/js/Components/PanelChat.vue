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

                <div class="flex gap-1 items-center">
                    <i class="fas fa-scroll cursor-pointer" @click="enableScroll" :title="t('global.no_auto_scroll')" v-if="scrollDisabled"></i>
                    <i :class="`fas fa-volume-${muted ? 'mute' : 'up'} cursor-pointer`" @click="toggleMute"></i>
                </div>
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

        <div class="w-full h-full overflow-y-auto" ref="chat" @chat-image-loaded="scrollInstant" @wheel="onScrollWheel">
            <div v-for="message in messages" :key="message.id" class="relative group dark:odd:bg-gray-500/10 px-1 py-0.5" :class="{ 'italic text-xs py-1': message.system }">
                <div class="font-semibold max-w-40 truncate inline pr-1" :title="message.name" v-if="!message.system">
                    {{ message.name }}
                    <sup v-if="message.room">{{ message.room }}</sup>
                </div>
                <div class="inline break-words" :class="getMessageColor(message)" v-html="getMessageHTML(message)"></div>

                <div class="absolute top-0 right-0 opacity-0 group-hover:opacity-100 text-xxs pointer-events-none italic text-gray-600 dark:text-gray-400 bg-gray-400/20 dark:bg-gray-600/20 backdrop-filter backdrop-blur-md px-1 py-0.5">
                    {{ getMessageTime(message.time) }}
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
        room: String | Boolean,
        group: String,
        height: String | Boolean,
        emotes: Object | Array,
        activeViewers: Array,
        inactiveViewers: Array,
    },
    data() {
        return {
            master: false,
            socket: null,

            showEmotes: false,
            scrollDisabled: false,

            interval: false,
            timestamp: false,

            timeout: false,
            connecting: false,
            connected: false,
            reconnect: true,
            muted: !!localStorage.getItem(`panel_chat_muted_${this.group || ""}`),

            message: '',
            messages: [],
            users: [],

            debounce: false,
            scrollDebounce: false
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
        },

        room() {
            if (!this.connected) return;

            this.roomChanged();
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
                localStorage.setItem(`panel_chat_muted_${this.group || ""}`, true);
            } else {
                localStorage.removeItem(`panel_chat_muted_${this.group || ""}`);
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
        getMessageTime(time) {
            const now = dayjs.unix(this.timestamp / 1000);

            return dayjs.unix(time).from(now);
        },
        getMessageHTML(message) {
            let html = this.escapeHtml(message.text);

            // Emotes
            for (const emote in this.emotes) {
                const url = this.emotes[emote],
                    str = emote.split("").map(c => `${c}+`).join(""),
                    rgx = new RegExp(str, 'gi'),
                    full = new RegExp(`^${str}$`, 'mis');

                const isAlone = html.match(full);

                html = html.replace(rgx, `<img src="${url}" title="${emote}" class="inline-block object-contain ${isAlone ? "w-10 h-10" : "w-6 h-6"}" />`);

                if (isAlone) {
                    return html;
                }
            }

            // Italic *text*
            html = html.replace(/\*([^\s][^*]+[^\s]|[^\s*]+)\*/g, '<i>$1</i>');

            // Image links
            html = html.replace(/(?<!")(https?:\/\/[^\s]+\.(png|jpe?g|webp|gif))/g, `<a href="$1" target="_blank"><img src="$1" class="inline-block max-w-full max-h-64" loading="lazy" onload="this.dispatchEvent(new CustomEvent('chat-image-loaded', {bubbles:true}))" /></a>`);

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
        updateViewers() {
            const active = {},
                inactive = {};

            if (this.room) {
                for (const user of this.users) {
                    const discord = user.discord;

                    if (this.room === user.room) {
                        if (user.active) {
                            active[discord] = (active[discord] || 0) + 1;
                        } else {
                            inactive[discord] = (inactive[discord] || 0) + 1;
                        }
                    }
                }
            }

            const map = obj => Object.entries(obj).map(([discord, amount]) => {
                const user = this.users.find(u => u.discord === discord);

                return user.name + (amount > 1 ? ` x${amount}` : "");
            }).filter(Boolean);

            this.$emit("update:activeViewers", map(active));
            this.$emit("update:inactiveViewers", map(inactive));
        },
        async resolveToken() {
            const token = this.$page.auth.token,
                expires = this.$page.auth.expires;

            if (Date.now()/1000 < expires - 60) {
                return token;
            }

            try {
                const data = await _get("/api/chat_token");

                if (!data?.status) {
                    throw new Error("failed to retrieve token");
                }

                return data.data
            } catch (e) {
                console.error(e);
            }

            return false;
        },
        async connect() {
            clearTimeout(this.timeout);

            if (this.socket || this.connecting) {
                return;
            }

            this.connecting = true;

            const token = await this.resolveToken();

            if (!token) {
                this.connecting = false;

                return;
            }

            this.openMasterTab(`chat_${this.group}`, isMaster => {
                this.master = isMaster;
            });

            this.messages = [];
            this.users = [];

            this.connected = false;

            const isDev = window.location.hostname === 'localhost',
                url = isDev ? 'ws://localhost:9999' : `wss://${window.location.host}`;

            this.socket = io(url, {
                reconnectionDelayMax: 5000,
                path: "/panel_chat",
                query: {
                    token: token,
                    server: this.$page.serverName,
                    license: this.$page.auth.player.licenseIdentifier,
                    group: this.group || "",
                }
            });

            this.socket.on("chat", compressed => {
                console.log(`Received socket "chat" event.`);

                this.updateTimestamp();
                this.addMessage(unpack(compressed));
            });

            this.socket.on("history", compressed => {
                console.log(`Received socket "history" event.`);

                this.messages = unpack(compressed);

                this.updateTimestamp();

                setTimeout(() => {
                    this.scrollInstant(true);
                }, 500);
            });

            this.socket.on("users", compressed => {
                console.log(`Received socket "users" event.`);

                this.users = unpack(compressed);

                this.updateViewers();
            });

            this.socket.on("user", compressed => {
                console.log(`Received socket "user" event.`);

                const update = unpack(compressed),
                    user = this.users.find(user => user.id === update.id);

                if (user) {
                    user[update.key] = update.value;

                    this.updateViewers();
                } else {
                    console.warn(`User "${update.id}" not found, desynced?`);
                }
            });

            this.socket.on("disconnect", () => {
                console.log(`Received socket "disconnect" event.`);

                this.disconnect();

                if (this.active && this.reconnect) {
                    this.timeout = setTimeout(() => this.connect(), 2500);
                }
            });

            this.socket.on("connect", () => {
                this.connecting = false;
                this.connected = true;

                this.roomChanged();
                this.visibilityStateChanged();
            });
        },

        onScrollWheel() {
            clearTimeout(this.scrollDebounce);

            setTimeout(() => {
                this.scrollDisabled = this.$refs.chat.scrollTop < (this.$refs.chat.scrollHeight - this.$refs.chat.clientHeight) - 20;
            }, 250);
        },

        disconnect() {
            this.connecting = false;
            this.connected = false;

            this.messages = [];
            this.users = [];

            this.sentRoom = false;

            if (!this.socket) {
                return;
            }

            this.closeMasterTab(`chat_${this.group}`);

            this.socket.disconnect();

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

        enableScroll() {
            this.scrollDisabled = false;

            this.scroll();
        },

        scroll() {
            if (this.scrollDisabled) return;

            const chat = this.$refs.chat;

            if (!chat) return;

            this.$nextTick(() => {
                chat.scrollTo({
                    top: chat.scrollHeight,
                    behavior: "smooth"
                });
            });
        },

        scrollInstant(force = false) {
            if (this.scrollDisabled && !force) return;

            const chat = this.$refs.chat;

            if (!chat) return;

            chat.scrollTop = chat.scrollHeight;
        },

        notify() {
            if (this.muted || !this.master) return;

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
        },

        updateTimestamp() {
            this.timestamp = Date.now();
        },

        roomChanged() {
            if (!this.connected) return;

            this.socket.emit("room", pack(this.room || ""));
        },

        visibilityStateChanged() {
            if (!this.connected) return;

            this.visible = document.visibilityState !== "hidden";

            this.socket.emit("active", pack(document.visibilityState !== "hidden"));
        }
    },
    created() {
        window.addEventListener("focus", this.scrollInstant);
        window.addEventListener("visibilitychange", this.visibilityStateChanged);
        window.addEventListener("fullscreenchange", this.scrollInstant);
    },
    destroyed() {
        window.removeEventListener("focus", this.scrollInstant);
        window.removeEventListener("visibilitychange", this.visibilityStateChanged);
        window.removeEventListener("fullscreenchange", this.scrollInstant);
    },
    beforeDestroy() {
        this.reconnect = false;

        this.disconnect();

        clearInterval(this.interval);
    },
    beforeMount() {
        if (this.active) {
            this.connect();
        }

        this.interval = setInterval(this.updateTimestamp, 5000);

        // preload
        fetch("/images/notification_pop3.ogg");
    }
}
</script>
