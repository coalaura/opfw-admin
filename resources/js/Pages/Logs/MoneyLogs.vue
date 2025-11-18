<template>
	<div>

		<portal to="title">
			<h1 class="dark:text-white">
				<i class="mr-3 fas fa-unlock-alt" :title="perm.restriction(perm.PERM_MONEY_LOGS)"></i>

				{{ t('logs.money_title') }}
			</h1>
			<p>
				{{ t('logs.money_description') }}
			</p>
		</portal>

		<portal to="actions">
			<button class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" type="button" @click="refresh">
				<i class="mr-1 fa fa-redo-alt"></i>
				{{ t('logs.refresh') }}
			</button>
		</portal>

		<!-- Querying -->
		<v-section :noFooter="true">
			<template #header>
				<h2>
					{{ t('logs.filter') }}
				</h2>
			</template>

			<template>
				<form @submit.prevent autocomplete="off">
					<input autocomplete="false" name="hidden" type="text" class="hidden" />

					<div class="flex flex-wrap mb-4">
						<!-- Type -->
						<div class="w-1/3 px-3 mobile:w-full mobile:mb-3 relative">
							<label class="block mb-2" for="type">
								{{ t('logs.type') }}
							</label>
							<select class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="type" v-model="filters.typ">
								<option value="">{{ t('logs.types.all') }}</option>
								<option value="cash">{{ t('logs.types.cash') }}</option>
								<option value="bank">{{ t('logs.types.bank') }}</option>
							</select>
						</div>
						<!-- Direction -->
						<div class="w-1/3 px-3 mobile:w-full mobile:mb-3 relative">
							<label class="block mb-2" for="direction">
								{{ t('logs.direction') }}
							</label>
							<select class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="direction" v-model="filters.direction">
								<option value="">{{ t('logs.directions.all') }}</option>
								<option value="in">{{ t('logs.directions.in') }}</option>
								<option value="out">{{ t('logs.directions.out') }}</option>
							</select>
						</div>
						<!-- Amount -->
						<div class="w-1/3 px-3 mobile:w-full mobile:mb-3">
							<label class="block mb-2" for="amount">
								{{ t('logs.amount') }} <sup class="text-muted dark:text-dark-muted">*</sup>
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="amount" placeholder=">8000" v-model="filters.amount">
						</div>

						<!-- Identifier -->
						<div class="w-1/3 px-3 mobile:w-full mobile:mb-3">
							<label class="block mb-2 mt-3" for="identifier">
								{{ t('logs.identifier') }} <sup class="text-muted dark:text-dark-muted">*</sup>
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="identifier" placeholder="license:2ced2cabd90f1208e7e056485d4704c7e1284196" v-model="filters.identifier">
						</div>
						<!-- Character ID -->
						<div class="w-1/3 px-3 mobile:w-full mobile:mb-3">
							<label class="block mb-2 mt-3" for="character_id">
								{{ t('logs.character_id') }} <sup class="text-muted dark:text-dark-muted">*</sup>
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" type="number" min="0" id="character_id" placeholder="1628" v-model="filters.character_id">
						</div>
						<!-- Details -->
						<div class="w-1/3 px-3">
							<label class="block mb-3 mt-3" for="details">
								{{ t('logs.details') }} <sup class="text-muted dark:text-dark-muted">*</sup>
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="details" placeholder="garage-retrieval" v-model="filters.details">
						</div>

						<!-- After Date -->
						<div class="w-1/4 px-3 pr-1 mobile:w-full mobile:mb-3">
							<label class="block mb-3 mt-3" for="after-date">
								{{ t('logs.after-date') }}
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="after-date" type="date" placeholder="">
						</div>
						<!-- After Time -->
						<div class="w-1/4 px-3 pl-1 mobile:w-full mobile:mb-3">
							<label class="block mb-3 mt-3" for="after-time">
								{{ t('logs.after-time') }}
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="after-time" type="time" placeholder="">
						</div>
						<!-- Before Date -->
						<div class="w-1/4 px-3 pr-1 mobile:w-full mobile:mb-3">
							<label class="block mb-3 mt-3" for="before-date">
								{{ t('logs.before-date') }}
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="before-date" type="date" placeholder="">
						</div>
						<!-- Before Time -->
						<div class="w-1/4 px-3 pl-1 mobile:w-full mobile:mb-3">
							<label class="block mb-3 mt-3" for="before-time">
								{{ t('logs.before-time') }}
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="before-time" type="time" placeholder="">
						</div>
					</div>

					<!-- Description -->
					<div class="w-full px-3 mt-3">
						<small class="text-muted dark:text-dark-muted mt-1 leading-4 block" v-html="t('global.search.custom')"></small>
					</div>

					<!-- Search button -->
					<div class="w-full px-3 mt-3">
						<button class="px-5 py-2 font-semibold text-white bg-success dark:bg-dark-success rounded hover:shadow-lg" @click="refresh">
							<span v-if="!isLoading">
								<i class="fas fa-search"></i>
								{{ t('logs.search') }}
							</span>
							<span v-else>
								<i class="fas fa-cog animate-spin"></i>
								{{ t('global.loading') }}
							</span>
						</button>
					</div>
				</form>
			</template>
		</v-section>

		<!-- Table -->
		<v-section class="overflow-x-auto">
			<template #header>
				<h2>
					{{ t('logs.logs') }}
				</h2>
				<p class="text-muted dark:text-dark-muted text-xs">
					{{ t('global.results', time) }}
				</p>
			</template>

			<template>
				<table class="w-full">
					<tr class="font-semibold text-left mobile:hidden">
						<th class="p-3 pl-8 max-w-56">{{ t('logs.player') }}</th>
						<th class="p-3 max-w-56">{{ t('logs.character') }}</th>
						<th class="p-3">{{ t('logs.type') }}</th>
						<th class="p-3">{{ t('logs.amount') }}</th>
						<th class="p-3">{{ t('logs.details') }}</th>
						<th class="p-3 pr-8">
							{{ t('logs.timestamp') }}
						</th>
					</tr>
					<tr class="border-t border-gray-300 dark:border-gray-500 relative" v-for="log in logs" :key="log.id" :class="getLogColor(log)">
						<td class="p-3 pl-8 mobile:block max-w-56">
							<inertia-link class="block px-4 py-2 truncate font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="'/players/' + log.licenseIdentifier">
								{{ log.playerName }}
							</inertia-link>
						</td>
						<td class="p-3 mobile:block max-w-56">
							<inertia-link class="block px-4 py-2 truncate font-semibold text-center text-white bg-blue-600 rounded dark:bg-blue-400" :href="'/players/' + log.licenseIdentifier + '/characters/' + log.characterId">
								{{ log.characterName }}
							</inertia-link>
						</td>
						<td class="p-3 mobile:block whitespace-nowrap">{{ log.type }}</td>
						<td class="p-3 mobile:block font-mono">
							<div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
								<span>{{ numberFormat(log.balanceBefore, 0, true) }}</span>

								<span :class="log.amount > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" class="font-semibold">
									{{ log.amount > 0 ? '+' : '-' }}
									{{ numberFormat(Math.abs(log.amount), 0, true) }}
								</span>

								<span class="font-semibold">=</span>
								<span>{{ numberFormat(log.balanceAfter, 0, true) }}</span>
							</div>
						</td>
						<td class="p-3 mobile:block">{{ log.details ? log.details : '-' }}</td>
						<td class="p-3 pr-8 mobile:block whitespace-nowrap">
							{{ log.timestamp | formatTime(true) }}

							<i class="block text-xs leading-1 whitespace-nowrap text-yellow-600 dark:text-yellow-400">{{ formatRawTimestamp(log.timestamp) }}</i>
						</td>
					</tr>
					<tr v-if="logs.length === 0" class="border-t border-gray-300 dark:border-gray-500">
						<td class="py-3 px-8 text-center" colspan="100%">
							{{ t('logs.no_logs') }}
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
import Layout from './../../Layouts/App.vue';
import VSection from './../../Components/Section.vue';
import Pagination from './../../Components/Pagination.vue';

