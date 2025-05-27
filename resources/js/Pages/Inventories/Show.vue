<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ name }}

                <inertia-link class="sup font-semibold bg-transparent text-blue-600 dark:text-blue-400" :title="t('inventories.view_logs')" :href="'/inventory/logs/' + name">
                    <i class="fas fa-dolly-flatbed"></i>
                </inertia-link>
            </h1>
            <p v-if="totalItems > 0">
                {{ t('inventories.show.description', totalItems, totalWeight) }}
            </p>
        </portal>

        <!-- Table -->
        <v-section class="overflow-x-auto w-inventory" :noHeader="true" :noFooter="true">
            <template>
                <div class="grid grid-cols-5 gap-3 w-max">
                    <div v-for="(items, slot) in contents" :key="slot" class="bg-black bg-opacity-10 rounded-sm border border-gray-500 w-item relative pt-2 text-white">
                        <template v-if="items.length > 0">
                            <div class="text-sm absolute top-0.5 right-1.5 select-none">x{{ items.length }}</div>

                            <button v-if="canEditItems" class="bg-transparent absolute top-0.5 left-1.5 text-red-600 dark:text-red-500 font-semibold text-sm cursor-pointer" :title="t('inventories.show.delete_item_slot')" @click="deleteItemSlot(slot)">
                                <i class="fas fa-trash-alt"></i>
                            </button>

                            <button v-if="canEditItems" class="bg-transparent absolute top-0.5 left-6 text-yellow-500 dark:text-yellow-400 font-semibold text-sm cursor-pointer" :title="t('inventories.show.edit_item_slot')" @click="editItemSlot(slot, items)">
                                <i class="fas fa-wrench"></i>
                            </button>

                            <button v-if="canEditItems" class="bg-transparent absolute top-0.5 left-11 text-teal-500 dark:text-teal-400 font-semibold text-sm cursor-pointer" :title="t('inventories.show.move_item_slot')" @click="moveItemSlot(slot)">
                                <i class="fas fa-people-carry"></i>
                            </button>

                            <img :src="getItemIcon(items[0])" @error="iconFailed($event)" class="w-full h-32 p-3 object-contain" />
                        </template>

                        <template v-else>
                            <button v-if="canEditItems" class="bg-transparent absolute top-0.5 right-1.5 text-lime-600 dark:text-lime-500 font-semibold text-sm cursor-pointer" :title="t('inventories.show.add_item_slot')" @click="editItemSlot(slot, items)">
                                <i class="fas fa-plus"></i>
                            </button>

                            <div class="h-32">&nbsp;</div>
                        </template>

                        <div class="px-1 py-0.5 text-center truncate text-sm bg-black bg-opacity-10" v-html="getItemLabelForSlot(slot)" :title="getItemLabelForSlot(slot, true, true)"></div>
                    </div>
                </div>
            </template>
        </v-section>

        <modal :show="isEditing">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('inventories.show.editing_slot', editingSlot) }}
                </h1>
            </template>

            <template #default>
                <div class="grid grid-cols-2 gap-12">
                    <div class="flex gap-3 items-center">
                        <label class="block w-40" for="amount">
                            {{ t('inventories.show.amount') }}
                        </label>
                        <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="amount" placeholder="12" type="number" min="1" max="250" v-model="editingAmount" :readonly="!editedItemStackable" />
                    </div>

                    <div class="flex gap-3 items-center">
                        <label class="block w-40" for="amount">
                            {{ t('inventories.show.item_name') }}
                        </label>
                        <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" :class="{ 'border-red-500': !editedItemNameValid }" id="amount" placeholder="skateboard" minlength="1" maxlength="255" v-model="editingItem" @input="changedItemName" />
                    </div>
                </div>

                <div class="pt-6 mt-6 border-t border-gray-500">
                    <div class="w-full bg-gray-300 dark:bg-gray-600 text-sm flex">
                        <input type="number" class="w-full text-sm bg-transparent py-1 px-2 border-0" placeholder="12345" v-model="attachCharacterId" />
                        <button class="py-1 px-2 bg-lime-600 hover:bg-lime-500 whitespace-nowrap" @click="loadAttachedCharacter" :class="{ 'opacity-50 !bg-rose-600': attachCharacterLoading || !parseInt(attachCharacterId) }">
                            <i class="fas fa-spinner animate-spin mr-1" v-if="attachCharacterLoading"></i>
                            {{ t('inventories.show.attach_identity') }}
                        </button>
                    </div>
                </div>

                <h3 class="pt-6 mt-6 mb-3 text-lg border-t border-gray-500">{{ t('inventories.show.metadata') }}</h3>

                <table class="w-full bg-gray-300 dark:bg-gray-600 text-sm">
                    <tr class="border-b-2 border-gray-500 text-left">
                        <th class="px-2 py-1">
                            <button class="font-semibold cursor-pointer text-sm" @click="addMetadataKey()">
                                <i class="fas fa-plus"></i>
                            </button>
                        </th>
                        <th class="px-2 py-1">{{ t('inventories.show.key') }}</th>
                        <th class="px-2 py-1">{{ t('inventories.show.value') }}</th>
                    </tr>

                    <tr v-for="(entry, index) in editingMetadata" :key="index" class="border-t border-gray-500">
                        <td class="px-2 py-1">
                            <button class="font-semibold cursor-pointer text-sm" @click="removeMetadataKey(entry.key)">
                                <i class="fas fa-minus"></i>
                            </button>
                        </td>
                        <td class="px-2 py-1">
                            <input class="text-sm bg-transparent py-1 px-2 border-0 border-b-2" type="text" v-model="entry.key" />
                        </td>
                        <td class="px-2 py-1 w-full">
                            <input class="w-full text-sm bg-transparent py-1 px-2 border-0 border-b-2" :class="{ '!border-red-500': !isFieldValid(entry.key, entry.value) }" :title="!isFieldValid(entry.key, entry.value) ? t('inventories.show.field_invalid') : ''" type="text" v-model="entry.value" />
                        </td>
                    </tr>
                </table>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500" @click="isEditing = false">
                    {{ t('global.close') }}
                </button>
                <button type="button" class="px-5 py-2 rounded bg-lime-200 hover:bg-lime-300 dark:bg-lime-600 dark:hover:bg-lime-500" @click="saveEditing()" v-if="editedItemNameValid">
                    {{ t('inventories.show.save') }}
                </button>
            </template>
        </modal>

        <modal :show="isMoving">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('inventories.show.moving_slot', movingSlot) }}
                </h1>
            </template>

            <template #default>
                <div class="flex gap-5 items-center">
                    <div class="flex-shrink-0" v-html="t('inventories.show.move_from', getItemLabelForSlot(movingSlot, true))"></div>

                    <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" :value="name" disabled />
                    <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" :class="{ 'border-red-500': !movingTargetValid }" minlength="3" maxlength="255" v-model="movingTarget" :placeholder="name" />
                </div>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500" @click="isMoving = false">
                    {{ t('global.close') }}
                </button>
                <button type="button" class="px-5 py-2 rounded bg-lime-200 hover:bg-lime-300 dark:bg-lime-600 dark:hover:bg-lime-500" @click="saveMove()" v-if="movingTargetValid">
                    {{ t('inventories.show.move') }}
                </button>
            </template>
        </modal>

    </div>
