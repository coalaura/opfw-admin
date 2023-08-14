<template>
    <div @click="click">
        <slot />

        <div class="text-xs font-mono px-1 py-0.5 bg-indigo-600 text-white rounded-sm absolute shadow" :style="{top: top + 'px', left: left + 'px'}" v-if="show" id="hash-resolver">
            <div v-if="loading">
                <i class="fas fa-spinner fa-spin"></i> Loading...
            </div>
            <div v-else-if="modelName">
                <b>{{ hash }}:</b> <i>{{ modelName }}</i>
            </div>
            <div v-else>
                <i class="fas fa-times"></i> Invalid hash
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            show: false,
            loading: false,
            modelName: false,
            hash: false,

            top: 0,
            left: 0
        }
    },
    methods: {
        isHash(str) {
            return str.match(/^-?[0-9]+$/gm);
        },
        async click(e) {
            if (this.loading) return;

            const target = e.target;

            if ($(target).closest('#hash-resolver').length) {
                return;
            }

            this.show = false;

            this.hash = target.innerText.trim();

            if (!this.isHash(this.hash)) {
                return;
            }

            const rect = target.getBoundingClientRect();

            this.top = rect.top + window.scrollY + target.offsetHeight;
            this.left = rect.left + window.scrollX;

            this.show = true;

            this.loading = true;

            this.modelName = await this.resolveHash(this.hash);

            this.loading = false;
        },
    },
    mounted() {
        // Hide on scroll
        window.addEventListener('scroll', () => {
            this.show = false;
        }, true);
    }
}
</script>
