<template>
    <div>
        <portal to="title">
            <h1 class="dark:text-white">
                {{ t('tools.config.title') }}
            </h1>
            <p>
                {{ t('tools.config.description') }}
            </p>
        </portal>

        <div class="rounded-lg shadow bg-secondary dark:bg-dark-secondary max-w-6xl px-8 py-6 mt-14">
            <div class="flex flex-col gap-3 mb-4 pb-4 border-b-2 border-dashed border-gray-400">
                <pre class="text-xs whitespace-pre-wrap py-2 px-3 bg-gray-200 dark:bg-gray-800 rounded-sm text-gray-400 font-mono select-all">{{ code || " " }}</pre>
            </div>

            <div class="flex flex-col gap-3 mb-4 pb-4 border-b-2 border-dashed border-gray-400">
                <div class="flex items-center gap-3">
                    <label for="type" class="font-semibold">{{ t('tools.config.type') }}:</label>
                    <select class="block w-full px-3 py-1 bg-gray-200 border rounded dark:bg-gray-600" id="type" v-model="type" @change="clearConfig()" :class="{ 'cursor-not-allowed': isLoading }" :disabled="isLoading">
                        <option :value="typ" v-for="typ in types">
                            {{ typ }}
                        </option>
                    </select>
                </div>

                <div class="grid grid-cols-2" :class="{ 'opacity-50': isLoading }">
                    <div class="grid grid-cols-2 gap-3 border-r-2 pr-3 border-gray-500">
                        <select class="block w-full px-3 py-1 bg-gray-200 border rounded dark:bg-gray-600" id="type" v-model="configKey" :class="{ 'cursor-not-allowed': isLoading }" :disabled="isLoading">
                            <option :value="parameter" v-for="parameter in parameters">
                                {{ parameter }}
                            </option>
                        </select>
                        <button class="font-semibold cursor-pointer text-sm border bg-lime-200 border-lime-600 rounded dark:bg-lime-700" :class="{ 'cursor-not-allowed': isLoading }" @click="readConfig()">
                            <i class="fas fa-file-import mr-1"></i>
                            {{ t('tools.config.read_config') }}
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pl-3">
                        <button class="font-semibold cursor-pointer text-sm border bg-sky-200 border-sky-600 rounded dark:bg-sky-700" :class="{ 'cursor-not-allowed': isLoading }" @click="copyConfig()">
                            <i class="fas fa-copy mr-1"></i>
                            {{ t('tools.config.copy_config') }}
                        </button>
                        <button class="font-semibold cursor-pointer text-sm border bg-yellow-200 border-yellow-600 rounded dark:bg-yellow-700" :class="{ 'cursor-not-allowed': isLoading }" @click="clearConfig()">
                            <i class="fas fa-trash-alt mr-1"></i>
                            {{ t('tools.config.clear_config') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex justify-center text-xl py-3" v-if="isLoading">
                <i class="fas fa-spinner animate-spin"></i>
            </div>

            <ConfigValue :value="config" @update:value="updateConfig" :level="0" :expected="expected" v-else-if="config" />
        </div>

    </div>
</template>

<script>
import Layout from "./../../Layouts/App.vue";
import ConfigValue from "./../../Components/ConfigValue.vue";
import ServerConfig from "./ServerConfig.js";

export default {
    layout: Layout,
    components: {
        ConfigValue,
    },
    props: {
        parameters: {
            type: Array,
            required: true
        }
    },
    data() {
        return {
            isLoading: false,
            configKey: "JobOverrides",

            types: [
                "array",
                "map",
                "map of arrays",
                "array of maps",
                "map of maps",
                "array of arrays",
                "map of array of maps",
            ],

            type: "array",
            config: null,
            code: "",
        };
    },
    computed: {
        expected() {
            switch (this.type) {
                case "array":
                    return ["array"];
                case "map":
                    return ["map"];
                case "map of arrays":
                    return ["map", "array"];
                case "array of maps":
                    return ["array", "map"];
                case "map of maps":
                    return ["map", "map"];
                case "array of arrays":
                    return ["array", "array"];
                case "map of array of maps":
                    return ["map", "array", "map"];
            }

            return [];
        }
    },
    watch: {
        config() {
            this.code = this.exportConfig();
        }
    },
    methods: {
        createBlankEntry(level, expected) {
            const type = level < expected.length ? expected[level] : null;

            if (type) {
                const content = this.createBlankEntry(level + 1, expected),
                    blank = type === "array" ? [] : {};

                if (content !== "") {
                    if (type === "array") {
                        blank.push(content);
                    } else {
                        blank[`key_${level}`] = content;
                    }
                }

                return {
                    type: type,
                    data: blank,
                };
            }

            return `value_${level}`;
        },
        blankArray(data = []) {
            return {
                type: "array",
                data: data
            };
        },
        blankMap(data = {}) {
            return {
                type: "map",
                data: data
            };
        },
        clearConfig() {
            if (this.isLoading) return;

            this.config = this.createBlankEntry(0, this.expected);
        },
        copyConfig() {
            if (this.isLoading) return;

            this.code = this.exportConfig();

            this.copyToClipboard(this.code);
        },
        normalize(parsed) {
            const isArray = Array.isArray(parsed),
                isMap = typeof parsed === "object" && parsed !== null;

            if (isArray) {
                const normalized = [];

                for (const entry of parsed) {
                    normalized.push(this.normalize(entry));
                }

                return this.blankArray(normalized);
            } else if (isMap) {
                const normalized = {};

                for (const [key, value] of Object.entries(parsed)) {
                    normalized[key] = this.normalize(value);
                }

                return this.blankMap(normalized);
            }

            return String(parsed);
        },
        denormalize(normalized) {
            if (typeof normalized === "object" && normalized !== null && "type" in normalized) {
                const type = normalized.type;

                if (type === "array") {
                    const denormalized = [];

                    for (const value of normalized.data) {
                        denormalized.push(this.denormalize(value));
                    }

                    return denormalized;
                } else if (type === "map") {
                    const denormalized = {};

                    for (const [key, value] of Object.entries(normalized.data)) {
                        denormalized[key] = this.denormalize(value);
                    }

                    return denormalized;
                }
            }

            return String(normalized);
        },
        exportConfig() {
            const raw = this.denormalize(this.config),
                cfg = new ServerConfig();

            switch (this.type) {
                case "array":
                    return cfg.asArray(raw);
                case "map":
                    return cfg.asMap(raw);
                case "map of arrays":
                    return cfg.asMapArray(raw);
                case "array of maps":
                    return cfg.asArrayMap(raw);
                case "map of maps":
                    return cfg.asMapMap(raw);
                case "array of arrays":
                    return cfg.asArrayArray(raw);
                case "map of array of maps":
                    return cfg.asMapArrayMap(raw);
            }

            return "";
        },
        async readConfig() {
            if (this.isLoading) return;

            this.isLoading = true;

            let value, type;

            try {
                const data = await _get(`/api/config/${this.configKey}`);

                if (!data?.status || !data?.data) {
                    throw new Error("Config not found");
                }

                value = data.data.value || "";
                type = data.data.type;
            } catch (e) {
                console.error(e);
            }

            try {
                const cfg = new ServerConfig(value);

                let parsed, expected;

                switch (type) {
                    case "array":
                        parsed = cfg.array();
                        expected = ["array"];

                        break;
                    case "map":
                        parsed = cfg.map();
                        expected = ["map"];

                        break;
                    case "map of arrays":
                        parsed = cfg.mapArray();
                        expected = ["map", "array"];

                        break;
                    case "array of maps":
                        parsed = cfg.arrayMap();
                        expected = ["array", "map"];

                        break;
                    case "map of maps":
                        parsed = cfg.mapMap();
                        expected = ["map", "map"];

                        break;
                    case "array of arrays":
                        parsed = cfg.arrayArray();
                        expected = ["array", "array"];

                        break;
                    case "map of array of maps":
                        parsed = cfg.mapArrayMap();
                        expected = ["map", "array", "map"];

                        break;
                    default:
                        throw new Error(`invalid type: ${type}`);
                }

                this.type = type;
                this.config = this.normalize(parsed, expected);
            } catch(e) {
                console.error(e);
            }

            this.isLoading = false;
        },
        updateConfig(newValue) {
            this.config = newValue;
        },
    },
    mounted() {
        this.clearConfig();
    }
}
</script>
