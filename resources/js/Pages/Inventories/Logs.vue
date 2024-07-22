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
				<i class="mr-1 fa fa-refresh"></i>
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
						<th class="p-3">{{ t('inventories.logs.items_moved') }}</th>
						<th class="p-3">{{ t('inventories.logs.from') }}</th>
						<th class="p-3">{{ t('inventories.logs.to') }}</th>
						<th class="p-3 pr-8 whitespace-nowrap">{{ t('inventories.logs.timestamp') }}</th>
					</tr>
					<tr class="border-t border-gray-300 dark:border-gray-500 relative" v-for="log in logs" :key="log.id">
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
						<td class="p-3 mobile:block">{{ movedItems(log.details) }}</td>
						<td class="p-3 mobile:block" v-html="fromInventory(log.details)"></td>
						<td class="p-3 mobile:block" v-html="toInventory(log.details)"></td>
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

	</div>
</template>

<script>
import Layout from './../../Layouts/App';
import VSection from './../../Components/Section';
import Pagination from './../../Components/Pagination';

export default {
	layout: Layout,
	components: {
		Pagination,
		VSection
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

			statusLoading: false,
			status: {}
		};
	},
	methods: {
		formatRawTimestamp(timestamp) {
			return this.$moment(timestamp).unix();
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
		movedItems(details) {
			const items = details.match(/(?<=moved )\d+x .+?(?= )/gi).pop();

			return items;
		},
		fromInventory(details) {
			const inventory = details.match(/(?<=from inventory )[\w-:]+/i).pop();

			return `<a title="${this.t('inventories.show_inv')}" class="text-indigo-600 dark:text-indigo-400 font-semibold" href="/inventory/${inventory.replace(/:\d+/, '')}">${inventory}</a>`;
		},
		toInventory(details) {
			const inventory = details.match(/(?<=to )[\w-:]+/i).pop();

			return `<a title="${this.t('inventories.show_inv')}" class="text-indigo-600 dark:text-indigo-400 font-semibold" href="/inventory/${inventory.replace(/:\d+/, '')}">${inventory}</a>`;
		},
		playerName(licenseIdentifier) {
			return licenseIdentifier in this.playerMap ? this.playerMap[licenseIdentifier] : licenseIdentifier;
		},
		async updateStatus() {
			this.statusLoading = true;

			const identifiers = this.logs.map(player => player.licenseIdentifier).filter((value, index, self) => self.indexOf(value) === index).join(",");

			if (identifiers) {
				this.status = (await this.requestData("/online/" + identifiers)) || {};
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
