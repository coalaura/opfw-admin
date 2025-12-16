<template>
	<div>

		<portal to="title">
			<h1 class="dark:text-white">
				{{ t('logs.logs') }}
			</h1>
			<p>
				{{ t('logs.description') }}
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
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="action" :placeholder="t('logs.placeholder_action')" v-model="filters.action" @keyup="searchActions()" @blur="cancelActionSearch()" @focus="searchActions()" :title="previewQuery(filters.action)">
							<div class="w-full absolute top-full left-0 px-3 z-10" v-if="searchingActions && searchableActions.length > 0">
								<div class="max-h-40 overflow-y-auto rounded-b border">
									<button class="block text-left w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 transition duration-200 hover:bg-gray-300" :class="{ 'border-b': index < searchableActions.length - 1 }" v-for="(action, index) in searchableActions" @click="selectAction('=' + action.action)">
										{{ action.action }}
										<sup class="text-muted dark:text-dark-muted">{{
											numberFormat(action.count, 0, false)
										}}</sup>
									</button>
								</div>
							</div>
						</div>
						<!-- Details -->
						<div class="w-1/3 px-3">
							<label class="block mb-2" for="details">
								{{ t('logs.details') }} <sup class="text-muted dark:text-dark-muted">*</sup>
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="details" :placeholder="t('logs.placeholder_details')" v-model="filters.details" :title="previewQuery(filters.details)">
						</div>

						<!-- Server -->
						<div class="w-1/6 px-3 mobile:w-full mobile:mb-3 mt-3">
							<label class="block mb-2" for="server">
								{{ t('logs.server_id') }} <sup class="text-muted dark:text-dark-muted">*</sup>
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="server" placeholder="3" v-model="filters.server" :title="previewQuery(filters.server)">
						</div>
						<!-- Minigames -->
						<div class="w-1/6 px-3 mobile:w-full mobile:mb-3 mt-3">
							<label class="block mb-2" for="minigames">
								{{ t('logs.minigames') }}
							</label>
							<select class="w-full px-4 py-3 bg-gray-200 dark:bg-gray-600 border rounded" id="minigames" v-model="filters.minigames">
								<option :value="null">{{ t('global.all') }}</option>
								<option value="none">{{ t('logs.minigame_none') }}</option>
							</select>
						</div>
						<!-- After Date -->
						<div class="w-1/6 px-3 pr-1 mobile:w-full mobile:mb-3 mt-3">
							<label class="block mb-2" for="after-date">
								{{ t('logs.after-date') }}
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="after-date" type="date" placeholder="">
						</div>
						<!-- After Time -->
						<div class="w-1/6 px-3 pl-1 mobile:w-full mobile:mb-3 mt-3">
							<label class="block mb-2" for="after-time">
								{{ t('logs.after-time') }}
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="after-time" type="time" placeholder="">
						</div>
						<!-- Before Date -->
						<div class="w-1/6 px-3 pr-1 mobile:w-full mobile:mb-3 mt-3">
							<label class="block mb-2" for="before-date">
								{{ t('logs.before-date') }}
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="before-date" type="date" placeholder="">
						</div>
						<!-- Before Time -->
						<div class="w-1/6 px-3 pl-1 mobile:w-full mobile:mb-3 mt-3">
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
								{{ t('logs.search') }}
							</span>
							<span v-else>
								<i class="fas fa-cog animate-spin"></i>
								{{ t('global.loading') }}
							</span>
						</button>

						<button class="px-5 py-2 ml-5 font-semibold text-white bg-teal-600 dark:bg-teal-500 rounded hover:shadow-lg" @click="showMoneyLogs">
							<i class="fas fa-money-bill-wave mr-1"></i>
							{{ t('logs.money_search') }}
						</button>

						<button class="px-5 py-2 ml-5 font-semibold text-white bg-lime-600 dark:bg-lime-500 rounded hover:shadow-lg" @click="showConnectLogs">
							<i class="fas fa-person-booth mr-1"></i>
							{{ t('logs.connect_search') }}
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

				<div class="text-xs italic mt-1" v-if="skipped && skipped.length > 0">
					{{ t('logs.skipped') }}

					<li v-for="skip in skipped" class="list-inside pl-1 list-dash">
						{{ skip }}
					</li>
				</div>
			</template>

			<template>
				<table class="w-full">
					<tr class="font-semibold text-left mobile:hidden">
						<th class="p-3 pl-8 max-w-56">{{ t('logs.player') }}</th>
						<th class="p-3 whitespace-nowrap">{{ t('logs.server_id') }}</th>
						<th class="p-3">{{ t('logs.action') }}</th>
						<th class="p-3">{{ t('logs.details') }}</th>
						<th class="p-3 pr-8 whitespace-nowrap">
							<div class="flex gap-3 items-center">
								{{ t('logs.timestamp') }}

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
					<tr class="border-t border-gray-300 dark:border-gray-500 relative" :class="getLogColor(log.action, log.metadata)" v-for="(log, index) in logs" :key="log.id">
						<td class="p-3 pl-8 mobile:block max-w-56">
							<div class="absolute top-1 left-1 text-sm leading-3 font-semibold italic" v-html="getLogTag(log.action, log.metadata)"></div>

							<inertia-link class="block px-4 py-2 truncate font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="'/players/' + log.licenseIdentifier" v-if="log.licenseIdentifier">
								{{ playerName(log.licenseIdentifier) }}
							</inertia-link>

							<div class="block px-4 py-2 truncate font-semibold text-center text-white bg-teal-600 rounded" v-else>{{ t('global.system') }}</div>
						</td>
						<td class="p-3 mobile:block whitespace-nowrap">
							<span class="font-semibold" v-if="log.status && log.status.status === 'online'">
								{{ log.status.serverId }}
							</span>
							<span class="font-semibold" v-else>
								{{ t('global.status.offline') }}
							</span>
						</td>
						<td class="p-3 mobile:block whitespace-nowrap">
							{{ log.action }}
							<a href="#" @click="detailedAction($event, log)" class="block text-xs leading-1 text-blue-700 dark:text-blue-300 whitespace-nowrap" v-if="log.metadata">
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

		<modal :show.sync="showLogDetail">
			<template #header>
				<h1 class="dark:text-white">
					{{ t('logs.detail.title') }}
				</h1>
				<p class="dark:text-dark-muted !-mt-3 italic">
					{{ t('logs.detail.description', log_detail.user) }}
				</p>
			</template>

			<template #default>
				<pre class="text-lg block mb-2 whitespace-pre-wrap" v-html="log_detail.reason"></pre>
				{{ log_detail.description }}

				<a href="/docs/disconnect_reasons" target="_blank" class="block mt-3 text-indigo-600 dark:text-indigo-300 hover:text-yellow-500 dark:hover:text-yellow-300 text-sm italic">
					{{ t('logs.detail.read_more') }}
				</a>
			</template>

			<template #actions>
				<button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="showLogDetail = false">
					{{ t('global.close') }}
				</button>
			</template>
		</modal>

		<metadataViewer :title="t('players.show.anti_cheat_metadata')" :metadata="logMetadata" :show.sync="showLogMetadata">
			<template #default>
				<p class="m-0 mb-2 font-bold">{{ t('logs.metadata.details') }}:</p>
				<pre class="block text-sm whitespace-pre-wrap break-words border-dashed border-b-2 mb-4 pb-4">{{ parseLogMetadata(logMetadata) || 'N/A' }}</pre>
			</template>
		</metadataViewer>

	</div>
