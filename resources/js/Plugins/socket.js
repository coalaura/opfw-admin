import DataCompressor from "../Pages/Map/DataCompressor.js";

import { io } from "socket.io-client";

const Socket = {
	async install(Vue, options) {
		const isDev = window.location.hostname === "localhost";

		async function executeRequest(vue, type, route, throwError) {
			if (!vue.$page.auth.socket) {
				if (throwError) {
					throw new Error("Socket unavailable");
				}

				return false;
			}

			route = route.replace(/^\/|\/$/, "");

			const token = vue.$page.auth.token,
				server = vue.$page.serverName,
				host = isDev ? "http://localhost:9999" : `https://${window.location.host}`,
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
				console.log(`Error fetching data from ${url}: ${err.message}`);

				if (throwError) {
					throw err;
				}

				return false;
			}
		}

		Vue.prototype.resolveHash = async hash => {
			const int = parseInt(hash, 10);

			if (Number.isNaN(int)) return false;

			try {
				const response = await fetch("https://joaat.sh/j/reverse/", {
					method: "POST",
					headers: {
						"Content-Type": "application/json",
					},
					body: JSON.stringify([int]),
				}).then(response => response.json());

				if (!response || !Array.isArray(response)) return false;

				const names = response[0];

				if (!names || !Array.isArray(names)) return false;

				return {
					name: names[0],
					hash: `0x${int.toString(16)}`,
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

		Vue.prototype.createSocket = function (type, options = {}) {
			const socketUrl = isDev ? 'ws://localhost:9999' : `wss://${window.location.host}`,
                token = this.$page.auth.token,
				server = this.$page.serverName;

            const compressor = new DataCompressor();

			const socket = io(socketUrl, {
				reconnectionDelayMax: 5000,
				query: {
					server: server,
					token: token,
					type: type,
					license: this.$page.auth.player.licenseIdentifier,
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
