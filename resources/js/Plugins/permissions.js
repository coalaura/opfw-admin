const Permissions = {
    async install(Vue, options) {
        const permissions = options.props.auth.permissions;

        function getPermissionLevel() {
            if (!options.props.auth || !options.props.auth.player) {
                return 0;
            }

            if (options.props.auth.player.isRoot) {
                return 4;
            } else if (options.props.auth.player.isSuperAdmin) {
                return 3;
            } else if (options.props.auth.player.isSeniorStaff) {
                return 2;
            } else if (options.props.auth.player.isStaff) {
                return 1;
            }

            return 0;
        }

        const permissionLevel = getPermissionLevel();

        Vue.prototype.perm = {
            PERM_SOFT_BAN: 'soft_ban',
            PERM_LIVEMAP: 'livemap',
            PERM_SCREENSHOT: 'screenshot',
            PERM_SUSPICIOUS: 'suspicious',
            PERM_ADVANCED: 'advanced',
            PERM_LOCK_BAN: 'lock_ban',
            PERM_EDIT_TAG: 'edit_tag',
            PERM_LOADING_SCREEN: 'loading_screen',
            PERM_VIEW_QUEUE: 'view_queue',
            PERM_TWITTER: 'twitter',
            PERM_TWITTER_VERIFY: 'twitter_verify',
            PERM_TWITTER_EDIT: 'twitter_edit',
            PERM_LINKED: 'linked',
            PERM_ANNOUNCEMENT: 'announcement',
            PERM_DAMAGE_LOGS: 'damage_logs',
            PERM_CRAFTING: 'crafting',
            PERM_PHONE_LOGS: 'phone_logs',
            PERM_MONEY_LOGS: 'money_logs',
            PERM_ANTI_CHEAT: 'anti_cheat',
            PERM_DARK_CHAT: 'dark_chat',
            PERM_BAN_EXCEPTION: 'ban_exception',
            PERM_WHITELIST: 'whitelist',
            PERM_API_TOKENS: 'api_tokens',

            level(permission) {
                if (!(permission in permissions)) {
                    return 0;
                }

                return permissions[permission];
            },

            check(permission) {
                return this.level(permission) <= permissionLevel;
            },

            restriction(permission) {
                const level = this.level(permission);

                switch (level) {
                    case 1:
                        return Vue.prototype.t('global.restricted_to_staff');
                    case 2:
                        return Vue.prototype.t('global.restricted_to_senior_staff');
                    case 3:
                        return Vue.prototype.t('global.restricted_to_super');
                    case 4:
                        return Vue.prototype.t('global.restricted_to_root');
                }

                return Vue.prototype.t('global.not_restricted');
            }
        };
    },
}

export default Permissions;
