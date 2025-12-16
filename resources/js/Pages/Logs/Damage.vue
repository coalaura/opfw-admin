<template>
	<div>

		<portal to="title">
			<h1 class="dark:text-white">
				<i class="mr-3 fas fa-unlock-alt" :title="perm.restriction(perm.PERM_DAMAGE_LOGS)"></i>

				{{ t('logs.damage') }}
			</h1>
			<p>
				{{ t('logs.damage_description') }}
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
						<!-- Attacker -->
						<div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
							<label class="block mb-2" for="attacker">
								{{ t('logs.attacker') }} <sup class="text-muted dark:text-dark-muted">*</sup>
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="attacker" placeholder="license:2ced2cabd90f1208e7e056485d4704c7e1284196" v-model="filters.attacker" :title="previewQuery(filters.attacker)">
						</div>

						<!-- Victim -->
						<div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
							<label class="block mb-2" for="victim">
								{{ t('logs.victim') }}
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="victim" placeholder="license:2ced2cabd90f1208e7e056485d4704c7e1284196" v-model="filters.victim">

							<div class="w-full mt-1 italic">
								<small class="text-muted dark:text-dark-muted leading-4 block" v-html="t('logs.victim_hint')"></small>
							</div>
						</div>

						<!-- Damage -->
						<div class="w-1/6 px-3 mobile:w-full mobile:mb-3">
							<label class="block mb-2" for="damage">
								{{ t('logs.damage') }} <sup class="text-muted dark:text-dark-muted">*</sup>
							</label>
							<input class="block w-full px-4 py-3 !bg-opacity-10 border rounded outline-none" :class="damageColor" id="damage" placeholder=">20" v-model="filters.damage" :title="previewQuery(filters.damage)">

							<div class="w-full mt-1 italic">
								<small class="text-muted dark:text-dark-muted leading-4 block" v-html="t('logs.damage_hint')"></small>
							</div>
						</div>

						<!-- Weapon -->
						<div class="w-1/6 px-3 mobile:w-full mobile:mb-3">
							<label class="block mb-2" for="weapon">
								{{ t('logs.weapon') }}
							</label>
							<input class="block w-full px-4 py-3 !bg-opacity-10 border rounded outline-none" :class="weaponColor" id="weapon" placeholder="weapon_pistol" v-model="filters.weapon">
						</div>

						<!-- Entity Type -->
						<div class="w-1/6 px-3 mobile:w-full mobile:mb-3">
							<label class="block mb-2" for="entity">
								{{ t('logs.entity') }}
							</label>
							<select class="w-full px-4 py-3 bg-gray-200 dark:bg-gray-600 border rounded" id="entity" v-model="filters.entity">
								<option :value="null">{{ t('global.all') }}</option>
								<option value="player">{{ t('logs.players') }}</option>
								<option value="ped">{{ t('logs.peds') }}</option>
								<option value="vehicle">{{ t('logs.vehicles') }}</option>
								<option value="object">{{ t('logs.objects') }}</option>
							</select>
						</div>

						<!-- After Date -->
						<div class="w-1/4 px-3 mobile:w-full mobile:mb-3 mt-3">
							<label class="block mb-2" for="after-date">
								{{ t('logs.after-date') }}
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="after-date" type="date" placeholder="">
						</div>

						<!-- After Time -->
						<div class="w-1/4 px-3 mobile:w-full mobile:mb-3 mt-3">
							<label class="block mb-2" for="after-time">
								{{ t('logs.after-time') }}
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="after-time" type="time" placeholder="">
						</div>

						<!-- Before Date -->
						<div class="w-1/4 px-3 mobile:w-full mobile:mb-3 mt-3">
							<label class="block mb-2" for="before-date">
								{{ t('logs.before-date') }}
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="before-date" type="date" placeholder="">
						</div>

						<!-- Before Time -->
						<div class="w-1/4 px-3 mobile:w-full mobile:mb-3 mt-3">
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
						<th class="p-3 pl-8 max-w-56">{{ t('logs.attacker') }}</th>
						<th class="p-3 max-w-40">{{ t('logs.victim') }}</th>
						<th class="p-3 max-w-40" :title="t('logs.hit_id_hint')">{{ t('logs.hit_id') }}</th>
						<th class="p-3">{{ t('logs.health_before') }}</th>
						<th class="p-3">{{ t('logs.damage_dealt') }}</th>
						<th class="p-3">{{ t('logs.distance') }}</th>
						<th class="p-3">{{ t('logs.component') }}</th>
						<th class="p-3">{{ t('logs.weapon') }}</th>
						<th class="p-3 pr-8">
							{{ t('logs.timestamp') }}
							<a href="#" :title="t('logs.toggle_diff')" @click="$event.preventDefault(); showLogTimeDifference = !showLogTimeDifference">
								<i class="fas fa-stopwatch"></i>
							</a>
						</th>
					</tr>
					<tr class="border-t border-gray-300 dark:border-gray-500 relative" :class="{ 'opacity-50': log.canceled }" v-for="(log, index) in logs" :key="log.id" :title="log.canceled ? t('logs.canceled') : ''">
						<td class="p-3 pl-8 mobile:block max-w-56">
							<div class="absolute top-1 left-1 text-sm leading-3 font-semibold italic" v-html="getDamageTag(log)"></div>

							<inertia-link class="block px-4 py-2 truncate font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="'/players/' + log.licenseIdentifier">
								{{ playerName(log.licenseIdentifier) }}
							</inertia-link>
						</td>
						<td class="p-3 mobile:block max-w-40">
							<span class="italic" v-if="log.hitEntityType === 1">
								<inertia-link class="block px-4 py-2 truncate font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="'/players/' + log.hitLicense" v-if="log.hitLicense">
									{{ playerName(log.hitLicense) }}
								</inertia-link>

								<span v-else>
									{{ t('logs.npc') }}
								</span>
							</span>
							<span class="italic whitespace-nowrap" v-else-if="log.hitEntityType === 2">
								{{ t('logs.vehicle') }}

								<i v-if="Number.isInteger(log.tireIndex)" :title="t('logs.hit_tire', log.tireIndex)" class="fas fa-truck-monster ml-2 cursor-help"></i>
								<i v-if="Number.isInteger(log.suspensionIndex)" :title="t('logs.hit_suspension', log.suspensionIndex)" class="fas fa-car-crash ml-2 cursor-help"></i>
							</span>
							<span class="italic" v-else-if="log.hitEntityType === 3">
								{{ t('logs.object') }}
							</span>
						</td>
						<td class="p-3 mobile:block max-w-40 whitespace-nowrap">
							<a :title="t('logs.vehicle_id')" class="text-blue-600 dark:text-blue-400" v-if="log.hitVehicleId" :href="`/find/vehicle/${log.hitVehicleId}`" target="_blank">
								<i class="fas fa-truck-pickup mr-1"></i>
								{{ log.hitVehicleId }}
							</a>
							<div :title="t('logs.network_id')" v-else>
								<i class="fas fa-network-wired mr-1"></i>
								{{ log.hitGlobalId }}
							</div>
						</td>
						<td class="p-3 mobile:block">
							{{ log.hitHealth ? log.hitHealth + "hp" : "N/A" }}
						</td>
						<td class="p-3 mobile:block whitespace-nowrap" :title="t('logs.damage_flags', getDamageFlags(log.flags))" :style="{ color: getDamageColor(log.damage) }">
							{{ log.damage }}hp

							<i v-if="log.bonusDamage" :title="t('logs.bonus_damage')">+{{ log.bonusDamage }}hp</i>
						</td>
						<td class="p-3 mobile:block">
							{{ log.distance.toFixed(2) }}m
						</td>
						<td class="p-3 mobile:block">
							{{ log.hitComponent }}
						</td>
						<td class="p-3 mobile:block">
							<div class="flex items-center gap-1">
								<span :class="{ 'italic cursor-help': log.weapon.match(/^-?\d+$/m) && !(log.weapon in resolvedHashes) }" @click="resolveWeaponHash(log.weapon)" v-html="resolvedWeaponHash(log.weapon)"></span>
								<a href="/docs/hashes" target="_blank" class="text-blue-600 dark:text-blue-400" :title="t('logs.hashes_docs')" v-if="log.weapon.match(/^-?\d+$/m)">[?]</a>
							</div>
						</td>
						<td class="p-3 mobile:block whitespace-nowrap" v-if="showLogTimeDifference" :title="t('logs.diff_label')">
							<span v-if="index + 1 < logs.length">
								{{ formatMilliSecondDiff(log.timestamp - logs[index + 1].timestamp) }}
								<i class="fas fa-arrow-down"></i>
							</span>
							<span v-else>Start</span>
						</td>
						<td class="p-3 pr-8 mobile:block whitespace-nowrap" v-else>
							{{ log.timestamp | formatTime(true) }}
							<i class="block text-xs leading-1 whitespace-nowrap text-yellow-600 dark:text-yellow-400">{{ Math.round(log.timestamp / 1000) }}</i>
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
import HashResolver from './../../Components/HashResolver.vue';

