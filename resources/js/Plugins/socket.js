const Socket = {
    async install(Vue, options) {
        const cache = {};

        let originUnavailable = false;

        Vue.prototype.requestData = async function (route, useCache = false) {
            if (originUnavailable) return null;

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

                if (e.message === 'Network Error') {
                    originUnavailable = true;

                    console.info('Origin server is unavailable, aborting future requests.');

                    return null;
                }

                if (isDev) return null;

                return false;
            }
        };

        Vue.prototype.resolveHash = async function (hash) {
            try {
                const response = await axios.get('https://joaat.sh/api/unjoaat/' + hash);

                if (!response) return false;

                const data = response.data;

                if (!data || !data.hash || !data.string) return false;

                return {
                    name: data.string,
                    hash: data.hash
                };
            } catch (e) {
                return false;
            }
        };

        Vue.prototype.requestGenerated = async function (route) {
            if (!route.startsWith('/')) route = '/' + route;

            const isDev = window.location.hostname === 'localhost',
                host = isDev ? 'http://localhost:9999' : 'https://' + window.location.host,
                server = this.$page.auth.server;

            const url = host + '/generated/' + server + route;

            try {
                const response = await axios.get(url),
                    data = response.data;

                if (!data || typeof data !== 'object') return false;

                return data;
            } catch (e) {
                return false;
            }
        };
    },
}

export default Socket;
