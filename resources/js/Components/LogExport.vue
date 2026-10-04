<template>
	<div class="inline-block mr-3">
		<button class="px-4 py-2 text-sm font-semibold text-white bg-teal-600 rounded dark:bg-teal-500" type="button" @click="open">
			<i class="mr-1 fas fa-file-csv"></i>
			{{ t('logs.export.button') }}
		</button>

		<modal :show="show" @update:show="close">
			<template #header>
				<h1 class="dark:text-white">{{ t('logs.export.title') }}</h1>
				<p>{{ t('logs.export.description') }}</p>
			</template>

			<form id="log-export-form" @submit.prevent="download">
				<h2 class="font-semibold mb-2">{{ t('logs.export.filters') }}</h2>
				<dl class="mb-5 divide-y divide-gray-300 dark:divide-gray-500" v-if="activeFilters.length">
					<div class="py-2" v-for="filter in activeFilters" :key="filter.key">
						<dt class="text-sm font-semibold">{{ label(filter.key) }}</dt>
						<dd class="whitespace-pre-wrap break-words">{{ filter.value }}</dd>
					</div>
				</dl>
				<p class="mb-5 text-muted dark:text-dark-muted" v-else>{{ t('logs.export.no_filters') }}</p>

				<fieldset class="flex flex-wrap gap-4" :disabled="isExporting">
					<div class="flex-1 min-w-[10rem]">
						<label class="block mb-2" for="export-limit">{{ t('logs.export.limit') }}</label>
						<input id="export-limit" class="w-full px-4 py-3 bg-gray-200 dark:bg-gray-600 border rounded" type="number" min="1" max="500" step="1" required v-model.number="limit">
					</div>
					<div class="flex-1 min-w-[10rem]">
						<label class="block mb-2" for="export-sort">{{ t('logs.export.sort') }}</label>
						<select id="export-sort" class="w-full px-4 py-3 bg-gray-200 dark:bg-gray-600 border rounded" v-model="sort">
							<option v-for="option in sortOptions" :key="option" :value="option">{{ label(option) }}</option>
						</select>
					</div>
					<div class="flex-1 min-w-[10rem]">
						<label class="block mb-2" for="export-order">{{ t('logs.export.order') }}</label>
						<select id="export-order" class="w-full px-4 py-3 bg-gray-200 dark:bg-gray-600 border rounded" v-model="order">
							<option value="desc">{{ t('logs.export.descending') }}</option>
							<option value="asc">{{ t('logs.export.ascending') }}</option>
						</select>
					</div>
				</fieldset>

				<p class="mt-3 text-sm text-muted dark:text-dark-muted">{{ t('logs.export.hint') }}</p>
				<p class="mt-3 text-red-600 dark:text-red-400" role="alert" v-if="error">{{ error }}</p>
			</form>

			<template #actions>
				<button class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400 disabled:opacity-50" type="button" :disabled="isExporting" @click="close">
					{{ t('global.close') }}
				</button>
				<button class="px-5 py-2 font-semibold text-white bg-teal-600 rounded dark:bg-teal-500 disabled:opacity-50" type="submit" form="log-export-form" :disabled="isExporting">
					<i class="mr-1 fas" :class="isExporting ? 'fa-spinner fa-spin' : 'fa-download'"></i>
					{{ t(isExporting ? 'logs.export.exporting' : 'logs.export.download') }}
				</button>
			</template>
		</modal>
	</div>
</template>

<script>
import Modal from './Modal.vue';

const FilterLabels = {
	server: 'logs.server_id',
	typ: 'logs.type',
	damage: 'logs.damage_dealt',
	before: 'logs.before-date',
	after: 'logs.after-date'
};

export default {
	components: {
		Modal: Modal
	},
	props: {
		type: { type: String, required: true },
		getFilters: { type: Function, required: true },
		sortOptions: { type: Array, required: true }
	},
	data() {
		return {
			show: false,
			filters: {},
			limit: 500,
			sort: 'timestamp',
			order: 'desc',
			isExporting: false,
			error: ''
		};
	},
	computed: {
		activeFilters() {
			return Object.entries(this.filters).filter(([key, value]) => value !== null && value !== undefined && value !== '').map(([key, value]) => {
				return { key: key, value: this.filterValue(key, value) };
			});
		}
	},
	methods: {
		label(key) {
			return this.t(FilterLabels[key] || `logs.${key}`);
		},
		filterValue(key, value) {
			if (key === 'before' || key === 'after') {
				return dayjs.unix(value).format('YYYY-MM-DD HH:mm:ss Z');
			}

			if (key === 'minigame' && ['none', 'arena', 'battle_royale', 'zombie_pill', 'training'].includes(value)) {
				return this.t(`logs.minigame_${value}`);
			}

			if (key === 'typ' && ['cash', 'bank'].includes(value)) {
				return this.t(`logs.types.${value}`);
			}

			if (key === 'direction' && ['in', 'out'].includes(value)) {
				return this.t(`logs.directions.${value}`);
			}

			return value;
		},
		open() {
			if (this.isExporting) {
				return;
			}

			this.filters = { ...this.getFilters() };
			this.error = '';
			this.show = true;
		},
		close() {
			if (!this.isExporting) {
				this.show = false;
			}
		},
		async download() {
			if (this.isExporting) {
				return;
			}

			if (!Number.isInteger(this.limit) || this.limit < 1 || this.limit > 500) {
				this.error = this.t('logs.export.invalid_limit');
				return;
			}

			this.error = '';
			this.isExporting = true;

			try {
				const parameters = new URLSearchParams();

				for (const [key, value] of Object.entries(this.filters)) {
					if (value !== null && value !== undefined && value !== '') {
						parameters.set(key, value);
					}
				}

				parameters.set('limit', this.limit);
				parameters.set('sort', this.sort);
				parameters.set('order', this.order);

				const response = await fetch(`/export/logs/${this.type}?${parameters}`, {
					credentials: 'same-origin',
					headers: { Accept: 'application/json, text/csv' }
				});

				if (!response.ok || !response.headers.get('content-type')?.includes('text/csv')) {
					throw new Error('CSV export failed');
				}

				const blob = await response.blob();
				const url = URL.createObjectURL(blob);
				const link = document.createElement('a');

				link.href = url;
				link.download = `${this.type}_logs.csv`;
				document.body.append(link);
				link.click();
				link.remove();

				// Let the browser start reading the download before releasing its URL.
				setTimeout(() => URL.revokeObjectURL(url), 1000);
				this.show = false;
			} catch (error) {
				this.error = this.t('logs.export.error');
			} finally {
				this.isExporting = false;
			}
		}
	}
};
</script>