import Vue from 'vue';

export default {
	layout: Layout,
	components: {
		Pagination,
		VSection,
		HashResolver
	},
	props: {
		logs: {
			type: Array,
			required: true,
		},
		filters: {
			attacker: String,
			victim: String,
			damage: String,
			weapon: String,
			entity: String,
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
		weapons: {
			type: Array
		}
	},
	data() {
		return {
			isLoading: false,
			showLogTimeDifference: false,
			resolvedHashes: {}
		};
	},
	computed: {
		weaponColor() {
			const weapon = this.filters.weapon?.trim()?.toLowerCase();

			if (!weapon) {
				return "bg-gray-200 dark:bg-gray-600";
			} else if (this.weapons.includes(weapon)) {
				return "bg-lime-500 focus:border-lime-500 border-lime-500";
			} else if (weapon.match(/^-?\d+$/m)) {
				return "bg-orange-500 focus:border-orange-500 border-orange-500";
			}

			return "bg-red-500 focus:border-red-500 border-red-500";
		},
		damageColor() {
			const damage = this.filters.damage?.trim();

			if (!damage) {
				return "bg-gray-200 dark:bg-gray-600";
			} else if (damage.match(/^[<=>]\d+$/m)) {
				return "bg-lime-500 focus:border-lime-500 border-lime-500";
			}

			return "bg-red-500 focus:border-red-500 border-red-500";
		}
	},
	methods: {
		formatMilliSecondDiff(ms) {
			if (ms <= 5000) {
				const seconds = Math.floor(ms / 1000);
				ms -= seconds * 1000;

				return (seconds ? `${seconds}s ` : '') + (!seconds || ms ? `${ms}ms` : '');
			}

			return dayjs.duration(Math.round(ms / 1000), 'seconds').format('D[d] H[h] m[m] s[s]');
		},
		async refresh() {
			if (this.isLoading) {
				return;
			}

			this.isLoading = true;

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

			try {
				await this.$inertia.replace('/damage', {
					data: this.filters,
					preserveState: true,
					preserveScroll: true,
					only: ['logs', 'playerMap', 'time', 'links', 'page'],
				});
			} catch (e) {
			}

			this.isLoading = false;
		},
		playerName(licenseIdentifier) {
			return licenseIdentifier in this.playerMap ? this.playerMap[licenseIdentifier] : licenseIdentifier;
		},
		getDamageTag(log) {
			if (!this.setting('parseLogs')) return '';

			if (log.silenced) {
				return `<i class="text-teal-700 dark:text-teal-300 fas fa-volume-mute" title="${this.t("logs.silenced")}"></i>`;
			}

			if (log.hitComponent === "head") {
				return `<i class="text-red-700 dark:text-red-300 fas fa-crosshairs" title="${this.t("logs.headshot")}"></i>`;
			}

			if (log.damage >= 200) {
				return `<i class="text-red-700 dark:text-red-300 fas fa-temperature-high" title="${this.t("logs.high_damage")}"></i>`;
			}

			return '';
		},
		getDamageFlags(bitmask) {
			if (!bitmask || bitmask < 0) return 'None';

			const flags = [];

			bitmask & 2 ** 0 && flags.push('IsAccurate');
			bitmask & 2 ** 1 && flags.push('MeleeDamage');
			bitmask & 2 ** 2 && flags.push('SelfDamage');
			bitmask & 2 ** 3 && flags.push('ForceMeleeDamage');
			bitmask & 2 ** 4 && flags.push('IgnorePedFlags');
			bitmask & 2 ** 5 && flags.push('ForceInstantKill');
			bitmask & 2 ** 6 && flags.push('IgnoreArmor');
			bitmask & 2 ** 7 && flags.push('IgnoreStatModifiers');
			bitmask & 2 ** 8 && flags.push('FatalMeleeDamage');
			bitmask & 2 ** 9 && flags.push('AllowHeadShot');
			bitmask & 2 ** 10 && flags.push('AllowDriverKill');
			bitmask & 2 ** 11 && flags.push('KillPriorToClearedWantedLevel');
			bitmask & 2 ** 12 && flags.push('SuppressImpactAudio');
			bitmask & 2 ** 13 && flags.push('ExpectedPlayerKill');
			bitmask & 2 ** 14 && flags.push('DontReportCrimes');
			bitmask & 2 ** 15 && flags.push('PtFxOnly');
			bitmask & 2 ** 16 && flags.push('UsePlayerPendingDamage');
			bitmask & 2 ** 17 && flags.push('AllowCloneMeleeDamage');
			bitmask & 2 ** 18 && flags.push('NoAnimatedMeleeReaction');
			bitmask & 2 ** 19 && flags.push('IgnoreRemoteDistCheck');
			bitmask & 2 ** 20 && flags.push('VehicleMeleeHit');
			bitmask & 2 ** 21 && flags.push('EnduranceDamageOnly');
			bitmask & 2 ** 22 && flags.push('HealthDamageOnly');
			bitmask & 2 ** 23 && flags.push('DamageFromBentBullet');
			bitmask & 2 ** 24 && flags.push('DontAssertOnNullInflictor');

			return flags.join(' | ');
		},
		getDamageColor(damage) {
			const percentage = 1 - Math.min(1, Math.max(0, (damage - 80) / 60));

			return `hsl(${percentage * 100}, ${this.isDarkMode() ? "100%, 75%" : "75%, 25%"})`;
		},
		resolvedWeaponHash(hash) {
			const resolved = this.resolvedHashes[hash] || false;

			if (resolved) {
				if (resolved === true) {
					return '<i class="fas fa-spinner fa-spin"></i>';
				}

				return resolved;
			}

			return hash;
		},
		async resolveWeaponHash(hash) {
			if (hash in this.resolvedHashes || !hash?.match(/^-?\d+$/m)) return;

			Vue.set(this.resolvedHashes, hash, true);

			const result = await this.resolveHash(hash);

			Vue.set(this.resolvedHashes, hash, result ? result.name : false);
		}
	},
	mounted() {
		if (this.filters.before) {
			const d = dayjs.utc(this.filters.before * 1000);

			$('#before-date').val(d.format('YYYY-MM-DD'));
			$('#before-time').val(d.format('HH:mm'));
		}

		if (this.filters.after) {
			const d = dayjs.utc(this.filters.after * 1000);

			$('#after-date').val(d.format('YYYY-MM-DD'));
			$('#after-time').val(d.format('HH:mm'));
		}
	}
};
</script>
