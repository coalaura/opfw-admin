<template>
	<div class="w-full" :class="{ 'pt-1': contentType }">
		<div class="flex gap-2" v-if="isArray()">
			<div class="flex flex-col gap-2 w-full relative">
				<div class="absolute -top-4 left-0.5 italic font-semibold text-xxs select-none" :class="colors[level]">array</div>

				<div class="p-3 italic" :class="levels[level]" v-if="value.data.length === 0">{{ t('tools.config.no_entries') }}</div>

				<div class="flex gap-3 p-3" :class="`${levels[level]} ${!nextType ? 'items-center py-1.5' : ''}`" v-for="(item, index) in value.data" :key="index">
					<div class="font-semibold flex-shrink-0 w-6 select-none">{{ index }}</div>

					<ConfigValue :value="item" @update:value="updateArrayItem(index, $event)" :level="level + 1" :expected="expected" />

					<div class="flex gap-2 flex-shrink-0">
						<div class="flex justify-center items-center w-8 h-8 border-2 cursor-pointer bg-yellow-100 dark:bg-yellow-800 border-yellow-300 dark:border-yellow-400 text-yellow-800 dark:text-white" @click="updateArrayIndex(index, index - 1)" v-if="index > 0" :title="t('tools.config.move_entry_up')">
							<i class="fas fa-arrow-up"></i>
						</div>

						<div class="flex justify-center items-center w-8 h-8 border-2 cursor-pointer bg-yellow-100 dark:bg-yellow-800 border-yellow-300 dark:border-yellow-400 text-yellow-800 dark:text-white" @click="updateArrayIndex(index, index + 1)" v-if="index < value.data.length - 1" :title="t('tools.config.move_entry_down')">
							<i class="fas fa-arrow-down"></i>
						</div>

						<div class="flex justify-center items-center w-8 h-8 border-2 cursor-pointer bg-red-100 dark:bg-red-800 border-red-300 dark:border-red-400 text-red-800 dark:text-white" @click="removeArrayIndex(index)" :title="t('tools.config.remove_array_entry')">
							<i class="fas fa-trash-alt"></i>
						</div>
					</div>
				</div>
			</div>

			<div class="flex gap-2 flex-shrink-0">
				<template v-if="value.data.length === 0">
					<div class="flex justify-center items-center w-8 h-8 border-2 cursor-pointer bg-lime-100 dark:bg-lime-800 border-lime-300 dark:border-lime-400 text-lime-800 dark:text-white" @click="addArrayIndex(true)" v-if="contentType" :title="t('tools.config.add_end')">
						<i class="fas fa-plus"></i>
					</div>
				</template>

				<template v-else>
					<div class="flex justify-center items-center w-8 h-8 border-2 cursor-pointer bg-lime-100 dark:bg-lime-800 border-lime-300 dark:border-lime-400 text-lime-800 dark:text-white" @click="addArrayIndex(false)" v-if="contentType" :title="t('tools.config.add_beginning')">
						<i class="fas fa-fast-backward"></i>
					</div>

					<div class="flex justify-center items-center w-8 h-8 border-2 cursor-pointer bg-lime-100 dark:bg-lime-800 border-lime-300 dark:border-lime-400 text-lime-800 dark:text-white" @click="addArrayIndex(true)" v-if="contentType" :title="t('tools.config.add_end')">
						<i class="fas fa-fast-forward"></i>
					</div>
				</template>
			</div>
		</div>

		<div class="flex gap-2" v-else-if="isMap()">
			<div class="flex flex-col gap-2 w-full relative">
				<div class="absolute -top-4 left-0.5 italic font-semibold text-xxs select-none" :class="colors[level]">map</div>

				<div class="p-3 italic" :class="levels[level]" v-if="Object.keys(value.data).length === 0">{{ t('tools.config.no_entries') }}</div>

				<div class="flex gap-3 p-3" :class="`${levels[level]} ${!nextType ? 'items-center py-1.5' : ''}`" v-for="(item, key) in value.data" :key="key">
					<div class="w-56 flex-shrink-0">
						<input type="text" class="border-0 border-b-2 bg-white/10 px-2 py-1 font-semibold italic w-full" :class="borders[level]" :value="key" @change="updateMapKey(key, $event.target.value)" />
					</div>

					<ConfigValue :value="item" @update:value="updateMapItem(key, $event)" :level="level + 1" :expected="expected" />

					<div class="flex justify-center items-center w-8 h-8 flex-shrink-0 border-2 cursor-pointer bg-red-100 dark:bg-red-800 border-red-300 dark:border-red-400 text-red-800 dark:text-white" @click="removeMapKey(key)" :title="t('tools.config.remove_map_entry')">
						<i class="fas fa-trash-alt"></i>
					</div>
				</div>
			</div>

			<div class="flex justify-center items-center w-8 h-8 flex-shrink-0 border-2 cursor-pointer bg-lime-100 dark:bg-lime-800 border-lime-300 dark:border-lime-400 text-lime-800 dark:text-white" @click="addMapKey()" v-if="contentType" :title="t('tools.config.add_entry')">
				<i class="fas fa-plus"></i>
			</div>
		</div>

		<input type="text" class="border-0 border-b-2 bg-white/10 px-2 py-1 italic w-full" :class="borders[level - 1]" v-model="temporaryValue" @input="valueChanged" v-else />
	</div>
