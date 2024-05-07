const Socket = {
    async install(Vue, options) {
        let originUnavailable = false;

        async function executeRequest(vue, type, route) {
            if (originUnavailable) return null;

            route = route.replace(/^\/|\/$/, '');

            const isDev = window.location.hostname === 'localhost';

            const token = vue.$page.auth.token,
                server = vue.$page.auth.server,
                host = isDev ? 'http://localhost:9999' : 'https://' + window.location.host,
                query = type === "data" ? `?token=${token}`: '';

            const url = `${host}/socket/${server}/${type}/${route}${query}`;

            try {
                const data = await axios.get(url);

                if (data.data && data.data.status) {
                    const value = data.data.data;

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

        Vue.prototype.requestData = async function (route) {
            return await executeRequest(this, "data", route);
        };

        Vue.prototype.requestStatic = async function (route) {
            return await executeRequest(this, "static", route);
        };

        Vue.prototype.requestMisc = async function (route) {
            return await executeRequest(this, "misc", route);
        };
    },
}

export default Socket;
