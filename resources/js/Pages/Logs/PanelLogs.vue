<template>
	<div>

		<portal to="title">
			<h1 class="dark:text-white">
				{{ t('panel_logs.title') }}
			</h1>
			<p>
				{{ t('panel_logs.description') }}
			</p>
		</portal>

		<portal to="actions">
			<button class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" type="button" @click="refresh">
				<i class="mr-1 fa fa-redo-alt"></i>
				{{ t('panel_logs.refresh') }}
			</button>
		</portal>

		<!-- Querying -->
		<v-section :noFooter="true">
			<template #header>
				<h2>
					{{ t('panel_logs.filter') }}
				</h2>
			</template>

			<template>
				<form @submit.prevent autocomplete="off">
					<input autocomplete="false" name="hidden" type="text" class="hidden" />

					<div class="flex flex-wrap mb-4">
						<!-- Identifier -->
						<div class="w-1/3 px-3 mobile:w-full mobile:mb-3">
							<label class="block mb-2" for="identifier">
								{{ t('logs.identifier') }} <sup class="text-muted dark:text-dark-muted">*</sup>
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="identifier" placeholder="license:2ced2cabd90f1208e7e056485d4704c7e1284196" v-model="filters.identifier" :title="previewQuery(filters.identifier)">
						</div>
						<!-- Action -->
						<div class="w-1/3 px-3 mobile:w-full mobile:mb-3 relative">
							<label class="block mb-2" for="action">
								{{ t('logs.action') }} <sup class="text-muted dark:text-dark-muted">*</sup>
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="action" :placeholder="t('panel_logs.placeholder_action')" v-model="filters.action" @keyup="searchActions()" @blur="cancelActionSearch()" @focus="searchActions()" :title="previewQuery(filters.action)">
							<div class="w-full absolute top-full left-0 px-3 z-10" v-if="searchingActions && searchableActions.length > 0">
								<div class="max-h-40 overflow-y-auto rounded-b border">
									<button class="block text-left w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 transition duration-200 hover:bg-gray-300" :class="{ 'border-b': index < searchableActions.length - 1 }" v-for="(action, index) in searchableActions" @click="selectAction('=' + action)">
										{{ action }}
									</button>
								</div>
							</div>
						</div>
						<!-- Details -->
						<div class="w-1/3 px-3">
							<label class="block mb-2" for="details">
								{{ t('logs.details') }} <sup class="text-muted dark:text-dark-muted">*</sup>
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="details" :placeholder="t('panel_logs.placeholder_details')" v-model="filters.details" :title="previewQuery(filters.details)">
						</div>

						<!-- After Date -->
						<div class="w-1/4 px-3 pr-1 mobile:w-full mobile:mb-3 mt-3">
							<label class="block mb-2" for="after-date">
								{{ t('logs.after-date') }}
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="after-date" type="date" placeholder="">
						</div>
						<!-- After Time -->
						<div class="w-1/4 px-3 pl-1 mobile:w-full mobile:mb-3 mt-3">
							<label class="block mb-2" for="after-time">
								{{ t('logs.after-time') }}
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="after-time" type="time" placeholder="">
						</div>
						<!-- Before Date -->
						<div class="w-1/4 px-3 pr-1 mobile:w-full mobile:mb-3 mt-3">
							<label class="block mb-2" for="before-date">
								{{ t('logs.before-date') }}
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="before-date" type="date" placeholder="">
						</div>
						<!-- Before Time -->
						<div class="w-1/4 px-3 pl-1 mobile:w-full mobile:mb-3 mt-3">
							<label class="block mb-2" for="before-time">
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
								{{ t('panel_logs.search') }}
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
					{{ t('panel_logs.logs') }}
				</h2>
				<p class="text-muted dark:text-dark-muted text-xs">
					{{ t('global.results', time) }}
				</p>
			</template>

			<template>
				<table class="w-full">
					<tr class="font-semibold text-left mobile:hidden">
						<th class="p-3 pl-8 max-w-56">{{ t('panel_logs.player') }}</th>
						<th class="p-3">{{ t('panel_logs.action') }}</th>
						<th class="p-3">{{ t('panel_logs.details') }}</th>
						<th class="p-3 pr-8 whitespace-nowrap">
							<div class="flex gap-3 items-center">
								{{ t('panel_logs.timestamp') }}

								<select class="block px-2 py-0.5 bg-gray-200 dark:bg-gray-600 border rounded w-28" v-model="logTimezone">
									<option :value="false" selected>Default</option>
									<option value="UTC">UTC (Universal)</option>
									<option value="Europe/London">GMT/BST (British)</option>
									<option value="America/New_York">EST/EDT (Eastern)</option>
									<option value="America/Chicago">CST/CDT (Central)</option>
									<option value="America/Denver">MST/MDT (Mountain)</option>
									<option value="America/Los_Angeles">PST/PDT (Pacific)</option>
									<option value="Europe/Paris">CET/CEST (Central European)</option>
									<option value="Asia/Tokyo">JST (Japan Standard Time)</option>
									<option value="Australia/Sydney">AEST/AEDT (Australian Eastern)</option>
								</select>
							</div>
						</th>
					</tr>
					<tr class="border-t border-gray-300 dark:border-gray-500 relative" v-for="(log, index) in logs" :key="log.id">
						<td class="p-3 pl-8 mobile:block max-w-56">
							<inertia-link class="block px-4 py-2 truncate font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="'/players/' + log.licenseIdentifier" v-if="log.licenseIdentifier">
								{{ playerName(log.licenseIdentifier) }}
							</inertia-link>

							<div class="block px-4 py-2 truncate font-semibold text-center text-white bg-teal-600 rounded" v-else>{{ t('global.system') }}</div>
						</td>
						<td class="p-3 mobile:block whitespace-nowrap">
							{{ log.action }}
							<a href="#" @click="showMetadata($event, log)" class="block text-xs leading-1 text-blue-700 dark:text-blue-300 whitespace-nowrap" v-if="log.metadata">
								{{ t('logs.metadata.show') }}
							</a>
						</td>
						<td class="p-3 mobile:block" v-html="parseLog(log.details, log.action, log.metadata)"></td>
						<td class="p-3 pr-8 mobile:block whitespace-nowrap">
							{{ formatTimestampWithTimezone(log.timestamp) }}
							<div class="block text-xs leading-1 whitespace-nowrap">
								<i class="text-yellow-600 dark:text-yellow-400">{{ formatRawTimestamp(log.timestamp) }}</i>
								-
								<i class="text-gray-700 dark:text-gray-300">{{ selectedTimezone }}</i>
							</div>
						</td>
					</tr>
					<tr v-if="logs.length === 0" class="border-t border-gray-300 dark:border-gray-500">
						<td class="py-3 px-8 text-center" colspan="4">
							{{ t('panel_logs.no_logs') }}
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

		<metadataViewer :title="t('players.show.anti_cheat_metadata')" :metadata="logMetadata" :show.sync="showLogMetadata" />

	</div>
