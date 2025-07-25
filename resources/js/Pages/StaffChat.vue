<template>
    <div class="w-full h-full relative" id="chat">
        <div class="absolute top-1 right-1 text-white text-xs font-medium flex gap-2 select-none">
            <div class="py-0.5 px-1.5 cursor-pointer rounded transition shadow-sm" :class="{ 'bg-lime-600': autoScroll, 'bg-gray-600 line-through !text-gray-200 opacity-90': !autoScroll }" @click="autoScroll = !autoScroll">
                <i class="fas fa-scroll mr-1" v-if="autoScroll"></i>
                <i class="fas fa-hand-paper mr-1" v-else></i>

                {{ t("staff_chat.auto_scroll") }}
            </div>

            <div class="py-0.5 px-1.5 cursor-pointer rounded transition shadow-sm" :class="{ 'bg-lime-600': soundEffects, 'bg-gray-600 line-through !text-gray-200 opacity-90': !soundEffects }" @click="soundEffects = !soundEffects">
                <i class="fas fa-bell mr-1" v-if="soundEffects"></i>
                <i class="fas fa-bell-slash mr-1" v-else></i>

                {{ t("staff_chat.sound") }}
            </div>

            <div class="py-0.5 px-1.5 cursor-pointer rounded transition shadow-sm" :class="{ 'bg-lime-600': localStaff, 'bg-gray-600 line-through !text-gray-200 opacity-90': !localStaff }" @click="localStaff = !localStaff">
                <i class="fas fa-comments mr-1" v-if="localStaff"></i>
                <i class="fas fa-comment-slash mr-1" v-else></i>

                {{ t("staff_chat.local_staff") }}
            </div>
        </div>

        <div class="messages" ref="messages">
            <div class="message" v-for="message in messages" :class="message.color" :title="message.claimed ? t('staff_chat.report_claimed') : ''" v-if="!message.local || localStaff">
                <a class="title" :href="'/players/' + message.license" target="_blank">{{ message.title }}:</a>
                <span class="text" v-html="message.text"></span>
                <span class="time">{{ message.time }}</span>
            </div>

            <div class="message red" v-if="!socket">
                <span class="title">{{ t("staff_chat.voice_chat") }}:</span>
                <span class="text">{{ t("staff_chat.disconnected") }}</span>
            </div>

            <div class="message red" v-if="isLoading">
                <span class="title">{{ t("staff_chat.voice_chat") }}:</span>
                <span class="text">{{ t("staff_chat.connecting") }}</span>
            </div>
        </div>

        <div v-if="!isLoading && socket">
            <p class="notice" v-html="t('staff_chat.notice')"></p>

            <div class="input-wrap" :class="{ 'opacity-75': isSendingChat }">
                <div class="prefix">
                    <i class="fas fa-spinner fa-spin" v-if="isSendingChat"></i>
                    <span v-else>➤</span>
                </div>

                <input class="input !outline-none" v-model="chatInput" spellcheck="false" @keydown="keydown" :disabled="isSendingChat" ref="chat" />
            </div>
        </div>
    </div>
</template>

<style>
@import url("https://fonts.googleapis.com/css2?family=Rubik:wght@400;500&display=swap");

html,
body {
    width: 100%;
    height: 100%;
}

#chat {
    background: url(../../css/images/default.webp);
    background-size: cover;
    background-position: center;
    padding: 5vh;
    font-family: "Rubik", sans-serif;
    font-size: 2.15vh;
    color: white;
    line-height: 3.2vh;
    display: flex;
    flex-direction: column;
    gap: 2vh;
}

.messages {
    display: flex;
    flex-wrap: wrap;
    grid-gap: 1.4vh;
    align-items: flex-start;
    align-content: flex-start;
    height: 100%;
    overflow-y: auto;
}

.message {
    padding: 1.4vh 2.2vh;
    border-radius: 1.2vh;
    overflow: hidden;
    max-width: 100%;
    word-wrap: break-word;
    position: relative;

    .title {
        font-weight: 500;
        padding-right: 0.4vh;
    }

    .emote {
        display: inline;
        height: 3.2vh;
        vertical-align: middle;
    }

    .time {
        display: none;
        bottom: .4vh;
        font-size: 1.2vh;
        font-style: italic;
        position: absolute;
        right: .8vh;
        line-height: 1.2vh;
    }

    &:hover {
        .time {
            display: block;
        }
    }
}

.dark-purple {
    background: rgba(102, 36, 146, 0.85);
}

.purple {
    background: rgba(125, 63, 166, 0.85);
}

.green {
    background: rgba(50, 130, 35, 0.85);
}

.red {
    background: rgba(140, 50, 35, 0.85);
}

.gray {
    background: rgba(90, 90, 90, 0.85);
}

.notice {
    line-height: 2vh;
    font-size: 1.8vh;
    font-style: italic;
    color: #ddd;
    margin-bottom: 0.7vh;
}

.notice b {
    font-style: normal;
}

