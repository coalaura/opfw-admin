const Settings = {
    async install(Vue, options) {
        const settings = options.props.auth.settings;

        Vue.prototype.setting = key => {
            if (!settings || !(key in settings)) return null;

            return settings[key].value;
        };

        Vue.prototype.pageId = (prefix = "") => {
            let id = window.location.pathname
                .replace(/[^\w]+/g, "_")
                .replace(/^_+|_+$/gm, "");

            if (prefix) {
                id = `${prefix}_${id}`;
            }

            return id;
        };
    },
}

export default Settings;