</template>

<script>
import Layout from './../../Layouts/App.vue';
import VSection from './../../Components/Section.vue';
import Pagination from './../../Components/Pagination.vue';
import Modal from './../../Components/Modal.vue';
import MetadataViewer from './../../Components/MetadataViewer.vue';

const MoneyTransferActions = [
	'Bank Transfer',
	'Cash Transfer',
	'Paid Bill'
];

const DisconnectActions = [
	'User Disconnected',
	'Character Unloaded',
	'Unloaded Character',
	'Dropped Timed Out Player'
];

const ConnectActions = [
	'User Joined',
	'User Connected',
	'Character Loaded',
	'Connection Accepted'
];

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
			server: String,
			minigames: String,
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
		skipped: {
			type: Array
		}
	},
	data() {
		return {
			isLoading: false,
			showLogDetail: false,
			log_detail: {
				user: '',
				reason: '',
				description: ''
			},
			searchingActions: false,
			searchableActions: [],

			logTimezone: false,

			showLogMetadata: false,
			logMetadata: null,

			searchTimeout: false
		};
	},
	computed: {
		selectedTimezone() {
			if (this.logTimezone) {
				return dayjs().tz(this.logTimezone).format('z');
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

			const actions = this.actions ? this.actions.filter(action => action.action.toLowerCase().includes(search)) : [];
			actions.sort((a, b) => {
				return b.count - a.count;
			});

			this.searchableActions = actions;
			this.searchingActions = true;
		},
		showMoneyLogs() {
			this.filters.action = MoneyTransferActions.map(action => `=${action}`).join('|');

			this.refresh();
		},
		showConnectLogs() {
			this.filters.action = ConnectActions.concat(DisconnectActions).map(action => `=${action}`).join('|');

			this.refresh();
		},
		formatRawTimestamp(timestamp) {
			return dayjs(timestamp).unix();
		},
		stamp(time) {
			return dayjs.utc(time).unix();
		},
		getLogColor(action, metadata) {
			if (this.setting('parseLogs')) {
				const minigames = metadata?.minigames || [];

				if (minigames.length > 0) {
					return 'bg-purple-500 !bg-opacity-20 hover:!bg-opacity-40';
				} else if (MoneyTransferActions.includes(action)) {
					return 'bg-teal-500 !bg-opacity-20 hover:!bg-opacity-40';
				} else if (DisconnectActions.includes(action)) {
					return 'bg-rose-500 !bg-opacity-20 hover:!bg-opacity-40';
				} else if (ConnectActions.includes(action)) {
					return 'bg-lime-500 !bg-opacity-20 hover:!bg-opacity-40';
				}
			}

			return 'hover:bg-gray-200 dark:hover:bg-gray-600';
		},
		getLogTag(action, metadata) {
			if (!this.setting('parseLogs')) return '';

			const minigames = metadata?.minigames || [];

			if (minigames.length > 0) {
				return `<i class="text-purple-800 dark:text-purple-200 fas fa-gamepad" title="${minigames.join(', ')}"></i>`;
			} else if (MoneyTransferActions.includes(action)) {
				return `<i class="text-teal-800 dark:text-teal-200 fas fa-money-bill-wave" title="money transfer"></i>`;
			} else if (DisconnectActions.includes(action)) {
				return `<i class="text-rose-800 dark:text-rose-200 fas fa-door-open" title="exit/disconnect/unload"></i>`;
			} else if (ConnectActions.includes(action)) {
				return `<i class="text-lime-800 dark:text-lime-200 fas fa-person-booth" title="connect/join/load"></i>`;
			}

			return '';
		},
		detailedAction(e, log) {
			e.preventDefault();

			const metadata = log.metadata;

			if (metadata) {
				this.logMetadata = metadata;
				this.showLogMetadata = true;
			}
		},
		parseLogMetadata(metadata) {
			if (metadata?.secondaryCause) {
				const source = metadata.secondaryCause.source ? metadata.secondaryCause.source : '/';

				switch (metadata.secondaryCause.label) {
					case 'Unknown':
						return this.t('logs.metadata.secondary_unknown');
					case 'Player':
						return this.t('logs.metadata.secondary_player', source);
					case 'NPC':
						return this.t('logs.metadata.secondary_npc');
					case 'Vehicle':
						return this.t('logs.metadata.secondary_vehicle', source);
					case 'Touching Vehicle': {
						const vehicles = metadata.secondaryCause.source ? Object.entries(metadata.secondaryCause.source).map(e => `${e[0]} [${e[1] ? e[1] : '/'}]`).join(', ') : 'N/A';

						return this.t('logs.metadata.secondary_touching', vehicles);
					}
				}
			}

			return null;
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

				await this.$inertia.replace('/logs', {
					data: this.filters,
					preserveState: true,
					preserveScroll: true,
					only: ['logs', 'playerMap', 'time', 'links', 'page'],
				});
			} catch (e) {
			}

			this.isLoading = false;
		},
		parseOtherLog(details, action, metadata) {
			const regex = /attempted to add a song with video ID `(.+?)` to boombox/gmi;
			const matches = details.matchAll(regex).next();
			const match = matches?.value ? matches.value[1] : null;

			if (match) {
				const html = `<a href="https://youtube.com/watch?v=${match}" target="_blank" class="text-blue-600 dark:text-blue-400">${match}</a>`;

				return details.replace(match, html);
			}

			return details;
		},
		parseDisconnectLog(details, action, metadata) {
			const regex = /(?<=\) has disconnected from the server .+? with reason: `)(.+?)(?=`\.)/gm;
			const matches = details.match(regex);
			const match = matches && matches.length === 1 && matches[0].trim() ? matches[0].trim() : null;

			if (match) {
				const descriptions = [
					[/^Exiting/gmi, this.t('logs.detail.reasons.exited')],
					[/^Disconnected|^You have disconnected from the server/gmi, this.t('logs.detail.reasons.disconnected')],
					[/^Reloading game./gmi, this.t('logs.detail.reasons.reloading_game')],
					[/Game crashed: /gmi, this.t('logs.detail.reasons.crash')],
					[/(?<=connection|You) timed out[!.]|^Timed out after/gmi, this.t('logs.detail.reasons.timeout')],
					[/^You have been banned/gmi, this.t('logs.detail.reasons.banned')],
					[/^The server is restarting/gmi, this.t('logs.detail.reasons.restart')],
					[/^You have been kicked/gmi, this.t('logs.detail.reasons.kicked')],
					[/^Your Job Priority expired/gmi, this.t('logs.detail.reasons.job')],
					[/^Failed to sync doors/gmi, this.t('logs.detail.reasons.doors')],
					[/^You have been globally banned from all OP-FW servers/gmi, this.t('logs.detail.reasons.global')],
					[/^Entering Rockstar Editor/gmi, this.t('logs.detail.reasons.editor')],
					[/^Reliable network event overflow/gmi, this.t('logs.detail.reasons.overflow')],
					[/^Connecting to another server/gmi, this.t('logs.detail.reasons.another')],
					[/^Obtaining configuration from server failed/gmi, this.t('logs.detail.reasons.config')],
				];

				let description = '';
				for (const x in descriptions) {
					const entry = descriptions[x];

					if (entry[0].test(match)) {
						description = entry[1];
						break;
					}
				}

				if (!description) {
					description = this.t('logs.detail.reasons.unknown');
				}

				const html = `<a href="#" class="text-rose-600 dark:text-rose-200 exit-log" data-reason="${match}" data-description="${description}">${match}</a>`;

				return details.replace(match, html);
			}

			return this.parseOtherLog(details, action, metadata);
		},
		parseLog(details, action, metadata) {
			details = this.escapeHtml(details);

			if (!this.setting('parseLogs')) return details;

			if (action === "Item Moved") {
				details = details.replace(/(?<=to |from inventory )[\w-:]+/gmi, inventory => {
					return `<a title="${this.t('inventories.show_inv')}" class="text-indigo-600 dark:text-indigo-400" href="/inventory/${inventory.replace(/:\d+/, '')}">${inventory}</a>`;
				});
			}

			if (metadata?.killerLicenseIdentifier) {
				const killerLicense = metadata.killerLicenseIdentifier;

				details = details.replace(/killed by (.+?), death cause/gm, (match, playerName) => {
					return `killed by <a class="text-red-600 dark:text-red-400" href="/players/${killerLicense}">${playerName}</a>, death cause`;
				});
			}

			if (metadata?.killerSteam) {
				const killerSteam = metadata.killerSteam;

				details = details.replace(/killed by (.+?), death cause/gm, (match, playerName) => {
					return `killed by <a class="text-red-600 dark:text-red-400" href="/players/${killerSteam}">${playerName}</a>, death cause`;
				});
			}

			details = details.replace(/(license:\w+)(?=\))/gm, pMatch => {
				const start = pMatch.substring(8, 12);
				const end = pMatch.substring(pMatch.length - 4);

				return `<span class="copy_title text-gray-700 dark:text-gray-300 cursor-pointer license_id" title="${pMatch}">${start}...${end}</span>`;
			});

			details = details.replace(/URL `(.+?)`/gm, (pMatch, pUrl) => {
				return pMatch.replace(pUrl, `<a href="${pUrl}" target="_blank" class="text-indigo-600 dark:text-indigo-400">${pUrl}</a>`);
			});

			details = this.parseDisconnectLog(details, action, metadata);

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

		$('body').on('click', 'a.exit-log', function (e) {
			e.preventDefault();
			const parent = $(this).closest('tr');

			_this.showLogDetail = true;
			_this.log_detail.user = $('td:first-child a', parent).text().trim();
			_this.log_detail.reason = $(this).data('reason');
			_this.log_detail.description = $(this).data('description');
		});

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
