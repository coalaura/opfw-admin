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
                <div class="flex flex-col flex-grow overflow-y-auto bg-white dark:bg-gray-800 dark:text-white" scroll-region>
                    <div class="flex-grow p-12 pt-10 mobile:px-4 relative">

                        <!-- Flash message -->
                        <div>
                            <flash-message />
                        </div>

                        <!-- Header -->
                        <header class="flex flex-wrap items-start justify-between flex-grow mb-8 relative">

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
import FlashMessage from './../Components/FlashMessage';
import Navbar from './../Components/Navbar';
import Sidebar from './../Components/Sidebar';
import Foot from './../Components/Footer';

export default {
    components: {
        FlashMessage,
        Foot,
        Sidebar,
        Navbar,
    },
    methods: {
        isMobile() {
            return $(window).width() <= 640;
        },

        padVerticalOverflows() {
            $(".overflow-y-auto").each(function() {
                const isOverflowing = this.scrollHeight > this.clientHeight;

                if (isOverflowing) {
                    $(this).addClass("overflowing");
                } else {
                    $(this).removeClass("overflowing");
                }
            });
        }
    },
    updated() {
        let mapTitle = $("#map_title").text().trim();

        if (mapTitle) {
            mapTitle += ' (' + $("#server").val() + ')';
        }

        const title = mapTitle || $("header h1").html()?.replace(/<(\w+) .+?>.*?<\/\1>/g, "")?.trim();

        if (title) {
            const cluster = this.$page?.auth?.cluster ? this.$page.auth.cluster.toUpperCase() : "OP-FW";

            $("title").text(cluster + " - " + title);
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
    }
};
</script>
