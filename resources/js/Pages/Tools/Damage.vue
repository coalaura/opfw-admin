<template>
	<div>

		<portal to="title">
			<h1 class="dark:text-white">
				<i class="mr-3 fas fa-unlock-alt" :title="perm.restriction(perm.PERM_DAMAGE_LOGS)"></i>

				{{ t('damages.title') }}
			</h1>
			<p>
				{{ t('damages.description') }}
			</p>
		</portal>

		<!-- Querying -->
		<v-section :noFooter="true" :noHeader="true">
			<template>
				<form @submit.prevent autocomplete="off">
					<div class="flex flex-wrap mb-4">
						<!-- License -->
						<div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
							<label class="block mb-2" for="license">
								{{ t('damages.license') }}
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" id="license" placeholder="license:2ced2cabd90f1208e7e056485d4704c7e1284196" v-model="filters.license">
						</div>

						<!-- Before -->
						<div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
							<label class="block mb-2" for="before">
								{{ t('damages.before') }}
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" type="date" id="before" v-model="filters.before">
						</div>

						<!-- After -->
						<div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
							<label class="block mb-2" for="after">
								{{ t('damages.after') }}
							</label>
							<input class="block w-full px-4 py-3 bg-gray-200 border rounded dark:bg-gray-600" type="date" id="after" v-model="filters.after">
						</div>

						<div class="w-1/4 px-3 mobile:w-full mobile:mb-3">
							<label class="block mb-2">&nbsp;</label>
							<button class="px-4 py-3 w-full font-semibold text-white bg-success dark:bg-dark-success rounded hover:shadow-lg" @click="refresh">
								<span v-if="!isLoading">
									<i class="fas fa-search"></i>
									{{ t('damages.search') }}
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

		<v-section class="overflow-x-auto" :noHeader="true" :noFooter="true">
			<template>
				<div class="mb-3">
					{{ t("damages.details_for") }}
					<a class="font-semibold" :href="`/players/${details.target.license}`" target="_blank" v-if="details.target">
						{{ details.target.name }}
					</a>
					<span class="font-semibold" v-else>
						{{ t("damages.everyone") }}
					</span>

					<template v-if="!details.before && !details.after">{{ t("damages.all_time") }}.</template>

					<template v-if="details.before">
						{{ t("damages.details_before") }}
						<span class="font-semibold">{{ details.before }}</span><template v-if="!details.after">.</template>
						<template v-if="details.after">
							{{ t("damages.and") }}
						</template>
					</template>

					<template v-if="details.after">
						{{ t("damages.details_after") }}
						<span class="font-semibold">{{ details.after }}</span>.
					</template>
				</div>

				<div class="w-max relative" v-if="!loadedTextures">
					{{ t("damages.loading_textures") }}
				</div>
				<div class="w-max relative" v-else-if="failedTextures">
					{{ t("damages.failed_textures") }}
				</div>
				<div class="w-max relative" v-else>
					<canvas ref="canvas"></canvas>

					<div class="absolute cursor-pointer transition-colors hover:bg-black/10 font-mono flex justify-center items-center" :style="data.style" @click="toggleComponent(component)" v-for="(data, component) in components" :key="component">
						{{ component }}
					</div>

					<template v-if="active !== false">
						<div class="absolute h-px right-0 bg-black/50 transform -translate-y-1/2 transition-position" :style="components[active].center"></div>

						<div class="absolute left-full w-max text-sm text-justify bg-black/20 px-2 py-1.5 transform -translate-y-1/2 transition-position" :style="{top: components[active].center.top}" v-html="components[active].details"></div>
					</template>
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
        VSection,
    },
    props: {
        filters: {
            license: String,
            before: String,
			after: String,
        },
		details: {
			type: Object,
			required: true,
		},
        damages: {
            type: Array,
            required: true,
        },
        names: {
            type: Array,
            required: true,
        }
    },
	computed: {
		total() {
			return this.damages.reduce((acc, val) => (acc || 0) + val);
		},
		max() {
			return Math.max(...this.damages);
		}
	},
    data() {
        return {
            isLoading: false,
			failedTextures: false,
			loadedTextures: false,

			active: false,
			textures: {},
			components: [],
        };
    },
    methods: {
		toggleComponent(component) {
			if (this.active === component) {
				if (component === 7) {
					this.active = 0;

					return;
				}

				this.active = false;

				return;
			} else if (this.active === 0 && component === 7) {
				this.active = false;

				return;
			}

			this.active = component;
		},
		updateComponent(component, texture, amount) {
			const canvas = new OffscreenCanvas(texture.width, texture.height),
				ctx = canvas.getContext("2d");

			ctx.drawImage(texture, 0, 0);

			const image = ctx.getImageData(0, 0, canvas.width, canvas.height),
				data = image.data;

			const min = {x: Infinity, y: Infinity},
				max = {x: 0, y: 0};

			for (let y = 0; y < canvas.height; y++) {
				for (let x = 0; x < canvas.width; x++) {
					const isSet = data[(y * canvas.width + x) * 4 + 3] > 127;

					if (isSet) {
						min.x = Math.min(min.x, x);
						min.y = Math.min(min.y, y);

						max.x = Math.max(max.x, x);
						max.y = Math.max(max.y, y);
					}
				}
			}

			const metadata = {},
				width = max.x - min.x,
				height = max.y - min.y;

			metadata.details = `<span class="font-semibold">${(amount / this.total * 100).toFixed(2)}%</span> (${this.numberFormat(amount)} of ${this.numberFormat(this.total)}) of damage was dealt to the ${this.names[component].toLowerCase()}.`;

			metadata.center = {
				top: `${((min.y + height / 2) / canvas.height * 100).toFixed(2)}%`,
				left: `${((min.x + width) / canvas.width * 100).toFixed(2)}%`,
			};

			metadata.style = {
				top: `${(min.y / canvas.height * 100).toFixed(2)}%`,
				left: `${(min.x / canvas.width * 100).toFixed(2)}%`,
				width: `${(width / canvas.width * 100).toFixed(2)}%`,
				height: `${(height / canvas.height * 100).toFixed(2)}%`,
			};

			this.$set(this.components, component, metadata);
		},
		render() {
			if (!this.loadedTextures || this.failedTextures) return;

			const canvas = this.$refs.canvas,
				ctx = canvas.getContext("2d");

			const base = this.textures["base"];

			canvas.width = base.width;
			canvas.height = base.height;

			ctx.clearRect(0, 0, canvas.width, canvas.height);

			ctx.drawImage(base, 0, 0);

			if (this.max === 0) return;

			for (let component = 0; component < this.damages.length; component++) {
				const amount = this.damages[component],
					texture = this.textures[component];

				this.updateComponent(component, texture, amount);

				if (!amount) continue;

				const opacity = Math.min(1, Math.max(0, amount / this.max)),
					temp = new OffscreenCanvas(canvas.width, canvas.height),
					tempCtx = temp.getContext("2d");

				tempCtx.fillStyle = "red";
				tempCtx.fillRect(0, 0, temp.width, temp.height);

				tempCtx.filter = "blur(5px)";

				tempCtx.globalCompositeOperation = "destination-in";
    			tempCtx.drawImage(texture, 0, 0);
				tempCtx.globalCompositeOperation = "source-over";

				ctx.globalAlpha = opacity;
				ctx.drawImage(temp, 0, 0);
				ctx.globalAlpha = 1.0;
			}
		},
		async refresh() {
			if (this.isLoading) {
				return;
			}

			this.isLoading = true;

			try {
				await this.$inertia.replace('/damages', {
					data: this.filters,
					preserveState: true,
					preserveScroll: true,
					only: ['damages', 'details'],
				});

				this.render();
			} catch (e) {
			}

			this.isLoading = false;
		},
		loadTexture(texture, cb) {
			const image = new Image();

			image.onload = () => {
				this.textures[texture] = image;

				cb(true);
			};

			image.onerror = error => {
				this.textures[texture] = false;

				this.failedTextures = true;

				cb(false);

				console.error(`Failed to load ${texture}.png: ${error}`);
			};

			image.src = `/images/damage/${texture}.png`;
		}
    },
	mounted() {
		const textures = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "base"];

		let failed = 0,
			done = 0;

		const complete = loaded => {
			if (loaded) {
				done++;
			} else {
				failed++;
			}

			if (done + failed === textures.length) {
				this.loadedTextures = true;

				this.$nextUpdate(() => {
					this.render();
				});
			}
		};

		this.loadTexture("base", complete);

		for (let component = 0; component <= 20; component++) {
			this.loadTexture(component, complete);
		}
	}
};
</script>
