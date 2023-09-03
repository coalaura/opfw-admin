const Settings = {
    async install(Vue, options) {
        const settings = options.props.auth.settings;

        Vue.prototype.setting = key => {
            return settings[key].value;
        };
    },
}

export default Settings;
