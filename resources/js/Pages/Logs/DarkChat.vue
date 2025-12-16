<template>
	<div>

		<portal to="title">
			<h1 class="dark:text-white">
				<i class="mr-3 fas fa-unlock-alt" :title="perm.restriction(perm.PERM_DARK_CHAT)"></i>

				{{ t('logs.dark_chat') }}
			</h1>
			<p>
				{{ t('logs.dark_chat_description') }}
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
					{{ t('logs.filter_messages') }}
				</h2>
			</template>

			<template>
				<form @submit.prevent autocomplete="off">
					<input autocomplete="false" name="hidden" type="text" class="hidden" />

					<div class="flex flex-wrap mb-4">
						<!-- License Identifier -->
						<div class="w-3/12 px-3 mobile:w-full mobile:mb-3">
							<label class="block mb-2" for="license">
								{{ t('logs.license') }} <sup class="text-muted dark:text-dark-muted">*</sup>
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="license" placeholder="license:2ced2cabd90f1208e7e056485d4704c7e1284196" v-model="filters.license" :title="previewQuery(filters.license)">
						</div>
						<!-- Channel -->
						<div class="w-2/12 px-3 mobile:w-full mobile:mb-3">
							<label class="block mb-2" for="channel">
								{{ t('logs.channel') }} <sup class="text-muted dark:text-dark-muted">*</sup>
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="channel" placeholder="guns" v-model="filters.channel" :title="previewQuery(filters.channel)">
						</div>
						<!-- Message -->
						<div class="w-7/12 px-3 mobile:w-full mobile:mb-3">
							<label class="block mb-2" for="message">
								{{ t('logs.message') }} <sup class="text-muted dark:text-dark-muted">*</sup>
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="message" placeholder="how do i remove safety?" v-model="filters.message" :title="previewQuery(filters.message)">
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
								{{ t('logs.search_chat') }}
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
					{{ t('logs.messages') }}
				</h2>
				<p class="text-muted dark:text-dark-muted text-xs">
					{{ t('global.results', time) }}
				</p>
			</template>

			<template>
				<table class="w-full">
					<tr class="font-semibold text-left mobile:hidden">
						<th class="p-3 pl-8 max-w-56">{{ t('logs.player') }}</th>
						<th class="p-3 whitespace-nowrap">{{ t('logs.character') }}</th>
						<th class="p-3 w-40 whitespace-nowrap">{{ t('logs.channel') }}</th>
						<th class="p-3">{{ t('logs.message') }}</th>
						<th class="p-3 pr-8 whitespace-nowrap w-40">
							{{ t('logs.timestamp') }}
						</th>
					</tr>
					<tr class="border-t border-gray-300 dark:border-gray-500 relative" v-for="log in logs" :key="log.id">
						<td class="p-3 pl-8 mobile:block max-w-56 whitespace-nowrap">
							<inertia-link class="block px-4 py-2 truncate font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="'/players/' + log.license_identifier" v-if="log.license_identifier">
								{{ log.player_name }}
							</inertia-link>

							<span class="italic" v-else>{{ t('logs.not_available') }}</span>
						</td>
						<td class="p-3 mobile:block whitespace-nowrap">
							<span v-if="log.character_id" class="font-semibold">#{{ log.character_id }}</span>

							<span class="italic" v-else>{{ t('logs.not_available') }}</span>
						</td>
						<td class="p-3 mobile:block w-40 whitespace-nowrap">
							{{ log.channel }}
						</td>
						<td class="p-3 mobile:block">
							{{ log.message }}
						</td>
						<td class="p-3 pr-8 mobile:block w-40 whitespace-nowrap">
							{{ log.timestamp * 1000 | formatTime(true) }}

							<i class="block text-xs leading-1 whitespace-nowrap text-yellow-600 dark:text-yellow-400">{{ formatRawTimestamp(log.timestamp * 1000) }}</i>
						</td>
					</tr>
					<tr v-if="logs.length === 0" class="border-t border-gray-300 dark:border-gray-500">
						<td class="py-3 px-8 text-center" colspan="100%">
							{{ t('logs.no_messages') }}
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

export default {
	layout: Layout,
	components: {
		VSection
	},
	props: {
		logs: {
			type: Array,
			required: true,
		},
		filters: {
			license: String,
			channel: String,
			message: String,
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
			isLoading: false
		};
	},
	methods: {
		formatRawTimestamp(timestamp) {
			return dayjs(timestamp).unix();
		},
		async refresh() {
			if (this.isLoading) {
				return;
			}

			this.isLoading = true;

			try {
				await this.$inertia.replace('/dark_chat', {
					data: this.filters,
					preserveState: true,
					preserveScroll: true,
					only: ['logs', 'time', 'links', 'page', 'filters'],
				});
			} catch (e) {
			}

			this.isLoading = false;
		},
		playerName(licenseIdentifier) {
			return this.playerMap && licenseIdentifier in this.playerMap ? this.playerMap[licenseIdentifier] : licenseIdentifier;
		}
	}
};
</script>
