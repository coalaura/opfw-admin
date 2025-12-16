<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t('characters.title') }}
            </h1>
            <p>
                {{ t('characters.description') }}
            </p>
        </portal>

        <!-- Querying -->
        <v-section :noFooter="true">
            <template #header>
                <h2>
                    {{ t('characters.filter') }}
                </h2>
            </template>

            <template>
                <form @submit.prevent>
                    <div class="absolute top-2 right-2 flex gap-2">
                        <select class="block w-32 px-2 py-1 bg-gray-200 dark:bg-gray-600 border rounded text-sm" id="sort" name="sort" v-model="filters.sort" :title="t('global.sort_by')">
                            <option value="id">{{ t('global.sort.id') }}</option>
                            <option value="name">{{ t('global.sort.name') }}</option>
                            <option value="playtime">{{ t('global.sort.playtime') }}</option>
                            <option value="last">{{ t('global.sort.last') }}</option>
                        </select>

                        <select class="block w-20 px-2 py-1 bg-gray-200 dark:bg-gray-600 border rounded text-sm" id="order" name="order" v-model="filters.order" :title="t('global.sort_order')">
                            <option value="">ASC</option>
                            <option value="desc">DESC</option>
                        </select>
                    </div>

                    <div class="flex flex-wrap mb-4">
                        <!-- Character ID -->
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2" for="character_id">
                                {{ t('characters.form.character_id') }}
                            </label>
                            <input class="block w-full px-4 py-3 mb-3 bg-gray-200 border rounded dark:bg-gray-600" type="number" id="character_id" placeholder="83118" v-model="filters.character_id">
                        </div>
                        <!-- Name -->
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2" for="name">
                                {{ t('characters.form.name') }} <sup class="text-muted dark:text-dark-muted">*</sup>
                            </label>
                            <input class="block w-full px-4 py-3 mb-3 bg-gray-200 border rounded dark:bg-gray-600" id="name" placeholder="Lela Law" v-model="filters.name" :title="previewQuery(filters.name)">
                        </div>
                        <!-- Vehicle Plate -->
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2" for="vehicle_plate">
                                {{ t('characters.form.plate') }} <sup class="text-muted dark:text-dark-muted">*</sup>
                            </label>
                            <input class="block w-full px-4 py-3 mb-3 bg-gray-200 border rounded dark:bg-gray-600" id="vehicle_plate" placeholder="23FTW355" v-model="filters.vehicle_plate" :title="previewQuery(filters.vehicle_plate)">
                        </div>
                        <!-- Vehicle ID -->
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2" for="vehicle_id">
                                {{ t('characters.form.vehicle_id') }} <sup class="text-muted dark:text-dark-muted">*</sup>
                            </label>
                            <input class="block w-full px-4 py-3 mb-3 bg-gray-200 border rounded dark:bg-gray-600" id="vehicle_id" placeholder="69420" v-model="filters.vehicle_id" :title="previewQuery(filters.vehicle_id)">
                        </div>
                        <!-- Date of Birth -->
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2" for="dob">
                                {{ t('characters.form.dob') }}
                            </label>
                            <input class="block w-full px-4 py-3 mb-3 bg-gray-200 border rounded dark:bg-gray-600" id="dob" placeholder="1998-03-04" v-model="filters.dob">
                        </div>
                        <!-- Phone Number -->
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2" for="phone">
                                {{ t('characters.form.phone') }} <sup class="text-muted dark:text-dark-muted">*</sup>
                            </label>
                            <input class="block w-full px-4 py-3 mb-3 bg-gray-200 border rounded dark:bg-gray-600" id="phone" placeholder="723-4797" v-model="filters.phone" :title="previewQuery(filters.phone)">
                        </div>
                        <!-- Job -->
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2" for="job">
                                {{ t('characters.form.job') }} <sup class="text-muted dark:text-dark-muted">*</sup>
                            </label>
                            <input class="block w-full px-4 py-3 mb-3 bg-gray-200 border rounded dark:bg-gray-600" id="job" placeholder="Law Enforcement SASP Cadet" v-model="filters.job" :title="previewQuery(filters.job)">
                        </div>
                        <!-- Licenses -->
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-2" for="license">
                                {{ t('characters.form.license') }}
                            </label>
                            <select class="block w-full px-4 py-3 mb-3 bg-gray-200 dark:bg-gray-600 border rounded" id="license" name="license" v-model="filters.license">
                                <option value="">{{ t('global.any') }}</option>
                                <option :value="license" v-for="license in licenses" :key="license">{{ t('players.characters.license.' + license) }}</option>
                            </select>
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
                                    {{ t('characters.search') }}
                                </span>
                                <span v-else>
                                    <i class="fas fa-cog animate-spin"></i>
                                    {{ t('global.loading') }}
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </template>
        </v-section>

        <!-- Table -->
        <v-section class="overflow-x-auto">
            <template #header>
                <h2>
                    {{ t('characters.title') }}
                </h2>
                <p class="text-muted dark:text-dark-muted text-xs">
                    {{ t('global.results', time) }}
                </p>
            </template>

            <template>
                <table class="w-full">
                    <tr class="font-semibold text-left mobile:hidden">
                        <th class="p-3 pl-8">{{ t('characters.result.player') }}</th>
                        <th class="p-3">{{ t('characters.form.character_id') }}</th>
                        <th class="p-3">{{ t('characters.form.phone') }}</th>
                        <th class="p-3">{{ t('characters.form.name') }}</th>
                        <th class="p-3">{{ t('characters.result.gender') }}</th>
                        <th class="p-3">{{ t('characters.form.dob') }}</th>
                        <th class="p-3">{{ t('characters.form.job') }}</th>
                        <th class="p-3 pr-8"></th>
                    </tr>
                    <tr class="border-t border-gray-300 dark:border-gray-500 hover:bg-gray-200 dark:hover:bg-gray-600" v-for="character in characters" :key="character.id">
                        <td class="p-3 pl-8 mobile:block">
                            <inertia-link class="block px-4 py-2 font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="'/players/' + character.licenseIdentifier">
                                {{ playerName(character.licenseIdentifier) }}
                            </inertia-link>
                        </td>
                        <td class="p-3 mobile:block">{{ character.id }}</td>
                        <td class="p-3 mobile:block">{{ character.phoneNumber }}</td>
                        <td class="p-3 mobile:block">
                            {{ character.firstName }} {{ character.lastName }}
                        </td>
                        <td class="p-3 mobile:block">
                            {{ character.gender | formatGender(t) }}
                        </td>
                        <td class="p-3 mobile:block">
                            {{ character.dateOfBirth }}
                        </td>
                        <td class="p-3 mobile:block">
                            {{ character.jobName || t('global.none') }} /
                            {{ character.departmentName || t('global.none') }} /
                            {{ character.positionName || t('global.none') }}
                        </td>
                        <td class="p-3 pr-8 mobile:block">
                            <inertia-link class="block px-4 py-2 font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" v-bind:href="'/players/' + character.licenseIdentifier + '/characters/' + character.id">
                                <i class="fas fa-chevron-right"></i>
                            </inertia-link>
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
						<inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" v-if="characters.length === 20" :href="links.next">
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
        VSection,
    },
    props: {
        characters: {
            type: Array,
            required: true,
        },
        filters: {
            sort: String,
            order: String,

            character_id: Number,
            name: String,
            vehicle_plate: String,
            vehicle_id: Number,
            dob: String,
            phone: String,
            job: String,
            license: String,
        },
        playerMap: {
            type: Object,
            required: true,
        },
        time: {
            type: Number,
            required: true,
        },
        licenses: {
            type: Array,
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
            isLoading: false
        };
    },
    methods: {
        refresh: async function () {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;
            try {
                await this.$inertia.replace('/characters', {
                    data: this.filters,
                    preserveState: true,
                    preserveScroll: true,
                    only: ['characters', 'playerMap', 'time', 'page', 'links'],
                });
            } catch (e) { }

            this.isLoading = false;
        },
        playerName(licenseIdentifier) {
            return licenseIdentifier in this.playerMap ? this.playerMap[licenseIdentifier] : licenseIdentifier;
        }
    }
};
</script>