</template>

<script>
export default {
    name: "ConfigValue",
    props: {
		level: {
			type: Number,
			required: true,
		},
        value: {
            type: [Object, String],
            required: true,
        },
		expected: {
			type: Array,
            required: true,
		},
    },
    data() {
        return {
			colors: [
				"text-sky-400",
				"text-green-400",
				"text-purple-400",
				"text-orange-400",
			],
			borders: [
				"!border-sky-400",
				"!border-green-400",
				"!border-purple-400",
				"!border-orange-400",
			],
			levels: [
				"border-2 bg-sky-100 border-sky-300 dark:bg-sky-800 dark:border-sky-600",
				"border-2 bg-green-100 border-green-300 dark:bg-green-800 dark:border-green-600",
				"border-2 bg-purple-100 border-purple-300 dark:bg-purple-800 dark:border-purple-600",
				"border-2 bg-orange-100 border-orange-300 dark:bg-orange-800 dark:border-orange-600",
			],
            temporaryValue: this.isCollection() ? null : String(this.value),
        };
    },
    watch: {
        value(newValue) {
            if (this.isCollection()) return;

			this.temporaryValue = String(newValue);
        }
    },
	computed: {
		contentType() {
			if (this.level >= this.expected.length) return null;

			return this.expected[this.level];
		},
		nextType() {
			if (this.level + 1 >= this.expected.length) return null;

			return this.expected[this.level + 1];
		},
	},
    methods: {
		isCollection() {
			return this.isArray() || this.isMap();
		},
		isArray() {
			const val = this.value;

			return typeof val === "object" && val !== null && val.type === "array";
		},
		isMap() {
			const val = this.value;

			return typeof val === "object" && val !== null && val.type === "map";
		},
        valueChanged() {
            this.$emit("update:value", this.temporaryValue.trim());
        },
		addArrayIndex(push) {
			const newValue = this.nextType ? {
				type: this.nextType,
				value: this.nextType === "array" ? [] : {},
			} : "";

			const updatedData = [...this.value.data];

			if (push) {
				updatedData.push(newValue);
			} else {
				updatedData.unshift(newValue);
			}

			this.$emit("update:value", {
                ...this.value,
                data: updatedData
            });
		},
		removeArrayIndex(index) {
			const updatedData = this.value.data.filter((value, idx) => idx !== index);

			this.$emit("update:value", {
                ...this.value,
                data: updatedData
            });
		},
		updateArrayIndex(oldIndex, newIndex) {
			const updatedData = [...this.value.data],
				[itemToMove] = updatedData.splice(oldIndex, 1);

			updatedData.splice(newIndex, 0, itemToMove);

			this.$emit("update:value", {
                ...this.value,
                data: updatedData
            });
		},
        updateArrayItem(index, newValue) {
            const updatedData = [...this.value.data];

			updatedData[index] = newValue;

            this.$emit("update:value", {
                ...this.value,
                data: updatedData
            });
        },
		addMapKey(key) {
			if ("" in this.value.data) return;

			const newValue = this.nextType ? {
				type: this.nextType,
				value: this.nextType === "array" ? [] : {},
			} : "";

			const updatedData = {
				"": newValue,
				...this.value.data,
			};

			this.$emit("update:value", {
                ...this.value,
                data: updatedData
            });
		},
		removeMapKey(key) {
			const updatedData = { ...this.value.data };

            delete updatedData[key];

            this.$emit("update:value", {
                ...this.value,
                data: updatedData
            });
		},
		updateMapKey(oldKey, newKey) {
			newKey = newKey.trim();

            if (oldKey === newKey || !newKey) return;

			const updatedData = {};

			for (const [key, value] of Object.entries(this.value.data)) {
				if (key === oldKey) {
					updatedData[newKey] = value;
				} else {
					updatedData[key] = value;
				}
			}

			this.$emit("update:value", {
                ...this.value,
                data: updatedData
            });
        },
        updateMapItem(key, newValue) {
            const updatedData = { ...this.value.data };

            updatedData[key] = newValue;

            this.$emit("update:value", {
                ...this.value,
                data: updatedData
            });
        }
    },
};
</script>