</template>

<script>
import Layout from './../../Layouts/App.vue';
import VSection from './../../Components/Section.vue';
import Pagination from './../../Components/Pagination.vue';
import Modal from './../../Components/Modal.vue';
import MetadataViewer from './../../Components/MetadataViewer.vue';

export default {
	layout: Layout,
	components: {
		Pagination,
		Modal,
		VSection,
		MetadataViewer
	},
	props: {
		logs: {
			type: Array,
			required: true,
		},
		filters: {
			identifier: String,
			action: String,
			details: String,
			before: Number,
			after: Number,
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
		},
		time: {
			type: Number,
			required: true,
		},
		actions: {
			type: Array
		},
	},
	data() {
		return {
			isLoading: false,

			searchingActions: false,
			searchableActions: [],
			searchTimeout: false,

			logTimezone: false,

			showLogMetadata: false,
			logMetadata: null
		};
	},
	computed: {
		selectedTimezone() {
			if (this.logTimezone) {
				return dayjs.tz(this.logTimezone).zoneName();
			}

			return dayjs.tz.guess();
		}
	},
	methods: {
		formatTimestampWithTimezone(timestamp) {
			const date = dayjs(timestamp);

			if (this.logTimezone) {
				return date.tz(this.logTimezone).format('MMM D, YYYY h:mm:ss A');
			}

			return date.format('MMM D, YYYY h:mm:ss A');
		},
		selectAction(action) {
			clearTimeout(this.searchTimeout);

			this.filters.action = action;
			this.searchingActions = false;
		},
		cancelActionSearch() {
			clearTimeout(this.searchTimeout);

			this.searchTimeout = setTimeout(() => {
				this.searchingActions = false;
			}, 250);
		},
		searchActions() {
			clearTimeout(this.searchTimeout);

			let search = this.filters.action ? this.filters.action.trim().toLowerCase() : '';

			search = search.startsWith('=') ? search.substring(1) : search;

			if (search === '') {
				this.searchingActions = false;

				return;
			}

			const actions = this.actions ? this.actions.filter(action => action.toLowerCase().includes(search)) : [];

			actions.sort((a, b) => {
				return b.count - a.count;
			});

			this.searchableActions = actions;
			this.searchingActions = true;
		},
		formatRawTimestamp(timestamp) {
			return dayjs(timestamp).unix();
		},
		stamp(time) {
			return dayjs.utc(time).unix();
		},
		showMetadata(e, log) {
			e.preventDefault();

            this.showLogMetadata = true;
            this.logMetadata = log.metadata || {};
		},
		async refresh() {
			if (this.isLoading) {
				return;
			}

			this.isLoading = true;

			try {
				const beforeDate = $('#before-date').val();
				const beforeTime = $('#before-time').val() || '00:00';
				const afterDate = $('#after-date').val();
				const afterTime = $('#after-time').val() || '23:59';

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

				await this.$inertia.replace('/panel', {
					data: this.filters,
					preserveState: true,
					preserveScroll: true,
					only: ['logs', 'playerMap', 'time', 'links', 'page'],
				});
			} catch (e) {
			}

			this.isLoading = false;
		},
		parseLog(details, action, metadata) {
			details = this.escapeHtml(details);

			if (!this.setting('parseLogs')) return details;

			details = details.replace(/(license:\w+)(?=\))/gm, pMatch => {
				const start = pMatch.substring(8, 12);
				const end = pMatch.substring(pMatch.length - 4);

				return `<span class="copy_title text-gray-700 dark:text-gray-300 cursor-pointer license_id" title="${pMatch}">${start}...${end}</span>`;
			});

			// Small fix for idiots with backticks in their name
			details = details.replace(/^.+?\[\d+]/gm, pMatch => {
				return pMatch.replace(/[`]+/g, "");
			});

			details = details.replace(/`(.+?)`/gm, (pMatch, pCode) => {
				return `<code class="select-all bg-black !bg-opacity-10 dark:!bg-opacity-20 font-mono px-1.5 py-0.5">${pCode}</code>`;
			});

			return details;
		},
		playerName(licenseIdentifier) {
			return licenseIdentifier in this.playerMap ? this.playerMap[licenseIdentifier] : licenseIdentifier;
		}
	},
	mounted() {
		const _this = this;

		$('body').on('click', '.copy_title', function (e) {
			const title = $(this).attr('title');
			const timeout = $(this).data('timeout');
			const original = $(this).data('original') || $(this).text();

			clearTimeout(timeout);

			_this.copyToClipboard(title);

			$(this).data('original', original);

			$(this).text('Copied!');

			$(this).data('timeout', setTimeout(() => {
				$(this).text(original);
			}, 2000));
		});

		$('body').on('mousedown', '.license_id', function (e) {
			// Middle mouse
			if (e.which !== 2) {
				return;
			}

			const license = $(this).attr('title');

			window.open(`/players/${license}`, '_blank');
		});

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
