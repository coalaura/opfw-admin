<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t('blacklist.title') }}
            </h1>
            <p>
                {{ t('blacklist.description') }}
            </p>
        </portal>

        <!-- Search -->
        <v-section :noFooter="true">
            <template #header>
                <h2>
                    {{ t('global.filter') }}
                </h2>
            </template>

            <template>
                <form @submit.prevent>
                    <div class="flex flex-wrap mb-4">
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-4 font-semibold" for="creator">
                                {{ t('blacklist.creator') }}
                            </label>
                            <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="creator" name="creator" placeholder="license:2ced2cabd90f1208e7e056485d4704c7e1284196" v-model="filters.creator">
                        </div>
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-4 font-semibold" for="identifier">
                                {{ t('blacklist.identifier') }} <sup class="text-muted dark:text-dark-muted">*</sup>
                            </label>
                            <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="identifier" name="identifier" placeholder="license:2ced2cabd90f1208e7e056485d4704c7e1284196" v-model="filters.identifier">
                        </div>
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-4 font-semibold" for="reason">
                                {{ t('blacklist.reason') }} <sup class="text-muted dark:text-dark-muted">*</sup>
                            </label>
                            <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="reason" name="reason" placeholder="Really bad guy i don't like him." v-model="filters.reason">
                        </div>
                        <div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
                            <label class="block mb-4 font-semibold" for="note">
                                {{ t('blacklist.note') }} <sup class="text-muted dark:text-dark-muted">*</sup>
                            </label>
                            <input class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="note" name="note" placeholder="Really bad guy i don't like him." v-model="filters.note">
                        </div>
                    </div>
                    <!-- Description -->
                    <div class="w-full px-3 mt-3">
                        <small class="text-muted dark:text-dark-muted mt-1 leading-4 block" v-html="t('global.search.custom')"></small>
                    </div>
                </form>
                <!-- Search button -->
                <div class="w-full mt-3">
                    <button class="px-5 py-2 font-semibold text-white bg-success dark:bg-dark-success rounded hover:shadow-lg" @click="refresh">
                        <span v-if="!isLoading">
                            <i class="fas fa-search"></i>
                            {{ t('global.do_search') }}
                        </span>
                        <span v-else>
                            <i class="fas fa-cog animate-spin"></i>
                            {{ t('global.loading') }}
                        </span>
                    </button>
                </div>
            </template>
        </v-section>

        <portal to="actions">
            <div>
                <!-- Importing -->
                <button class="px-5 py-2 mr-2 font-semibold text-white rounded bg-danger dark:bg-dark-danger mobile:block mobile:w-full mobile:m-0 mobile:mb-3" @click="isUploading = true">
                    <i class="mr-1 fas fa-cloud-upload-alt"></i>
                    {{ t('blacklist.import') }}
                </button>

                <!-- Adding -->
                <button class="px-5 py-2 font-semibold text-white rounded bg-danger dark:bg-dark-danger mobile:block mobile:w-full mobile:m-0 mobile:mb-3" @click="isAdding = true">
                    <i class="mr-1 fas fa-plus"></i>
                    {{ t('blacklist.add') }}
                </button>
            </div>
        </portal>

        <!-- Add -->
        <div class="fixed bg-black bg-opacity-70 top-0 left-0 right-0 bottom-0 z-2k" v-if="isAdding">
            <div class="shadow-xl absolute bg-gray-100 dark:bg-gray-600 text-black dark:text-white left-2/4 top-2/4 -translate-x-2/4 -translate-y-2/4 transform p-6 rounded w-alert">
                <h3 class="mb-2">
                    {{ t('blacklist.add') }}
                </h3>

                <!-- License Identifier -->
                <div class="w-full p-3 flex justify-between px-0">
                    <label class="mr-4 block w-1/4 pt-2 font-bold" for="add_identifier">
                        {{ t('blacklist.identifier') }}
                    </label>
                    <input class="w-3/4 px-4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="add_identifier" placeholder="license:2ced2cabd90f1208e7e056485d4704c7e1284196" v-model="form.identifier" />
                </div>

                <!-- Ban Reason -->
                <div class="w-full p-3 flex justify-between px-0">
                    <label class="mr-4 block w-1/4 pt-2 font-bold">
                        {{ t('blacklist.reason') }}
                    </label>
                    <textarea class="block w-3/4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="add_reason" placeholder="1.1, 1.2" v-model="form.reason"></textarea>
                </div>

                <!-- Note -->
                <div class="w-full p-3 flex justify-between px-0">
                    <label class="mr-4 block w-1/4 pt-2 font-bold">
                        {{ t('blacklist.note') }}
                    </label>
                    <textarea class="block w-3/4 py-2 bg-gray-200 dark:bg-gray-600 border rounded" id="add_note" placeholder="Really bad guy i don't like him." v-model="form.note"></textarea>
                </div>

                <!-- Buttons -->
                <div class="flex items-center mt-2">
                    <button class="px-5 py-2 font-semibold text-white bg-success dark:bg-dark-success rounded mr-2" @click="confirmAdd">
                        <i class="mr-1 fas fa-plus"></i>
                        {{ t('global.confirm') }}
                    </button>
                    <button class="px-5 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-500 dark:bg-gray-500" @click="isAdding = false">
                        {{ t('global.cancel') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Import -->
        <div class="fixed bg-black bg-opacity-70 top-0 left-0 right-0 bottom-0 z-2k" v-if="isUploading">
            <div class="shadow-xl absolute bg-gray-100 dark:bg-gray-600 text-black dark:text-white left-2/4 top-2/4 -translate-x-2/4 -translate-y-2/4 transform p-6 rounded w-alert">
                <h3 class="mb-2">
                    {{ t('blacklist.import') }}
                </h3>

                <!-- License Identifier -->
                <div class="w-full p-3 flex justify-between px-0">
                    <label class="mr-4 block w-1/4 pt-2 font-bold" for="add_identifier">
                        {{ t('blacklist.file') }}
                    </label>
                    <input class="w-3/4 px-4 py-2 bg-gray-200 dark:bg-gray-600 rounded" id="import-file" type="file" accept=".csv,.txt" />
                </div>

                <!-- Buttons -->
                <div class="flex items-center mt-2">
                    <button class="px-5 py-2 font-semibold text-white bg-success dark:bg-dark-success rounded mr-2" @click="confirmImport">
                        <span v-if="!isUploadLoading">
                            <i class="mr-1 fas fa-cloud-upload-alt"></i>
                            {{ t('global.confirm') }}
                        </span>
                        <span v-else>
                            <i class="mr-1 fas fa-cog animate-spin"></i>
                            {{ t('blacklist.importing') }}
                        </span>
                    </button>

                    <button class="px-5 py-2 rounded hover:bg-gray-200 dark:hover:bg-gray-500 dark:bg-gray-500" @click="isUploading = false">
                        {{ t('global.cancel') }}
                    </button>
                </div>
            </div>
        </div>

        <v-section class="overflow-x-auto">
            <template #header>
                <h2>
                    {{ t('global.result') }}
                </h2>
                <p class="text-muted dark:text-dark-muted text-xs">
                    {{ t('global.results', time) }}
                </p>
            </template>

            <template>
                <table class="w-full whitespace-no-wrap">
                    <tr class="font-semibold text-left mobile:hidden">
                        <th class="p-3 pl-8">{{ t('blacklist.creator') }}</th>
                        <th class="p-3">{{ t('blacklist.identifier') }}</th>
                        <th class="p-3">{{ t('blacklist.reason') }}</th>
                        <th class="p-3">{{ t('blacklist.note') }}</th>
                        <th class="p-3">{{ t('blacklist.timestamp') }}</th>
                        <th class="w-24 p-3 pr-8"></th>
                    </tr>
                    <tr class="border-t border-gray-300 dark:border-gray-500 hover:bg-gray-200 dark:hover:bg-gray-600" v-for="identifier in identifiers" v-bind:key="identifier.id">
                        <td class="p-3 pl-8 mobile:block">
                            <inertia-link class="block px-4 py-2 font-semibold text-center text-white bg-indigo-600 rounded dark:bg-indigo-400" :href="'/players/' + identifier.creator.licenseIdentifier">
                                {{ identifier.creator.playerName }}
                            </inertia-link>
                        </td>
                        <td class="p-3 mobile:block">{{ identifier.identifier }}</td>
                        <td class="p-3 mobile:block">{{ identifier.reason }}</td>
                        <td class="p-3 mobile:block">{{ identifier.note }}</td>
                        <td class="p-3 mobile:block">{{ identifier.timestamp | formatTime(true) }}</td>
                        <td class="p-3 pr-8 mobile:block">
                            <button class="block px-4 py-2 font-semibold text-center text-white bg-danger rounded dark:bg-dark-danger" @click="removeIdentifier(identifier.id)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="identifiers.length === 0" class="border-t border-gray-300 dark:border-gray-500">
                        <td class="px-8 py-3 text-center mobile:block" colspan="100%">
                            {{ t('blacklist.none') }}
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
                        <inertia-link class="px-4 py-2 mr-3 font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" v-if="identifiers.length === 15" :href="links.next">
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
import Badge from './../../Components/Badge.vue';
import Pagination from './../../Components/Pagination.vue';

export default {
    layout: Layout,
    components: {
        VSection,
        Badge,
        Pagination,
    },
    props: {
        identifiers: {
            type: Array,
            required: true,
        },
        filters: {
            creator: String,
            identifier: String,
            reason: String,
            note: String,
        },
        time: {
            type: Number,
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
    },
    data() {
        return {
            isLoading: false,
            isAdding: false,
            isUploading: false,
            isUploadLoading: false,
            form: {
                identifier: '',
                reason: '',
                note: ''
            }
        };
    },
    methods: {
        async refresh() {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;
            try {
                await this.$inertia.replace('/blacklist', {
                    data: this.filters,
                    preserveState: true,
                    preserveScroll: true,
                    only: ['identifiers', 'time'],
                });
            } catch (e) { }

            this.isLoading = false;
        },
        readFileContents(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();

                reader.readAsText(file, "UTF-8");

                reader.onload = (evt) => {
                    resolve(evt.target.result);
                };

                reader.onerror = () => {
                    reject();
                };
            });
        },
        async confirmImport() {
            if (this.isUploadLoading) {
                return;
            }
            this.isUploadLoading = true;

            const files = $("#import-file")[0].files;
            const file = files.length > 0 ? files[0] : null;

            if (file) {
                try {
                    const text = await this.readFileContents(file);

                    if (text.startsWith("license_identifier,reason\n")) {
                        // Send request.
                        await this.$inertia.post('/blacklist/import', {
                            text: text
                        });

                        this.isUploading = false;

                        this.refresh();
                    } else {
                        alert("Invalid file!");
                    }
                } catch (e) {
                    alert("Failed to read file!");
                }
            } else {
                alert("No file selected!");
            }

            this.isUploadLoading = false;
        },
        async confirmAdd() {
            // Send request.
            await this.$inertia.post('/blacklist', this.form);

            // Reset.
            this.isAdding = false;
            this.form.reason = '';
            this.form.identifier = '';
            this.form.note = '';
        },
        async removeIdentifier(id) {
            if (!confirm(this.t('blacklist.confirm_remove'))) {
                return;
            }

            // Send request.
            await this.$inertia.delete(`/blacklist/${id}`);
        }
    }
}
</script>
