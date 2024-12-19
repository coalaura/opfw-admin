const Socket = {
    async install(Vue, options) {
        async function executeRequest(vue, type, route, throwError) {
            if (!vue.$page.auth.socket) {
                if (throwError) {
                    throw new Error('Socket unavailable');
                }

                return false;
            }

            route = route.replace(/^\/|\/$/, '');

            const isDev = window.location.hostname === 'localhost';

            const token = vue.$page.auth.token,
                server = vue.$page.serverName,
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
                    if (throwError) {
                        throw new Error(data?.data?.error || 'Unknown error');
                    }

                    return false;
                }
            } catch (err) {
                console.log(`Error fetching data from ${url}: ${err.message}`);

                if (throwError) {
                    throw err;
                }

                return false;
            }
        };

        Vue.prototype.resolveHash = async function (hash) {
            try {
                const response = await fetch('https://joaat.sh/j/reverse/', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        hash: hash
                    })
                }).then(response => response.json());

                if (!response || !Array.isArray(response)) return false;

                const names = response[0];

                if (!names || !Array.isArray(names)) return false;

                return {
                    name: names[0],
                    hash: hash
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
