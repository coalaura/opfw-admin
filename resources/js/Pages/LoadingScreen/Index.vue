<template>
    <div>
        <portal to="title">
            <h1 class="dark:text-white">
                <i class="mr-3 fas fa-unlock-alt" :title="perm.restriction(perm.PERM_LOADING_SCREEN)"></i>

                {{ t('loading_screen.title') }}
            </h1>
            <p>
                {{ t('loading_screen.description') }}
            </p>
        </portal>

        <portal to="actions">
            <button class="px-4 py-2 text-sm font-semibold text-white bg-green-600 rounded dark:bg-green-400 mr-1" type="button" @click="isAdding = true">
                <i class="mr-1 fa fa-plus"></i>
                {{ t('loading_screen.add') }}
            </button>

            <button class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded dark:bg-indigo-400" type="button" @click="refresh">
                <span v-if="!isLoading">
                    <i class="mr-1 fa fa-redo-alt"></i>
                    {{ t('logs.refresh') }}
                </span>
                <span v-else>
                    <i class="fas fa-cog animate-spin"></i>
                    {{ t('global.loading') }}
                </span>
            </button>
        </portal>

        <template>
            <h2 class="mb-4 max-w-screen-xl m-auto text-2xl">
                {{ t('loading_screen.pictures') }}
                <sup>{{ pictures.length }}</sup>
            </h2>

            <div v-if="failedLoadCount > 0" class="badge px-5 py-1 border-2 max-w-screen-xl m-auto mb-3 rounded border-red-200 bg-danger-pale dark:bg-dark-danger-pale">
                {{ t("loading_screen.failed_count", failedLoadCount, pictures.length) }}
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 max-w-screen-xl m-auto">
                <div class="relative group rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800 border-2 transition-all duration-200 hover:shadow-lg" :class="{
                    'border-gray-300 dark:border-gray-600': !picture.included && !picture.excluded,
                    'border-green-500 ring-2 ring-green-500/50': picture.included,
                    'border-red-500 ring-2 ring-red-500/50': picture.excluded,
                    'opacity-60 grayscale': !isActive(picture)
                }" v-for="(picture, index) in pictures" :key="picture.id">
                    <!-- Include/Exclude Toggles -->
                    <div class="absolute top-2 left-2 flex gap-1 z-10">
                        <button @click.stop="toggleInclude(picture)" class="w-8 h-8 flex items-center justify-center text-white text-xs rounded shadow transition-all duration-200"
                            :class="picture.included ? 'bg-green-600 dark:bg-green-500' : 'bg-gray-600/80 dark:bg-gray-500/80 opacity-0 group-hover:opacity-100'" :title="t('loading_screen.include')">
                            <i class="fas" :class="picture.included ? 'fa-check-circle' : 'fa-check'"></i>
                        </button>
                        <button @click.stop="toggleExclude(picture)" class="w-8 h-8 flex items-center justify-center text-white text-xs rounded shadow transition-all duration-200"
                            :class="picture.excluded ? 'bg-red-600 dark:bg-red-500' : 'bg-gray-600/80 dark:bg-gray-500/80 opacity-0 group-hover:opacity-100'" :title="t('loading_screen.exclude')">
                            <i class="fas fa-ban"></i>
                        </button>
                    </div>

                    <!-- Edit/Delete Buttons -->
                    <div class="absolute top-2 right-2 flex gap-1 z-10 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                        <button class="w-8 h-8 flex items-center justify-center text-white text-xs bg-yellow-600 dark:bg-yellow-500 rounded shadow hover:bg-yellow-700 dark:hover:bg-yellow-600 transition-colors"
                            @click.stop="editingPicture = picture; isEditingPicture = true" :title="t('loading_screen.edit')">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <button class="w-8 h-8 flex items-center justify-center text-white text-xs bg-red-600 dark:bg-red-500 rounded shadow hover:bg-red-700 dark:hover:bg-red-600 transition-colors"
                            @click.stop="deletePicture($event, picture.id)" :title="t('loading_screen.remove')">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>

                    <!-- Status Badges -->
                    <div v-if="picture.included" class="absolute bottom-2 left-2 px-2 py-1 bg-green-600 text-white text-xs rounded shadow z-10 font-semibold">
                        <i class="fas fa-check mr-1"></i>{{ t('loading_screen.included') }}
                    </div>
                    <div v-else-if="picture.excluded" class="absolute bottom-2 left-2 px-2 py-1 bg-red-600 text-white text-xs rounded shadow z-10 font-semibold">
                        <i class="fas fa-ban mr-1"></i>{{ t('loading_screen.excluded') }}
                    </div>
                    <div v-else-if="hasIncluded" class="absolute bottom-2 left-2 px-2 py-1 bg-gray-500 text-white text-xs rounded shadow z-10 font-semibold">
                        <i class="fas fa-times mr-1"></i>{{ t('loading_screen.inactive') }}
                    </div>

                    <a class="block" target="_blank" :href="picture.image_url">
                        <div class="aspect-video w-full overflow-hidden bg-gray-200 dark:bg-gray-700 relative">
                            <video :src="picture.image_url" class="w-full h-full object-cover" @error="imageFailed(picture.id)" v-if="isURLVideo(picture.image_url)" controls></video>
                            <img :src="picture.image_url" class="w-full h-full object-cover" @error="imageFailed(picture.id)" loading="lazy" v-else />
                        </div>

                        <div class="p-3">
                            <span class="block text-sm text-gray-600 dark:text-gray-300 line-clamp-2" :class="{ 'italic': !picture.description }">
                                {{ picture.description || t("loading_screen.no_description") }}
                            </span>

                            <span v-if="failedLoad[picture.id]" class="block text-sm text-red-400 mt-2 italic">
                                <i class="fas fa-skull-crossbones"></i>
                                {{ t("loading_screen.failed_count_label") }}
                            </span>
                        </div>
                    </a>
                </div>

                <div v-if="pictures.length === 0" class="col-span-full text-center py-8">
                    <img :src="'/images/no-pictures.png'" class="w-full max-w-md mx-auto opacity-50" />
                </div>
            </div>
        </template>

        <modal :show.sync="isAdding">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('loading_screen.add') }}
                </h1>
            </template>

            <template #default>
                <video :src="image_url" v-if="image_url && image_url.startsWith('https://') && isURLVideo(image_url)" class="w-full max-h-96 mb-3" controls></video>
                <img v-else-if="image_url && image_url.startsWith('https://')" :src="image_url" class="w-full max-h-96 mb-3" />

                <div>
                    <label class="block mb-3" for="url">
                        {{ t('loading_screen.picture') }}
                    </label>
                    <input class="block w-full px-4 py-3 mb-3 bg-gray-200 border rounded dark:bg-gray-600" type="url" id="url" placeholder="https://images.unsplash.com/photo-1511044568932-338cba0ad803" v-model="image_url" required>
                </div>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="isAdding = false">
                    {{ t('global.cancel') }}
                </button>
                <button type="submit" class="px-5 py-2 text-white bg-indigo-600 rounded dark:bg-indigo-400" @click="handleAdd">
                    <span v-if="!isLoading">
                        <i class="mr-1 fa fa-plus"></i>
                        {{ t('loading_screen.do_add') }}
                    </span>
                    <span v-else>
                        <i class="fas fa-cog animate-spin"></i>
                        {{ t('global.loading') }}
                    </span>
                </button>
            </template>
        </modal>

        <modal :show.sync="isEditingPicture">
            <template #header>
                <h1 class="dark:text-white">
                    {{ t('loading_screen.edit') }}
                </h1>
            </template>

            <template #default>
                <video :src="editingPicture.image_url" v-if="editingPicture.image_url && editingPicture.image_url.startsWith('https://') && isURLVideo(editingPicture.image_url)" class="w-full max-h-96 mb-3" controls></video>
                <img v-else-if="editingPicture.image_url && editingPicture.image_url.startsWith('https://')" :src="editingPicture.image_url" class="w-full max-h-96 mb-3" />

                <div>
                    <label class="block mb-3" for="image_url">
                        {{ t('loading_screen.picture') }}
                    </label>
                    <input class="block w-full px-4 py-3 mb-3 bg-gray-200 border rounded dark:bg-gray-600" type="url" id="image_url" placeholder="https://images.unsplash.com/photo-1511044568932-338cba0ad803"
                        v-model="editingPicture.image_url" required>
                </div>

                <div v-if="!isURLVideo(editingPicture.image_url)">
                    <label class="block mb-3" for="description">
                        {{ t('loading_screen.image_description') }}
                    </label>
                    <input class="block w-full px-4 py-3 mb-3 bg-gray-200 border rounded dark:bg-gray-600" type="text" id="description" placeholder="Very cool picture" v-model="editingPicture.description">
                </div>
            </template>

            <template #actions>
                <button type="button" class="px-5 py-2 rounded hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-400" @click="isEditingPicture = false">
                    {{ t('global.cancel') }}
                </button>
                <button type="submit" class="px-5 py-2 text-white bg-indigo-600 rounded dark:bg-indigo-400" @click="handleEdit">
                    <span v-if="!isLoading">
                        <i class="mr-1 fa fa-pencil-alt"></i>
                        {{ t('loading_screen.edit') }}
                    </span>
                    <span v-else>
                        <i class="fas fa-cog animate-spin"></i>
                        {{ t('global.loading') }}
                    </span>
                </button>
            </template>
        </modal>
    </div>
