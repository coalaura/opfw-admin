<template>
    <div @click="click">
        <slot />
    </div>
</template>

<script>
export default {
    methods: {
        isHash(str) {
            const num = parseInt(str);

            if (Number.isNaN(num)) return false;

            // These may be "valid", but there is no model with such a hash, these are more commonly server-ids or similar
            if ([0, -1].includes(num) || (num > 0 && num < 9000)) return false;

            return !str.match(/\s/gm) && str.match(/^-?[0-9]+$/m);
        },
        async click(e) {
            const target = e.target;

            if (!target || target.classList.contains('resolved') || target.parentElement.classList.contains('resolved')) return;

            let hash = target.innerText.trim();

            if (!this.isHash(hash)) return;

            target.classList.add('resolved');

            target.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Resolving...`;

            const { name, joaat } = await this.resolveHash(hash);

            hash = hash || joaat;

            if (name) {
                target.innerHTML = `<a class="font-mono cursor-help italic" target="_blank" href="https://forge.plebmasters.de/objects?search=${name}" title="Hash: ${hash}">${name}</a>`;
            } else {
                target.innerHTML = `<i class="font-mono cursor-help" title="Unknown hash">${hash}</i>`;
            }
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
