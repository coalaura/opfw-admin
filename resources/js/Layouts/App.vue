<template>
    <div class="bg-white banner-bg">

        <div class="flex flex-col h-screen">
            <!-- Navbar -->
            <navbar />

            <!-- Modals -->
            <portal-target name="modals" />

            <div class="flex flex-grow overflow-hidden">
                <!-- Sidebar -->
                <sidebar class="flex-shrink-0" v-if="!isMobile()" />

                <!-- Content -->
                <div class="flex flex-col flex-grow overflow-y-auto bg-white dark:bg-gray-800 dark:text-white main-content" scroll-region ref="scrollable">
                    <div class="flex-grow p-12 pt-10 mobile:px-4 relative">
                        <i class="fas fa-clone cursor-pointer text-sm absolute top-1 left-1.5" :title="t('nav.hide_header')" @click="toggleHeader" v-if="canHideHeader"></i>

                        <!-- Flash message -->
                        <div>
                            <flash-message />
                        </div>

                        <!-- Header -->
                        <header class="flex flex-wrap items-start justify-between flex-grow mb-8 relative" v-if="!headerClosed">

                            <!-- Title -->
                            <div class="max-w-full w-full prose dark:text-white">
                                <portal-target name="title" />
                            </div>

                            <!-- Actions -->
                            <div class="absolute top-0 right-0">
                                <portal-target name="actions" />
                            </div>

                        </header>

                        <!-- Main -->
                        <main>
                            <slot />
                        </main>

                    </div>

                    <button class="fixed bottom-3 right-3 bg-gray-300 dark:bg-gray-500 rounded shadow-lg w-8 h-8 flex justify-center items-center" v-if="scrolled" @click="scrollTop">
                        <i class="fas fa-level-up-alt"></i>
                    </button>

                    <!-- Mobile Sidebar -->
                    <sidebar class="flex-shrink-0" v-if="isMobile()" />

                    <!-- Footer -->
                    <foot />
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import FlashMessage from './../Components/FlashMessage.vue';
import Navbar from './../Components/Navbar.vue';
import Sidebar from './../Components/Sidebar.vue';
import Foot from './../Components/Footer.vue';

export default {
    components: {
        FlashMessage,
        Foot,
        Sidebar,
        Navbar,
    },
    data() {
        return {
            scrolled: false,
            headerClosed: !!this.pageStore.get("header_closed")
        };
    },
    computed: {
        canHideHeader() {
            return !this.$page.url.startsWith("/players/license:");
        }
    },
    methods: {
        isMobile() {
            return window.outerWidth <= 640;
        },

        padVerticalOverflows() {
            $(".overflow-y-auto").each(function () {
                const isOverflowing = this.scrollHeight > this.clientHeight;

                if (isOverflowing) {
                    $(this).addClass("overflowing");
                } else {
                    $(this).removeClass("overflowing");
                }
            });
        },

        scrollTop() {
            this.$refs.scrollable.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        },

        toggleHeader() {
            this.headerClosed = !this.headerClosed;

            if (this.headerClosed) {
                this.pageStore.set("header_closed", "yes");
            } else {
                this.pageStore.remove("header_closed");
            }
        },

        async authPoll() {
            try {
                const response = await fetch("/meow");

                if (!response.ok && response.status === 401) {
                    window.location.reload();
                }
            } catch {}

            setTimeout(() => {
                this.authPoll();
            }, 30000);
        }
    },
    updated() {
        let mapTitle = $("#map_title").text().trim();

        if (mapTitle) {
            mapTitle += ` (${$("#server").val()})`;
        }

        const title = mapTitle || $("header h1").html()?.replace(/<span class="sr-only">.+?<\/span>|<\/?[^>]+(>|$)/g, "")?.trim();

        if (title) {
            const cluster = this.$page?.auth?.cluster ? this.$page.auth.cluster.toUpperCase() : "OP-FW";

            $("title").text(`${cluster} - ${title}`);
        }
    },
    beforeCreate() {
        const lang = this.setting("locale") || "en-US";

        this.loadLocale(lang);
    },
    mounted() {
        this.padVerticalOverflows();

        $(window).on("resize", () => {
            this.padVerticalOverflows();
        });

        $(this.$refs.scrollable).on("scroll", () => {
            this.scrolled = this.$refs.scrollable.scrollTop > 50;
        });

        // Randomize metallic color animation delays
        const metallicElements = [...document.querySelectorAll('.metallic-gold, .metallic-silver, .metallic-bronze')];

        for (const metallic of metallicElements) {
            const randomDelay = Math.random() * -3;

            metallic.style.setProperty('--shine-delay', `${randomDelay}s`);
        }

        setTimeout(() => {
            this.authPoll();
        }, 10000);
    }
};
</script>
