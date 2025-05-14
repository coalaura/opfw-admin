<template>
    <div class="p-8 mb-10 rounded-lg shadow mobile:p-4 mobile:py-2 relative max-w-full v-section" :class="{'bg-gray-300 dark:bg-gray-600 scale-105': bright, 'bg-secondary dark:bg-dark-secondary': !bright, 'resizing': isResizing}" ref="section">
        <!-- Header -->
        <header :class="{ 'mb-8': !collapsed }" v-if="!noHeader">
            <slot name="header" />
        </header>

        <!-- Body -->
        <div :class="!noFooter ? 'mb-8' : ''" v-if="!collapsed">
            <slot />
        </div>

        <!-- Footer -->
        <footer v-if="!noFooter && !collapsed">
            <slot name="footer" />
        </footer>

        <div class="absolute top-0 right-0 bottom-0 w-2 bg-gray-300 dark:bg-gray-500 cursor-resize" @mousedown="startResize" @dblclick="resetResize" v-if="resizable"></div>
    </div>
</template>

<script>
export default {
    name: 'Section',
    props: {
        noFooter: {
            type: Boolean,
            default: false
        },
        noHeader: {
            type: Boolean,
            default: false
        },
        bright: {
            type: Boolean,
            default: false
        },
        collapsed: {
            type: Boolean,
            default: false
        },
        resizable: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            isResizing: false
        };
    },
    methods: {
        resetResize() {
            this.pageStore.remove("section");

            this.$refs.section.style.width = "";
        },
        startResize($event) {
            if ($event.buttons !== 1) {
                if ($event.buttons === 4) {
                    this.resetResize();
                }

                return;
            }

            this.isResizing = true;

            window.addEventListener("mousemove", this.resize);
            window.addEventListener("mouseup", this.finishResize);
        },
        finishResize() {
            window.removeEventListener("mousemove", this.resize);
            window.removeEventListener("mouseup", this.finishResize);

            this.isResizing = false;

            this.$emit("resize");
        },
        resize($event) {
            const section = this.$refs.section,
                rect = section.getBoundingClientRect(),
                width = `min(100%, ${$event.clientX - rect.left}px)`;

            section.style.width = width;

            this.pageStore.set("section", width);

            this.$emit("resize");
        }
    },
    mounted() {
        if (!this.resizable) {
            return;
        }

        const width = this.pageStore.get("section", 1920);

        if (width) {
            this.$refs.section.style.width = width;
        }
    }
}
</script>
