const Socket = {
    async install(Vue, options) {
        let originUnavailable = false;

        async function executeRequest(vue, type, route, throwError) {
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

                const contentType = data.headers['content-type'];

                // Its a text response
                if (contentType && contentType.indexOf('text/plain') !== -1) {
                    return data.data;
                }

                if (data.data && data.data.status) {
                    return data.data.data;
                } else {
                    if (throwError) throw new Error(data?.data?.error || 'Unknown error');

                    return false;
                }
            } catch (e) {
                console.log(`Error fetching data from ${url}: ${e.message}`);

                if (e.message === 'Network Error') {
                    originUnavailable = isDev;

                    console.info('Origin server is unavailable, aborting future requests.');

                    return null;
                }

                if (throwError) throw e;

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

        Vue.prototype.requestData = async function (route, throwError = false) {
            return await executeRequest(this, "data", route, throwError);
        };

        Vue.prototype.requestStatic = async function (route, throwError = false) {
            return await executeRequest(this, "static", route, throwError);
        };

        Vue.prototype.requestMisc = async function (route, throwError = false) {
            return await executeRequest(this, "misc", route, throwError);
        };
    },
}

export default Socket;