</template>

<script>
import Layout from '../../Layouts/App.vue';
import VSection from '../../Components/Section.vue';
import Modal from './../../Components/Modal.vue';

export default {
    layout: Layout,
    components: {
        VSection,
        Modal
    },
    props: {
        name: {
            type: String,
            required: true,
        },
        contents: {
            type: Object,
            required: true,
        },
        items: {
            type: Object | Array,
            required: true,
        },
    },
    computed: {
        canEditItems() {
            return this.$page.auth.player.isSuperAdmin;
        },
        totalWeight() {
            let weight = 0;

            for (const slot in this.contents) {
                const items = this.contents[slot];
                const name = items.length ? items[0].name : null;
                const item = name ? this.items[name] : null;

                weight += item ? item.weight * items.length : 0;
            }

            return weight;
        },
        totalItems() {
            let items = 0;

            for (const slot in this.contents) {
                items += this.contents[slot].length;
            }

            return items;
        },
        editedItemNameValid() {
            if (!this.editingItem) return false;

            return this.editingItem in this.items;
        },
        editedItemStackable() {
            if (!this.editingItem) return false;

            const item = this.items[this.editingItem];

            return item?.stackable;
        },
        movingTargetValid() {
            if (!this.movingTarget) return false;

            const trimmed = this.movingTarget.trim().toLowerCase();

            return trimmed.match(/^\w+-.+/m) && this.name !== trimmed;
        }
    },
    data() {
        return {
            isLoading: false,

            isEditing: false,
            editingSlot: false,
            editingItem: false,
            editingAmount: 1,
            editingMetadata: [],

            isMoving: false,
            movingSlot: false,
            movingTarget: false,

            attachCharacterId: '',
            attachCharacterLoading: false
        };
    },
    methods: {
        iconFailed(event) {
            event.target.src = "/images/icons/items/other/unknown.png";
        },
        getItemIcon(item) {
            const metadata = item.metadata,
                iconUrl = metadata?.iconUrl;

            if (iconUrl) {
                if (iconUrl.startsWith("/variations")) {
                    return `/images/icons/items${iconUrl}`;
                } else if (iconUrl.startsWith("https://")) {
                    return iconUrl;
                } else if (iconUrl.startsWith("nui://op-framework/files/resources")) {
                    return `/images/icons/items/${iconUrl.replace("nui://op-framework/", "")}`;
                }
            }

            return `/images/icons/items/${item.name}.png`;
        },
        getItemLabelForSlot(slot, withAmount = false, noHtml = false) {
            const items = this.contents[slot];

            if (!items.length) return "&nbsp;";

            const item = items[0],
                itemName = item.name,
                itemMetadata = item.metadata,
                itemData = this.items[itemName];

            let label = itemData?.label || itemName;

            if (itemMetadata?.nameOverride) {
                label = itemMetadata.nameOverride;
            }

            if (itemMetadata?.battleRoyaleOnly) {
                label = `<span class="font-semibold">BR</span> ${label}`;
            } else if (itemMetadata?.jobName === "Law Enforcement") {
                label = `<span class="font-semibold">PD</span> ${label}`;
            } else if (itemMetadata?.jobName === "Medical") {
                label = `<span class="font-semibold">EMS</span> ${label}`;
            } else if (itemMetadata?.jobName === "Government") {
                label = `<span class="font-semibold">Gov</span> ${label}`;
            } else if (itemMetadata?.jobName === "Green Wonderland") {
                label = `<span class="font-semibold">WL</span> ${label}`;
            } else if (itemMetadata?.jobName === "Mechanic") {
                label = `<span class="font-semibold">Mech</span> ${label}`;
            }

            if (withAmount) {
                label = `${items.length}x ${label}`;
            }

            if (noHtml) {
                label = label.replace(`<span class="font-semibold">`, "").replace("</span>", "");
            }

            return label;
        },
        changedItemName() {
            if (this.editedItemStackable) return;

            this.editingAmount = 1;
        },
        async deleteItemSlot(slot) {
            if (this.isLoading || this.isEditing || this.isMoving || !confirm(this.t('inventories.show.delete_confirm'))) {
                return;
            }

            this.isLoading = true;

            await this.$inertia.delete(`/inventory/${this.name}/items/${slot}`);

            this.isLoading = false;
        },
        async saveEditing() {
            if (this.isLoading || !this.editingItem) return;

            this.editingItem = this.editingItem.trim().toLowerCase();

            if (!this.editingItem || !this.editingAmount || this.editingAmount <= 0 || this.editingAmount > 250) {
                return;
            }

            if (!this.editedItemStackable) {
                this.editingAmount = 1;
            }

            this.isLoading = true;

            const metadata = {};

            for (const entry of this.editingMetadata) {
                const key = entry.key;
                let value = entry.value;

                if (!this.isFieldValid(key, value)) {
                    this.isLoading = false;

                    alert(`Invalid field: ${key}`);

                    return;
                }

                if (typeof value === "string") {
                    value = value.trim();
                } else if (value === "true" || value === "false") {
                    value = value === "true";
                } else if (value.match(/^-?\d+$/)) {
                    value = Number.parseInt(value);
                } else if (value.match(/^-?\d+\.?\d*$/)) {
                    value = Number.parseFloat(value);
                } else {
                    value = null;
                }

                if (!key || (!value && value !== false)) continue;

                try {
                    metadata[key] = JSON.parse(value);
                } catch (e) {
                    metadata[key] = value;
                }
            }

            await this.$inertia.put(`/inventory/${this.name}/items/${this.editingSlot}`, {
                name: this.editingItem,
                amount: this.editingAmount,
                metadata: JSON.stringify(metadata)
            });

            this.isLoading = false;
            this.isEditing = false;
        },
        async saveMove() {
            if (this.isLoading || !this.isMoving || !this.movingTargetValid) return;

            this.isLoading = true;

            this.movingTarget = this.movingTarget.trim().toLowerCase();

            await this.$inertia.patch(`/inventory/${this.name}/items/${this.movingSlot}`, {
                target: this.movingTarget
            });

            this.isLoading = false;
            this.isMoving = false;
        },
        editItemSlot(slot, items) {
            if (this.isLoading || this.isEditing || this.isMoving) return;

            this.isEditing = true;

            this.editingSlot = slot;
            this.editingMetadata = [];

            this.newMetadataKey = '';
            this.newMetadataValue = '';

            this.attachCharacterId = '';

            if (items.length) {
                this.editingItem = items[0].name;
                this.editingAmount = items.length;

                for (const key in items[0].metadata) {
                    const value = items[0].metadata[key];

                    this.editingMetadata.push({
                        key: key,
                        value: typeof value === "object" ? JSON.stringify(value) : `${value}`
                    });
                }
            } else {
                this.editingItem = "";
                this.editingAmount = 1;
            }
        },
        moveItemSlot(slot) {
            if (this.isLoading || this.isEditing || this.isMoving) return;

            this.isMoving = true;
            this.movingSlot = slot;
            this.movingTarget = "";
        },
        removeMetadataKey(key) {
            if (this.isLoading || !this.isEditing) return;

            this.editingMetadata = this.editingMetadata.filter(item => item.key !== key);
        },
        addMetadataKey() {
            if (this.isLoading || !this.isEditing) return;

            this.editingMetadata.push({
                key: "",
                value: ""
            });
        },
        async loadAttachedCharacter() {
            const id = Number.parseInt(this.attachCharacterId);

            if (!id || id <= 0 || this.attachCharacterLoading) return;

            this.attachCharacterLoading = true;

            const slot = this.editingSlot;

            try {
                const data = await _get(`/inventory/attach_identity/${id}`);

                if (data?.status && this.isEditing && slot === this.editingSlot) {
                    for (const [key, value] of Object.entries(data.data)) {
                        this.editingMetadata.push({
                            key: key,
                            value: `${value}`
                        });
                    }
                }
            } catch { }

            this.attachCharacterLoading = false;
            this.attachCharacterId = "";
        },
        isFieldValid(key, value) {
            // Only common fields get validated
            switch (key) {
                // Needs to be an integer greater than 0
                case "degradesAt":
                case "characterId":
                case "durabilityPercent":
                case "ammoAmount":
                case "tint":
                case "remainingMessages":
                case "purchaseDate":
                case "unitId":
                case "impactAmount":
                case "casingAmount":
                case "hitsTaken":
                case "cigarettes":
                case "issueId":
                case "signedByCid":
                case "redeemLocationId":
                case "deviceId":
                case "deviceActivationTimestamp":
                case "kills":
                case "ranOver":
                case "packId":
                case "pieceNumber":
                case "deaths":
                case "serialNumber":
                case "firingMode":
                    return value?.match(/^\d+$/m) && Number.parseInt(value) >= 0;

                // Needs to be a float and greater than 0
                case "stepsWalked":
                    return value?.match(/^\d+(\.\d+)?$/m) && Number.parseFloat(value) >= 0;

                // Needs to be a boolean
                case "battleRoyaleOnly":
                case "noSerialNumber":
                case "gravityGun":
                case "reviewed":
                case "incomplete":
                case "fromLuckyWheel":
                    return value === "true" || value === "false";

                // Needs to be a valid URL (starting with http://, https:// or nui://) or "pending"
                case "iconUrl":
                case "screenshotURL":
                case "portraitURL":
                case "pictureURL":
                case "imageUrl":
                    return value && ((value.startsWith("http://") || value.startsWith("https://") || value.startsWith("nui://")) || value === "pending");

                // Needs to be an array
                case "attachments":
                case "numbers":
                case "prizes":
                    return value?.startsWith("[") && value.endsWith("]");

                // Needs to be an object
                case "ingredients":
                    return value?.startsWith("{") && value.endsWith("}");

                // Needs to be an array or object
                case "contents":
                    return value && (v(alue.startsWith("[") && value.endsWith("]")) || (value.startsWith("{") && value.endsWith("}")));
            }

            // Other fields are not validated
            return true;
        }
    }
};
</script>
