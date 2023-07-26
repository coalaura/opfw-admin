const Socket = {
    async install(Vue, options) {
        Vue.prototype.requestData = async function(route) {
            if (!route.startsWith('/')) route = '/' + route;

            const isDev = window.location.hostname === 'localhost';

            const token = this.$page.auth.token,
                server = this.$page.auth.server,
                host = isDev ? 'http://localhost:9999' : 'https://' + window.location.host;

            const url = host + '/data/' + server + route + '?token=' + token;

            try {
                const data = await axios.get(url);

                if (data.data && data.data.status) {
                    return data.data.data
                } else {
                    return false;
                }
            } catch (e) {
                console.error(e);

                return false;
            }
        };
    },
}

export default Socket;
