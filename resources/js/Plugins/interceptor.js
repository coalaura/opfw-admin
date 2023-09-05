const Interceptor = {
    async install(Vue, options) {
        const _replace = Vue.prototype.$inertia.replace;

        Vue.prototype.$inertia.replace = function (url, options = {}) {
            const data = options.data;

            if (data) {
                for (const key in data) {
                    const value = data[key];

                    if (value === "") data[key] = null;
                }
            }

            _replace.call(this, url, options);
        };
    },
}

export default Interceptor;
