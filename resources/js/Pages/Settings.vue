<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t("settings.title") }}
            </h1>
            <p>
                {{ t("settings.description") }}
            </p>
        </portal>

        <div class="mt-8 pt-8 border-t-2 border-gray-300 dark:border-gray-500">
            <h3 class="mb-5 dark:text-white">
                {{ t('settings.settings') }}
            </h3>

            <table class="bg-white dark:bg-black !bg-opacity-10">
                <tr class="border-t border-gray-300 dark:border-gray-500" v-for="(setting, key) in $page.auth.settings" :key="key" :class="{'opacity-30': setting.disabled}">
                    <td class="p-3 pl-8">{{ t('settings.' + key) }}</td>

                    <td class="p-3" v-if="setting.options">
                        <select v-model="setting.value" class="p-1 min-w-base block border-2 bg-gray-200 dark:bg-gray-800" :disabled="setting.disabled" @change="saveSetting(key, setting)">
                            <option v-for="(value, key) in setting.options" :value="key">{{ value }}</option>
                        </select>
                    </td>
                    <td class="p-3 relative" v-else>
                        <input v-model="setting.value" :type="getSettingsType(setting.type)" :disabled="setting.disabled" class="p-1 h-base min-w-base block border-2 border-gray-500 bg-gray-200 dark:bg-gray-800" @change="saveSetting(key, setting)" @focus="setSettingActive(setting, true)" @blur="setSettingActive(setting, false)" />

                        <div class="absolute right-0 top-18 left-0 border-gray-500 bg-gray-200 dark:bg-gray-800 mx-3 border-2 shadow z-10" v-if="setting.focus && setting.presets">
                            <div class="px-2 py-1 cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700" v-for="(preset, name) in setting.presets" :key="name" @click="saveSetting(key, setting, preset)">{{ name }}</div>
                        </div>
                    </td>

                    <td class="p-3">
                        <img :src="'/images/settings/' + key + '.png'" class="h-20" />
                    </td>
                    <td class="p-3 pr-8">
                        <i :class="getStatusIcon(setting)"></i>
                    </td>
                </tr>
            </table>
        </div>

        <div class="italic mt-8 pt-8 border-t-2 border-gray-300 dark:border-gray-500 text-sm">More coming soon™</div>

    </div>
</template>

<script>
import Layout from './../Layouts/App.vue';
import Card from './../Components/Card.vue';

export default {
    layout: Layout,
    components: {
        Card
    },
    props: {
        active: {
            type: String,
            required: true
        },
    },
    data() {
        return {
            status: false
        }
    },
    methods: {
        getStatusIcon(setting) {
            if (!setting.disabled) return 'fas fa-check';

            const prefix = 'animate-bounce';

            switch(this.status) {
                case 'download':
                    return `${prefix} fas fa-download`;
                case 'resize':
                    return `${prefix} fas fa-crop-alt`;
                case 'save':
                    return `${prefix} fas fa-save`;
            }

            return `${prefix} fas fa-truck-loading`;
        },
        formatTimestamp(timestamp) {
            return this.$options.filters.formatTime(timestamp * 1000);
        },
        getSettingsType(type) {
            switch (type) {
                case 'boolean':
                    return 'checkbox';
                case 'integer':
                    return 'number';
                default:
                    return 'text';
            }
        },
        refreshLocales(locale) {
            this.loadLocale(locale);

            this.$inertia.reload({
                preserveState: true,
                preserveScroll: true
            });
        },
        setSettingActive(setting, active) {
            if (active) {
                setting.focus = true;
            } else {
                setTimeout(() => {
                    setting.focus = false;
                }, 250);
            }
        },
        async saveSetting(key, setting, overrideValue = null) {
            if (setting.disabled) return;

            setting.disabled = true;

            if (overrideValue) setting.value = overrideValue;

            try {
                const data = await this.chunked.put(`/settings/${key}`, {
                    value: setting.value
                }, chunk => {
                    if (!chunk.status) return;

                    this.status = chunk.status;
                });

                if (!data || !data.status) {
                    alert(data?.message || 'An error occurred while saving the setting.');
                } else {
                    if ('data' in data) {
                        setting.value = data.data;
                        this.$page.auth.settings[key].value = data.data;

                        this.$bus.$emit('settingsUpdated');
                    }

                    if (key === 'locale') {
                        this.refreshLocales(setting.value);
                    }
                }
            } catch (e) {
                alert(e.message || 'An error occurred while saving the setting.');
            }

            setting.disabled = false;

            this.status = false;

            // For some reason it doesn't auto-refresh
            this.$forceUpdate();
        }
    }
}
</script>
