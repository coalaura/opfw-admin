<template>
	<div>

		<portal to="title">
			<h1 class="dark:text-white">
				{{ t('inventories.logs.title') }}
			</h1>
			<p>
				{{ t('inventories.logs.description', name) }}
			</p>
		</portal>

		<portal to="actions">
			<button class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" type="button" @click="refresh">
				<i class="mr-1 fa fa-redo-alt"></i>
				{{ t('inventories.logs.refresh') }}
			</button>
		</portal>

		<!-- Table -->
		<v-section class="overflow-x-auto" :noHeader="true">
			<template>
				<table class="w-full">
					<tr class="font-semibold text-left mobile:hidden">
						<th class="p-3 pl-8 max-w-56">{{ t('inventories.logs.player') }}</th>
						<th class="p-3 whitespace-nowrap">{{ t('inventories.logs.server_id') }}</th>
						<th class="p-3">{{ t('inventories.logs.move_type') }}</th>
						<th class="p-3">{{ t('inventories.logs.items_moved') }}</th>
						<th class="p-3">{{ t('inventories.logs.from') }}</th>
						<th class="p-3">{{ t('inventories.logs.to') }}</th>
						<th class="p-3 pr-8 whitespace-nowrap">{{ t('inventories.logs.timestamp') }}</th>
					</tr>
					<tr class="border-t border-gray-300 dark:border-gray-500 relative" :class="moveTypeColor(log.action)" v-for="log in logs" :key="log.id">
						<td class="p-3 pl-8 mobile:block max-w-56">
							<inertia-link class="block px-4 py-2 truncate font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="'/players/' + log.licenseIdentifier" v-if="log.licenseIdentifier">
								{{ playerName(log.licenseIdentifier) }}
							</inertia-link>

							<div class="block px-4 py-2 truncate font-semibold text-center text-white bg-teal-600 rounded" v-else>{{ t('global.system') }}</div>
						</td>
						<td class="p-3 mobile:block whitespace-nowrap">
							<span class="font-semibold" v-if="statusLoading">
								{{ t('global.loading') }}
							</span>
							<span class="font-semibold" v-else-if="status[log.licenseIdentifier]">
								{{ status[log.licenseIdentifier].source }}
							</span>
							<span class="font-semibold" v-else>
								{{ t('global.status.offline') }}
							</span>
						</td>
						<td class="p-3 mobile:block font-semibold">
							{{ log.action.replace(/^Item\s+/m, "") }}
						</td>
						<td class="p-3 mobile:block">
							<span v-if="!log.metadata || !log.metadata.itemIds || log.metadata.itemIds.length === 0">{{ movedItems(log.details) }}</span>

							<a class="text-lime-600 dark:text-lime-400 font-semibold" :href="'/inventory/item/' + log.metadata.itemIds[0]" :title="t('inventories.logs.single_item', log.metadata.itemIds[0])" v-else-if="log.metadata.itemIds.length === 1">
								{{ movedItems(log.details) }}
							</a>

							<a class="text-teal-600 dark:text-teal-400 font-semibold" href="#" :title="t('inventories.logs.multiple_items', log.metadata.itemIds.length)" @click="selectItemForHistory($event, log.metadata.itemIds)" v-else>
								{{ movedItems(log.details) }}
							</a>
						</td>
						<td class="p-3 mobile:block" v-html="fromInventory(log.metadata, log.details)"></td>
						<td class="p-3 mobile:block" v-html="toInventory(log.metadata, log.details)"></td>
						<td class="p-3 pr-8 mobile:block whitespace-nowrap">
							{{ log.timestamp | formatTime(true) }}
							<i class="block text-xs leading-1 whitespace-nowrap text-yellow-600 dark:text-yellow-400">{{ formatRawTimestamp(log.timestamp) }}</i>
						</td>
					</tr>
					<tr v-if="logs.length === 0" class="border-t border-gray-300 dark:border-gray-500">
						<td class="py-3 px-8 text-center" colspan="100%">
							{{ t('inventories.logs.no_logs') }}
						</td>
					</tr>
				</table>
			</template>

			<template #footer>
				<div class="flex items-center justify-between mt-6 mb-1">

					<!-- Navigation -->
					<div class="flex flex-wrap">
						<inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="links.prev" v-if="page >= 2">
							<i class="mr-1 fas fa-arrow-left"></i>
							{{ t("pagination.previous") }}
						</inertia-link>
						<inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" v-if="logs.length === 30" :href="links.next">
							{{ t("pagination.next") }}
							<i class="ml-1 fas fa-arrow-right"></i>
						</inertia-link>
					</div>

					<!-- Meta -->
					<div class="font-semibold">
						{{ t("pagination.page", page) }}
					</div>

				</div>
			</template>
		</v-section>

		<modal :show.sync="isSelectingItem">
			<template #header>
				<h1 class="dark:text-white">
					{{ t('inventories.logs.item_history') }}
				</h1>
			</template>

			<template #default>
				<div class="grid grid-cols-6 gap-3 justify-evenly">
					<a class="px-2 py-0.5 cursor-pointer truncate shadow border-teal-300 bg-teal-200 dark:bg-teal-700 font-semibold" :href="'/inventory/item/' + itemId" :title="t('inventories.logs.single_item', itemId)" v-for="itemId in selectingItemIds" :key="itemId">
						#{{ itemId }}
					</a>
				</div>
			</template>

			<template #actions>
				<button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="isSelectingItem = false">
					{{ t('global.close') }}
				</button>
			</template>
		</modal>

	</div>