export default {
	layout: Layout,
	components: {
		Pagination,
		VSection
	},
	props: {
		logs: {
			type: Array,
			required: true,
		},
		filters: {
			typ: String,
			direction: String,
			amount: String,
			identifier: String,
			character_id: String,
			details: String,
			before: Number,
			after: Number,
		},
		links: {
			type: Object,
			required: true,
		},
		page: {
			type: Number,
			required: true,
		},
		time: {
			type: Number,
			required: true,
		},
	},
	data() {
		return {
			isLoading: false,
			showLogTimeDifference: false
		};
	},
	methods: {
		getLogColor(log) {
			if (this.setting('parseLogs')) {
				switch(log.type) {
					case 'cash':
						return '!bg-purple-500 !bg-opacity-10 hover:!bg-opacity-20';
					case 'bank':
						return '!bg-blue-500 !bg-opacity-10 hover:!bg-opacity-20';
				}
			}

			return '!bg-gray-500 !bg-opacity-10 hover:!bg-opacity-20';
		},
		formatRawTimestamp(timestamp) {
			return dayjs(timestamp).unix();
		},
		async refresh() {
			if (this.isLoading) {
				return;
			}

			this.isLoading = true;
			try {
				const beforeDate = $('#before-date').val();
				const beforeTime = $('#before-time').val();
				const afterDate = $('#after-date').val();
				const afterTime = $('#after-time').val();

				if (beforeDate && beforeTime) {
					this.filters.before = Math.round((new Date(`${beforeDate} ${beforeTime}`)).getTime() / 1000);

					if (Number.isNaN(this.filters.before)) {
						this.filters.before = null;
					}
				}

				if (afterDate && afterTime) {
					this.filters.after = Math.round((new Date(`${afterDate} ${afterTime}`)).getTime() / 1000);

					if (Number.isNaN(this.filters.after)) {
						this.filters.after = null;
					}
				}

				await this.$inertia.replace('/money_logs', {
					data: this.filters,
					preserveState: true,
					preserveScroll: true,
					only: ['logs', 'time', 'links', 'page', 'filters'],
				});

				await this.updateStatus();
			} catch (e) {
			}

			this.isLoading = false;
		},
	},
	mounted() {
		if (this.filters.before) {
			const d = new Date(this.filters.before * 1000);

			$('#before-date').val(`${d.getFullYear()}-${(`${d.getMonth() + 1}`).padStart(2, '0')}-${(`${d.getDate()}`).padStart(2, '0')}`);
			$('#before-time').val(`${d.getHours()}:${d.getMinutes()}`);
		}
		if (this.filters.after) {
			const d = new Date(this.filters.after * 1000);

			$('#after-date').val(`${d.getFullYear()}-${(`${d.getMonth() + 1}`).padStart(2, '0')}-${(`${d.getDate()}`).padStart(2, '0')}`);
			$('#after-time').val(`${d.getHours()}:${d.getMinutes()}`);
		}
	}
};
</script>
