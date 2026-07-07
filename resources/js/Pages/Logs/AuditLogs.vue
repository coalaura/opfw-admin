<template>
	<div>

		<portal to="title">
			<h1 class="dark:text-white">
				<i class="mr-3 fas fa-unlock-alt" :title="perm.restriction(perm.PERM_VIEW_AUDIT_LOGS)"></i>
				{{ t('audit_logs.title') }}
			</h1>
			<p>
				{{ t('audit_logs.description') }}
			</p>
		</portal>

		<portal to="actions">
			<button class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" type="button" @click="refresh">
				<i class="mr-1 fa fa-redo-alt"></i>
				{{ t('audit_logs.refresh') }}
			</button>
		</portal>

		<!-- Querying -->
		<v-section :noFooter="true">
			<template #header>
				<h2>
					{{ t('audit_logs.filter') }}
				</h2>
			</template>

			<template>
				<form @submit.prevent autocomplete="off">
					<input autocomplete="false" name="hidden" type="text" class="hidden" />

				<div class="flex flex-wrap mb-4">
					<!-- License -->
					<div class="w-1/5 px-3 mobile:w-full mobile:mb-3">
						<label class="block mb-2" for="license">
							{{ t('audit_logs.license') }} <sup class="text-muted dark:text-dark-muted">*</sup>
						</label>
						<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="license" placeholder="license:2ced2cabd90f1208e7e056485d4704c7e1284196" v-model="filters.license" :title="previewQuery(filters.license)">
					</div>
					<!-- Action -->
					<div class="w-1/5 px-3 mobile:w-full mobile:mb-3">
						<label class="block mb-2" for="action">
							{{ t('audit_logs.action') }} <sup class="text-muted dark:text-dark-muted">*</sup>
						</label>
						<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="action" :placeholder="t('audit_logs.placeholder_action')" v-model="filters.action" :title="previewQuery(filters.action)">
					</div>
					<!-- Target Type -->
					<div class="w-1/5 px-3 mobile:w-full mobile:mb-3">
						<label class="block mb-2" for="target_type">
							{{ t('audit_logs.target_type') }}
						</label>
						<select class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="target_type" v-model="filters.target_type">
							<option value="">{{ t('audit_logs.any_target_type') }}</option>
							<option v-for="type in targetTypes" :key="type" :value="type">{{ type }}</option>
						</select>
					</div>
					<!-- Target Id -->
					<div class="w-1/5 px-3 mobile:w-full mobile:mb-3">
						<label class="block mb-2" for="target_id">
							{{ t('audit_logs.target_id') }} <sup class="text-muted dark:text-dark-muted">*</sup>
						</label>
						<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="target_id" :placeholder="t('audit_logs.placeholder_target_id')" v-model="filters.target_id" :title="previewQuery(filters.target_id)">
					</div>
					<!-- Details -->
					<div class="w-1/5 px-3 mobile:w-full mobile:mb-3">
						<label class="block mb-2" for="details">
							{{ t('audit_logs.details') }} <sup class="text-muted dark:text-dark-muted">*</sup>
						</label>
						<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="details" :placeholder="t('audit_logs.placeholder_details')" v-model="filters.details" :title="previewQuery(filters.details)">
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
								{{ t('audit_logs.search') }}
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
					{{ t('audit_logs.logs') }}
				</h2>
				<p class="text-muted dark:text-dark-muted text-xs">
					{{ t('global.results', time) }}
				</p>
			</template>

			<template>
				<table class="w-full">
					<tr class="font-semibold text-left mobile:hidden">
						<th class="p-3 pl-8 max-w-56">{{ t('audit_logs.player') }}</th>
						<th class="p-3">{{ t('audit_logs.action') }}</th>
						<th class="p-3">{{ t('audit_logs.target') }}</th>
						<th class="p-3">{{ t('audit_logs.details') }}</th>
						<th class="p-3 pr-8 whitespace-nowrap">
							<div class="flex gap-3 items-center">
								{{ t('audit_logs.timestamp') }}

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
						<div class="flex items-center gap-2" v-if="log.license">
							<status-tag :status="status[log.license]" :loading="statusLoading" />
							<inertia-link class="font-semibold text-indigo-700 dark:text-indigo-300 hover:underline truncate" :href="'/players/' + log.license">
								{{ playerName(log.license) }}
							</inertia-link>
						</div>

						<div class="font-semibold text-teal-600 dark:text-teal-400" v-else>{{ t('global.system') }}</div>
					</td>
						<td class="p-3 mobile:block whitespace-nowrap">
							{{ log.action }}
							<a href="#" @click="showMetadata($event, log)" class="block text-xs leading-1 text-blue-700 dark:text-blue-300 whitespace-nowrap" v-if="log.metadata">
								{{ t('logs.metadata.show') }}
							</a>
						</td>
						<td class="p-3 mobile:block whitespace-nowrap text-sm">
							<div v-if="log.targetType">
								<span class="font-semibold">{{ log.targetType }}</span>
								<div class="text-xs text-muted dark:text-dark-muted break-all">{{ log.targetId || '—' }}</div>
							</div>
							<div v-else class="text-muted dark:text-dark-muted">—</div>
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
						<td class="py-3 px-8 text-center" colspan="5">
							{{ t('audit_logs.no_logs') }}
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
import StatusTag from './../../Components/StatusTag.vue';

export default {
	layout: Layout,
	components: {
		Pagination,
		Modal,
		VSection,
		MetadataViewer,
		StatusTag
	},
	props: {
		logs: {
			type: Array,
			required: true,
		},
		filters: {
			license: String,
			action: String,
			target_type: String,
			target_id: String,
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
		targetTypes: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			isLoading: false,

			logTimezone: false,

			showLogMetadata: false,
			logMetadata: null,

			statusLoading: false,
			status: {}
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
		formatRawTimestamp(timestamp) {
			return dayjs(timestamp).unix();
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

				await this.$inertia.replace('/audit_logs', {
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
		},
		async loadStatus() {
			if (this.statusLoading) return;

			this.statusLoading = true;

			const identifiers = this.logs.map(log => log.license).filter(Boolean).join(',');

			if (identifiers) {
				this.status = await this.requestData(`/online/${identifiers}`) || {};
			} else {
				this.status = {};
			}

			this.statusLoading = false;
		}
	},
	watch: {
		logs() {
			this.loadStatus();
		}
	},
	mounted() {
		this.loadStatus();

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
