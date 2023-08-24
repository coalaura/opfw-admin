const Socket = {
    async install(Vue, options) {
        const cache = {};

        Vue.prototype.requestData = async function(route, useCache = false) {
            if (!route.startsWith('/')) route = '/' + route;

            const isDev = window.location.hostname === 'localhost';

            const token = this.$page.auth.token,
                server = this.$page.auth.server,
                host = isDev ? 'http://localhost:9999' : 'https://' + window.location.host;

            const url = host + '/data/' + server + route + '?token=' + token;

            if (useCache && url in cache) {
                return cache[url];
            }

            try {
                const data = await axios.get(url);

                if (data.data && data.data.status) {
                    const value = data.data.data;

                    cache[url] = value;

                    return value;
                } else {
                    return false;
                }
            } catch (e) {
                console.log(`Error fetching data from ${url}: ${e.message}`);

                if ((e.response && e.response.status === 404) || e.message === 'Network Error') {
                    return null;
                }

                if (isDev) return null;

                return false;
            }
        };

        Vue.prototype.resolveHash = async function(hash) {
            const data = await this.requestData('/hash/' + hash, true);

            if (!data || !data.name) return false;

            return data;
        };
    },
}

export default Socket;
