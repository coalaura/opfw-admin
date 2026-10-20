const permissionMethods = {
	PERM_LIVEMAP: 'livemap',
	PERM_SCREENSHOT: 'screenshot',
	PERM_SUSPICIOUS: 'suspicious',
	PERM_ADVANCED: 'advanced',
	PERM_LOCK_BAN: 'lock_ban',
	PERM_EDIT_TAG: 'edit_tag',
	PERM_LOADING_SCREEN: 'loading_screen',
	PERM_VIEW_QUEUE: 'view_queue',
	PERM_Y: 'y',
	PERM_Y_VERIFY: 'y_verify',
	PERM_Y_EDIT: 'y_edit',
	PERM_LINKED: 'linked',
	PERM_ANNOUNCEMENT: 'announcement',
	PERM_DAMAGE_LOGS: 'damage_logs',
	PERM_EXPLOSION_EVENTS: 'explosion_events',
	PERM_CRAFTING: 'crafting',
	PERM_PHONE_LOGS: 'phone_logs',
	PERM_MONEY_LOGS: 'money_logs',
	PERM_ANTI_CHEAT: 'anti_cheat',
	PERM_DARK_CHAT: 'dark_chat',
	PERM_BAN_EXCEPTION: 'ban_exception',
	PERM_WHITELIST: 'whitelist',
	PERM_API_TOKENS: 'api_tokens',
	PERM_REALTY_EDIT: 'realty_edit',
	PERM_SAVINGS_LOGS: 'savings_logs',
	PERM_BULK_DELETE: 'bulk_delete',
	PERM_VIEW_INVENTORY: 'view_inventory',
	PERM_EDIT_INVENTORY: 'edit_inventory',
	PERM_DIVORCE_CHARACTERS: 'divorce_characters',
	PERM_REVIVE_OFFLINE: 'revive_offline',
	PERM_MANAGE_VEHICLES: 'manage_vehicles',
	PERM_EDIT_PED_MODEL: 'edit_ped_model',
	PERM_RESET_VEHICLE_GARAGE: 'reset_vehicle_garage',
	PERM_SUSPICIOUS_ENTITIES: 'suspicious_entities',
	PERM_EDIT_COMPANY_BALANCE: 'edit_company_balance',
	PERM_EDIT_SAVINGS_BALANCE: 'edit_savings_balance',
	PERM_VIEW_AUDIT_LOGS: 'view_audit_logs',

	level(permission) {
		return this.auth.permissions?.[permission] ?? 99;
	},

	check(permission) {
		return this.auth.permissionAccess?.[permission] === true;
	},
};

const Permissions = {
	install(Vue, options) {
		const methods = {
			...permissionMethods,
			restriction(permission) {
				switch (this.level(permission)) {
					case 1:
						return Vue.prototype.t('global.restricted_to_staff');
					case 2:
						return Vue.prototype.t('global.restricted_to_senior_staff');
					case 3:
						return Vue.prototype.t('global.restricted_to_super');
					case 4:
						return Vue.prototype.t('global.restricted_to_root');
					case 99:
						return Vue.prototype.t('roles.permission_disabled');
				}

				return Vue.prototype.t('global.not_restricted');
			}
		};

		Vue.mixin({
			beforeCreate() {
				// Resolve current auth on access, including checks made during data initialization.
				this.perm = Object.create(methods, {
					auth: {
						get: () => (this.$page ?? options.props).auth ?? {}
					}
				});
			}
		});
	}
};

export default Permissions;
