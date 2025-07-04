import DataCompressor from "../Pages/Map/DataCompressor.js";

import { io } from "socket.io-client";

const Socket = {
	async install(Vue, options) {
		const isDev = window.location.hostname === "localhost",
			hashCache = {};

		function resolveSocketHost(vue, proto) {
			if (vue.$page.docker || !isDev) {
				if (window.location.protocol === "https:") {
					proto += "s";
				}

				return `${proto}://${window.location.host}`;
			}

			return `${proto}://localhost:9999`;
		}

		async function executeRequest(vue, type, route, throwError) {
			if (!vue.$page.auth.socket) {
				if (throwError) {
					throw new Error("Socket unavailable");
				}

				return false;
			}

			route = route.replace(/^\/|\/$/, "");

			const token = await vue.grabToken();

			if (!token) {
				return false;
			}

			const server = vue.$page.serverName,
				host = resolveSocketHost(vue, "http"),
				query = type === "data" ? `?token=${token}` : "";

			const url = `${host}/socket/${server}/${type}/${route}${query}`;

			try {
				const data = await _get(url);

				if (data?.status) {
					return data.data;
				} else {
					if (throwError) {
						throw new Error(data?.error || "Unknown error");
					}

					return false;
				}
			} catch (err) {
				console.log(`Error fetching data from ${url}`);
				console.error(err);

				if (throwError) {
					throw err;
				}

				return false;
			}
		}

		Vue.prototype.resolveHash = async hash => {
			const int = parseInt(hash, 10);

			if (Number.isNaN(int)) return false;

			if (hash in hashCache) {
				return hashCache[hash];
			}

			try {
				const response = await _post("https://joaat.sh/j/reverse", JSON.stringify([int]));

				if (!response || !Array.isArray(response)) return false;

				let result = false;

				const names = response[0];

				if (names && Array.isArray(names) && names.length) {
					result = {
						name: names[0],
						hash: `0x${int.toString(16)}`,
					};
				}

				hashCache[hash] = result;

				return result;
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

		const cache = {
			grabbing: false,
			expires: false,
			token: false,
		};

		Vue.prototype.grabToken = async () => {
			if (cache.token && Date.now() < cache.expires) {
				return cache.token;
			}

			if (!cache.grabbing) {
				cache.grabbing = _get("/api/token")
					.then(data => {
						if (!data?.status) {
							throw new Error("failed to retrieve token");
						}

						cache.token = data.data.token;
						cache.expires = (data.data.expires - 60) * 1000;
					})
					.catch(err => {
						console.error(err);
					})
					.finally(() => {
						cache.grabbing = false;
					});
			}

			await cache.grabbing;

			return cache.token;
		};

		Vue.prototype.createSocket = async function (type, options = {}) {
			const token = await this.grabToken();

			if (!token) {
				return false;
			}

			const socketUrl = resolveSocketHost(this, "ws"),
				server = this.$page.serverName;

			const compressor = new DataCompressor();

			const socket = io(socketUrl, {
				transports: ["websocket"],
				reconnectionDelayMax: 5000,
				query: {
					server: server,
					token: token,
					type: type,
				},
				path: "/io",
			});

			socket.on("reset", data => {
				console.log(`[${type}] Received socket "reset" event (${data.byteLength || data.length} bytes).`);

				compressor.reset();

				data = compressor.decompressData(type, data);

				options?.onData?.(data);
			});

			let received;

			socket.on("message", data => {
				if (!received) {
					received = true;

					console.log(`[${type}] Received first socket "message" event (${data.byteLength || data.length} bytes).`);
				}

				data = compressor.decompressData(type, data);

				options?.onData?.(data);
			});

			socket.on("no_data", () => {
				console.log(`[${type}] Received socket "no_data" event.`);

				options?.onNoData?.();
			});

			socket.on("connect", () => {
				console.log(`[${type}] Received socket "connect" event.`);

				options?.onConnect?.();
			});

			socket.on("rejection", err => {
                console.log(`[${type}] Received socket "rejection" event.`);
                console.warn(err);

                socket.disconnect();
            });

			socket.on("disconnect", () => {
				console.log(`[${type}] Received socket "disconnect" event.`);

				compressor.reset();
				socket.close();

				options?.onDisconnect?.();
			});

			return socket;
		};
	},
};

export default Socket;
