<template>
	<Modal :show="true" extra-class="!max-w-6xl" @update:show="close">
		<template #header>
			<h2 class="text-xl font-bold dark:text-white">{{ t('roles.permissions_for', player.name) }}</h2>
		</template>
		<p class="mt-2">{{ t('roles.permissions_description') }}</p>
		<p class="mt-2 text-warning" v-if="!player.permission_level">{{ t('roles.permissions_requires_staff') }}</p>

		<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 my-4">
			<label v-for="(level, permission) in permissions" :key="permission" class="flex items-start gap-3 p-3 rounded-md border transition-colors" :class="[selected.includes(permission) ? 'border-primary bg-blue-50 dark:border-dark-primary dark:bg-gray-800' : 'border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-700', saving || !canEdit(permission) ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:border-primary dark:hover:border-dark-primary']">
				<input type="checkbox" class="form-checkbox flex-shrink-0 w-5 h-5 mt-0.5 rounded border-2 border-gray-400 bg-white text-primary dark:border-gray-500 dark:bg-gray-900 dark:text-dark-primary checked:bg-primary dark:checked:bg-dark-primary checked:border-transparent dark:checked:border-transparent focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-gray-100 dark:focus:ring-offset-gray-800 cursor-pointer disabled:cursor-not-allowed" :value="permission" v-model="selected" :disabled="saving || !canEdit(permission)" />
				<span class="min-w-0">
					<span class="block font-bold">{{ t(`roles.permissions.${permission}`) }}</span>
					<code class="block font-mono text-[10px] leading-tight text-gray-500 dark:text-gray-400 break-all">{{ `P_${permission.toUpperCase()}` }}</code>
					<span class="block mt-2 mb-2 text-xs leading-tight text-gray-600 dark:text-gray-300">{{ t(`roles.permission_descriptions.${permission}`) }}</span>
					<small class="block">{{ perm.restriction(permission) }}</small>
					<small class="block text-success" v-if="isInherited(permission)">{{ t('roles.permission_inherited') }}</small>
				</span>
			</label>
		</div>

		<p class="text-danger mb-3" role="alert" v-if="error">{{ error }}</p>
		<p class="text-success mb-3" role="status" v-if="saved && !hasChanges">{{ t('roles.permissions_saved') }}</p>
		<template #actions>
			<button class="px-3 py-1 rounded bg-success dark:bg-dark-success" @click="save" :disabled="saving || !hasChanges">
				<i class="fas fa-spinner fa-spin mr-1" v-if="saving"></i>
				{{ t('roles.save') }}
			</button>
			<button class="px-3 py-1 rounded bg-secondary dark:bg-dark-secondary" @click="reset" :disabled="saving || !hasChanges">{{ t('roles.reset') }}</button>
			<button class="px-3 py-1 rounded bg-secondary dark:bg-dark-secondary" @click="close" :disabled="saving">{{ t('roles.close_permissions') }}</button>
		</template>
	</Modal>
</template>

<script>
import Modal from './../../Components/Modal.vue';

export default {
	components: {
		Modal: Modal
	},
	props: {
		player: { type: Object, required: true },
		permissions: { type: Object, required: true },
		inheritance: { type: Object, required: true },
		grantable: { type: Array, required: true }
	},
	data() {
		return {
			selected: [...this.player.panel_permissions],
			saving: false,
			saved: false,
			error: ''
		};
	},
	computed: {
		hasChanges() {
			const current = this.player.panel_permissions;

			return current.length !== this.selected.length || this.selected.some(permission => !current.includes(permission));
		}
	},
	methods: {
		isInherited(permission) {
			const level = this.permissions[permission];

			if (!this.player.permission_level || level === 99) {
				return false;
			}

			if (level <= this.player.permission_level) {
				return true;
			}

			return this.isInheritedFromPermission(permission);
		},
		isInheritedFromPermission(permission, explicitOnly = false) {
			if (!this.player.permission_level || this.permissions[permission] === 99) {
				return false;
			}

			return (this.inheritance[permission] ?? []).some(source => {
				if (this.permissions[source] === 99) {
					return false;
				}

				return this.selected.includes(source) || (explicitOnly ? this.isInheritedFromPermission(source, true) : this.isInherited(source));
			});
		},
		close() {
			if (!this.saving) {
				this.$emit('close');
			}
		},
		canEdit(permission) {
			if (this.isInheritedFromPermission(permission, true)) {
				return false;
			}

			if (this.grantable.includes(permission)) {
				return true;
			}

			return this.permissions[permission] === 99 && this.player.panel_permissions.includes(permission);
		},
		reset() {
			this.selected = [...this.player.panel_permissions];
			this.error = '';
			this.saved = false;
		},
		async save() {
			if (this.saving || !this.hasChanges) {
				return;
			}

			this.saving = true;
			this.error = '';
			this.saved = false;
			this.$emit('saving', true);

			try {
				const response = await _put(`/roles/${this.player.license}/permissions`, JSON.stringify({ panel_permissions: this.selected }));

				if (response.status) {
					this.$emit('saved', [...this.selected]);
					this.saved = true;
				} else {
					this.error = response.message || this.t('roles.permissions_failed');
				}
			} catch (error) {
				this.error = this.t('roles.permissions_failed');
			} finally {
				this.saving = false;
				this.$emit('saving', false);
			}
		}
	}
};
</script>