</template>

<script>
import Layout from './../../Layouts/App.vue';
import VSection from './../../Components/Section.vue';
import Pagination from './../../Components/Pagination.vue';
import Modal from './../../Components/Modal.vue';

export default {
	layout: Layout,
	components: {
		Pagination,
		VSection,
		Modal
	},
	props: {
		name: {
			type: String,
			required: true,
		},
		logs: {
			type: Array,
			required: true,
		},
		playerMap: {
			type: Object,
			required: true,
		},
		links: {
			type: Object,
			required: true,
		},
		page: {
			type: Number,
			required: true,
		}
	},
	data() {
		return {
			isLoading: false,

			isSelectingItem: false,
			selectingItemIds: [],

			statusLoading: false,
			status: {}
		};
	},
	methods: {
		formatRawTimestamp(timestamp) {
			return dayjs(timestamp).unix();
		},
		itemHistoryTitle(metadata) {
			const itemIds = metadata.itemIds;

			if (itemIds.length === 1) {
				return this.t('inventories.logs.single_item', itemIds[0]);
			}

			return this.t('inventories.logs.multiple_items', itemIds.length);
		},
		selectItemForHistory(event, itemIds) {
			event.preventDefault();

			this.isSelectingItem = true;
			this.selectingItemIds = itemIds.toSorted();
		},
		async refresh() {
			if (this.isLoading) {
				return;
			}

			this.isLoading = true;

			try {
				await this.$inertia.replace(window.location.href, {
					data: this.filters,
					preserveState: true,
					preserveScroll: true,
					only: ['logs', 'playerMap', 'links', 'page'],
				});

				await this.updateStatus();
			} catch (e) {
			}

			this.isLoading = false;
		},
		moveTypeColor(action) {
			switch (action) {
				case "Item Given":
					return 'bg-purple-500 !bg-opacity-20 hover:!bg-opacity-40';
				case "Item Moved":
					return 'bg-teal-500 !bg-opacity-20 hover:!bg-opacity-40';
			}

			return "";
		},
		movedItems(details) {
			const items = details.match(/(?<=moved |gave )\d+x .+?(?= to)/gi)?.shift() || "Unknown";

			return items;
		},
		fromInventory(metadata, details) {
			let inventory = metadata?.startInventory;

			if (!inventory) {
				inventory = details.match(/(?<=from inventory )\w+-[\w-:]+/i)?.shift();

				if (!inventory) {
					return "N/A";
				}
			}

			return `<a title="${this.t('inventories.show_inv')}" class="text-indigo-600 dark:text-indigo-400 font-semibold" href="/inventory/${inventory.replace(/:\d+/, '')}">${inventory}</a>`;
		},
		toInventory(metadata, details) {
			let inventory = metadata?.endInventory;

			if (!inventory) {
				inventory = details.match(/(?<=to )\w+-[\w-:]+/i)?.shift();

				if (!inventory) {
					return "N/A";
				}
			}

			return `<a title="${this.t('inventories.show_inv')}" class="text-indigo-600 dark:text-indigo-400 font-semibold" href="/inventory/${inventory.replace(/:\d+/, '')}">${inventory}</a>`;
		},
		playerName(licenseIdentifier) {
			return licenseIdentifier in this.playerMap ? this.playerMap[licenseIdentifier] : licenseIdentifier;
		},
		async updateStatus() {
			this.statusLoading = true;

			const identifiers = this.logs.map(player => player.licenseIdentifier).filter((value, index, self) => self.indexOf(value) === index).join(",");

			if (identifiers) {
				this.status = (await this.requestData(`/online/${identifiers}`)) || {};
			} else {
				this.status = {};
			}

			this.statusLoading = false;
		}
	},
	mounted() {
		this.updateStatus();

		$('body').on('mousedown', '.license_id', function (e) {
			// Middle mouse
			if (e.which !== 2) {
				return;
			}

			const license = $(this).attr('title');

			window.open(`/players/${license}`, '_blank');
		});
	}
};
</script>