</template>

<script>
import Layout from './../../Layouts/App.vue';
import VSection from './../../Components/Section.vue';
import Modal from '../../Components/Modal.vue';

export default {
    layout: Layout,
    components: {
        VSection,
        Modal,
    },
    props: {
        pictures: {
            type: Array,
            required: true,
        },
    },
    data() {
        return {
            isLoading: false,
            isAdding: false,

            failedLoad: {},
            failedLoadCount: 0,

            isEditingPicture: false,
            editingPicture: false,

            image_url: '',
        };
    },
    computed: {
        hasIncluded() {
            return this.pictures.some(p => p.included);
        }
    },
    methods: {
        isURLVideo(url) {
            const test = url.split("?").shift();

            return !!test.match(/\.(mp4|webm)$/);
        },
        isActive(picture) {
            if (picture.excluded) {
                return false;
            }

            if (this.hasIncluded && !picture.included) {
                return false;
            }

            return true;
        },
        async toggleInclude(picture) {
            picture.included = !picture.included;

            if (picture.included) {
                picture.excluded = false;
            }

            await this.savePictureStatus(picture);
        },
        async toggleExclude(picture) {
            picture.excluded = !picture.excluded;

            if (picture.excluded) {
                picture.included = false;
            }

            await this.savePictureStatus(picture);
        },
        async savePictureStatus(picture) {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;

            try {
                await this.$inertia.put(`/loading_screen/${picture.id}`, {
                    image_url: picture.image_url,
                    description: picture.description,
                    included: picture.included,
                    excluded: picture.excluded,
                }, {
                    preserveScroll: true
                });
            } catch (e) {}

            this.isLoading = false;
        },
        async deletePicture(e, id) {
            e.preventDefault();

            if (this.isLoading) {
                return;
            }

            this.isLoading = true;

            try {
                await this.$inertia.delete(`/loading_screen/${id}`);

                if (this.failedLoad[id]) {
                    delete this.failedLoad[id];

                    this.failedLoadCount = Object.values(this.failedLoad).length;
                }
            } catch (e) { }

            this.isLoading = false;
        },
        imageFailed(id) {
            this.failedLoad[id] = true;

            this.failedLoadCount = Object.values(this.failedLoad).length;
        },
        async handleAdd() {
            const url = this.image_url.trim();

            if (!url || !url.startsWith("https://")) {
                alert("Please enter a valid URL");

                return;
            }

            if (this.isLoading) {
                return;
            }

            this.isLoading = true;

            try {
                await this.$inertia.post('/loading_screen', {
                    image_url: url,
                });
            } catch (e) { }

            this.isLoading = false;
            this.isAdding = false;
        },
        async handleEdit() {
            const url = this.editingPicture.image_url.trim();
            const description = this.editingPicture.description?.trim() || "";

            if (!url || !url.startsWith("https://")) {
                alert("Please enter a valid URL");

                return;
            }

            if (this.isLoading) {
                return;
            }

            this.isLoading = true;

            try {
                await this.$inertia.put(`/loading_screen/${this.editingPicture.id}`, {
                    image_url: url,
                    description: description,
                });
            } catch (e) { }

            this.isLoading = false;
            this.isEditingPicture = false;
            this.editingPicture = false;
        },
        async refresh() {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;

            try {
                await this.$inertia.replace('/loading_screen', {
                    data: this.filters,
                    preserveState: true,
                    preserveScroll: true,
                    only: ['pictures'],
                });
            } catch (e) { }

            this.isLoading = false;
        },
    },
}
</script>