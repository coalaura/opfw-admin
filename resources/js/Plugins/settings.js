const Settings = {
    async install(Vue, options) {
        const settings = options.props.auth.settings;

        Vue.prototype.setting = key => {
            if (!settings || !(key in settings)) return null;

            return settings[key].value;
        };
    },
}

export default Settings;