.input-wrap {
    background: rgba(44, 62, 80, 0.7);
    border: 1px solid rgb(77, 144, 254);
    box-shadow: 0 0 2px rgb(77, 144, 254);
    padding: 1vh 2.2vh;
    border-radius: 1.2vh;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.input {
    flex-grow: 1;
    border: none;
    background: none;
    font-weight: 500;
    padding: 0.3vh 1.3vh;
    outline: none !important;

    &:focus {
        outline: none;
    }
}
</style>

<script>
export default {
    props: {
        emotes: {
            type: Array
        }
    },
    data() {
        return {
            chatInput: "",

            messages: [],

            isSendingChat: false,
            isLoading: false,
            initialScroll: true,
            error: false,

            localStaff: localStorage.getItem("localStaff") === "true",
            soundEffects: localStorage.getItem("soundEffects") !== "false",
            notifications: localStorage.getItem("notifications") === "true",
            autoScroll: localStorage.getItem("autoScroll") !== "false",

            socket: false
        };
    },
    watch: {
        localStaff() {
            localStorage.setItem("localStaff", this.localStaff ? "true" : "false");

            this.scroll();
        },

        soundEffects() {
            localStorage.setItem("soundEffects", this.soundEffects ? "true" : "false");

            if (this.soundEffects) this.notify();
        },

        autoScroll() {
            localStorage.setItem("autoScroll", this.autoScroll ? "true" : "false");

            if (this.autoScroll) this.scroll();
        }
    },
    methods: {
        keydown(e) {
            if (e.key !== "Enter") return;

            this.sendChat();
        },
        async sendChat() {
            if (this.isSendingChat) return;

            this.isSendingChat = true;

            // Replace emotes
            const text = this.chatInput.replace(/:(\w+):/g, (match, name) => {
                name = name.toLowerCase();

                const emote = this.emotes.find(emote => emote.name.toLowerCase() === name);

                if (!emote) return match;

                return `<${emote.id}>`;
            });

            try {
                await _post('/chat', {
                    message: text
                });
            } catch (e) { }

            this.chatInput = "";
            this.isSendingChat = false;

            this.$refs.chat?.focus();
        },
        chatKeyPress(event) {
            if (event.key === 'Enter') {
                this.sendChat();
            }
        },
        formatMessage(message) {
            // Slightly scuffed html encoding by the server
            message = message.trim()
                .replace(/&lt(?!;)/g, "<")
                .replace(/&gt(?!;)/g, ">")
                .replace(/&quot(?!;)/g, '"');

            // Escape HTML
            message = message
				.replace(/&/g, "&amp;")
				.replace(/</g, "&lt;")
				.replace(/>/g, "&gt;")
				.replace(/"/g, "&quot;")
				.replace(/'/g, "&#039;");

            // Emotes
            message = message.replace(/&lt;(\d{1,})&gt;/gm, (match, id) => {
                const emote = this.emotes.find(emote => emote.id === id);

                if (!emote) return match;

                return `<img src="https://cdn.discordapp.com/emojis/${emote.id}.webp?size=128&animated=${emote.animated ? "true" : "false"}" class="emote" title=":${emote.name}:" />`;
            });

            return message;
		},
        formatTitle(message) {
            let type = message.type.toUpperCase();

            switch (message.type) {
                case "report":
                    type = `REPORT-${message.reportId}`;

                    break;
                case "staff":
                    type = message.local ? "LOCAL STAFF" : "STAFF";

                    break;
            }

            return `${type} ${message.user.displayName ? message.user.displayName : `${message.user.playerName}${message.user.source ? ` [${message.user.source}]` : ''}`}`;
        },
        formatColor(message) {
            switch (message.type) {
                case "report":
                    return "claimed" in message && message.claimed ? "gray" : "green";
                case "staff":
                    return message.local ? "dark-purple" : "purple";
            }

            return "";
        },
        async init() {
            if (this.socket || this.isLoading) return;

            this.isLoading = true;
            this.initialScroll = true;

            const token = await this.grabToken();

            if (!token) {
                this.isLoading = false;

                return;
            }

            this.socket = this.createSocket("staff", {
                onData: data => {
                    this.isLoading = false;

                    try {
                        const messages = data.map(message => {
                            message.title = this.formatTitle(message);
                            message.text = this.formatMessage(message.message);

                            return message;
                        });

                        if (!messages.length) return;

                        const hasReports = messages.find(message => message.type === "report");

                        if (hasReports) {
                            this.notify();
                        }

                        const hasNew = messages.find(message => !this.messages.find(current => current.createdAt === message.createdAt && current.license === message.user.licenseIdentifier));

                        this.messages = messages.map(message => {
                            return {
                                license: message.user.licenseIdentifier,
                                title: message.title,
                                text: message.text,
                                claimed: "claimed" in message && message.claimed,
                                color: this.formatColor(message),
                                createdAt: message.createdAt,
                                time: dayjs.utc(message.createdAt * 1000).local().fromNow(),
                                local: !!message.local
                            };
                        });

                        if (hasNew) {
                            this.scroll();

                            this.$refs.chat?.focus();
                        }
                    } catch (e) {
                        console.error('Failed to parse socket message', e);
                    }
                },
                onNoData: () => {
                    this.isLoading = false;
                },
                onDisconnect: () => {
                    this.socket = false;

                    setTimeout(() => {
                        this.init();
                    }, 2000);
                },
            });
        },
        scroll() {
            if (!this.initialScroll && !this.autoScroll) return;

            this.initialScroll = true;

            const messages = this.$refs.messages;

            this.$nextTick(async () => {
                messages.scrollTo({
                    top: messages.scrollHeight,
                    behavior: "smooth"
                });

                await this.wait(500);

                messages.scrollTo({
                    top: messages.scrollHeight,
                    behavior: "smooth"
                });
            });
        },
        notify() {
            if (!this.soundEffects) return;

            const audio = new Audio("/images/notification_pop.ogg");

            audio.volume = 0.55;

            audio.play();
        },
    },
    beforeCreate() {
        const lang = this.setting("locale") || "en-US";

        this.loadLocale(lang);
    },
    mounted() {
        this.init();

        window.addEventListener("resize", () => {
            this.scroll();
        });
    }
}
</script>
