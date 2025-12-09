<template>
    <div :class="layout">
        <div class="px-2 py-0.5 cursor-pointer truncate border-lime-300 bg-lime-200 dark:bg-lime-700" :title="itemLabel(item)" :class="{ '!border-red-300 !bg-red-200 dark:!bg-red-700': !value.includes(item) }" v-for="item in items" :key="value.value" @click="toggle(item)">
            {{ itemLabel(item) }}
        </div>
    </div>
</template>

<script>
export default {
    name: 'MultiSelector',
    inheritAttrs: true,
    props: {
        value: {
            type: Array,
            required: true
        },
        items: {
            type: Array,
            required: true
        },
        labels: {
            type: Object,
        },
        prefix: {
            type: String
        },
        locale: {
            type: String
        },
        layout: {
            type: String,
            default: "grid grid-cols-4 gap-3 justify-evenly"
        }
    },
    methods: {
        itemLabel(item) {
            let label = item;

            if (this.labels && item in this.labels) {
                label = this.labels[item];

                if (typeof label === "object") {
                    return label.label;
                }

                return label;
            }

            if (this.locale) {
                label = this.t(`${this.locale}.${item}`);
            }

            if (this.prefix) {
                label = this.prefix + label;
            }

            return label;
        },
        toggle(item) {
            let newValue;

            if (this.value.includes(item)) {
                newValue = this.value.filter(i => i !== item);
            } else {
                newValue = [...this.value, item];
            }

            this.$emit('input', newValue);
        }
    }
}
</script>
