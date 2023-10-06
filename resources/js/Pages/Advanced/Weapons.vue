<template>
    <div>

        <portal to="title">
            <h1 class="dark:text-white">
                {{ t('weapons.title') }}
            </h1>
            <p>
                {{ t('weapons.description') }}
            </p>
        </portal>

        <!-- Querying -->
        <v-section :noFooter="true" :noHeader="true">
            <template>
                <form @submit.prevent>
                    <div class="flex flex-wrap">
                        <!-- Weapon -->
                        <div class="w-1/4 px-3">
                            <input class="block w-full px-4 py-3 outline-none bg-gray-200 border-2 rounded dark:bg-gray-600 border-red-500" :class="{'border-green-500': valid}" placeholder="weapon_pistol" v-model="weaponName">
                        </div>

                        <!-- Search -->
                        <div class="w-1/4 px-3" v-if="valid">
                            <button class="px-4 py-3 w-full font-semibold text-white bg-success dark:bg-dark-success rounded hover:shadow-lg" @click="loadWeaponData">
                                <span v-if="!isLoading">
                                    <i class="fas fa-search"></i>
                                    {{ t('weapons.search') }}
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

        <v-section class="overflow-x-auto" v-if="error" :noHeader="true" :noFooter="true">
            <template>
                <p class="text-red-500 font-semibold">{{ error }}</p>
            </template>
        </v-section>

        <v-section class="overflow-x-auto" v-if="weaponData" :noHeader="true" :noFooter="true">
            <template>
                <p class="text-muted dark:text-dark-muted mb-3" v-html="damageDescription"></p>

                <div class="w-full relative">
                    <BarChart :data="weaponData.damages" :colors="['100, 235, 55', '235, 55, 55']" :title="t('weapons.damages')" class="w-full"></BarChart>

                    <div class="absolute bg-opacity-10" :class="highlight.color" :style="highlight" v-for="highlight in damageHighlights"></div>
                </div>
            </template>
        </v-section>

    </div>
</template>

<script>
import Layout from './../../Layouts/App';
import VSection from './../../Components/Section';
import BarChart from './../../Components/Charts/BarChart';

export default {
    layout: Layout,
    components: {
        VSection,
        BarChart,
    },
    props: {
        weapons: {
            type: [Object, Array],
            required: true,
        }
    },
    data() {
        const weaponList = Object.entries(this.weapons)
            .map(([hash, name]) => ({ hash, name }))
            .sort((a, b) => a.name.localeCompare(b.name));

        return {
            isLoading: false,
            weaponName: '',

            weaponList: weaponList,
            weaponData: null,
            error: false
        };
    },
    computed: {
        valid() {
            return !!this.getWeaponHash();
        },

        damageDescription() {
            const avg = `<i class="text-lime-500">${this.weaponData.damages.avg}hp</i>`,
                max = `<i class="text-rose-500">${this.weaponData.damages.max}hp</i>`;

            return this.t('weapons.damage_description', this.weaponData.name, avg, max);
        }
    },
    methods: {
        cleanupWeaponName() {
            this.weaponName = this.weaponName.toLowerCase().trim();
        },
        getWeaponHash() {
            this.cleanupWeaponName();

            const weapon = this.weaponList.find(weapon => weapon.name === this.weaponName);

            return weapon ? weapon.hash : null;
        },
        async loadWeaponData() {
            const hash = this.getWeaponHash();

            if (this.isLoading || !this.weaponName || !hash) return;

            this.isLoading = true;
            this.error = false;
            this.weaponData = null;

            const weaponName = this.weaponName;

            try {
                const response = await axios.get('/weapons/' + hash);

                const data = response.data;

                if (data && data.data && data.status) {
                    this.weaponData = data.data;
                    this.weaponData.name = weaponName;
                } else {
                    this.error = data?.message || 'Unknown error';
                }
            } catch (e) {
                console.error(e);
            }

            this.isLoading = false;
        }
    }
};
</script>
