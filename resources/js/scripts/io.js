import { pack, unpack } from "msgpackr";

class IO {
	#ws;
	#type;
	#url;
	#listeners = {};

	#unloading;
	#beforeunload;
	#timeout;
	#connected;

	constructor(type, url, options) {
		this.#type = type;

		this.#parse(url, options);

		this.#beforeunload = () => {
			this.#unloading = true;

			this.close();
		};
	}

	#parse(url, options) {
		this.#url = new URL(options?.path || "/io", url);

		if (options?.query) {
			this.#url.search = new URLSearchParams(options.query).toString();
		}
	}

	connect() {
		if (this.#ws || this.#unloading) {
			return;
		}

		this.#timeout = setTimeout(() => {
			console.error(`[${this.#type}] Connection timed out after 5s.`);

			this.close();
		}, 5000);

		this.#ws = new WebSocket(this.#url.toString());

		this.#ws.binaryType = "arraybuffer";

		this.#ws.onopen = () => {
			clearTimeout(this.#timeout);

			window.addEventListener("beforeunload", this.#beforeunload);

			this.#connected = Math.floor(Date.now() / 100);

			this.#trigger("connect");
		};

		this.#ws.onmessage = event => {
			try {
				const message = unpack(new Uint8Array(event.data)),
					type = message?.t,
					data = message?.d;

				if (!type || typeof data === "undefined") {
					console.error(`[${this.#type}]Invalid message received`);

					return;
				}

				this.#trigger(type, data, message.byteLength);
			} catch (err) {
				console.error(`[${this.#type}]Failed to decode message: ${err}`);
			}
		};

		this.#ws.onclose = () => {
			clearTimeout(this.#timeout);

			this.#ws = null;

			this.#trigger("disconnect");

			this.#connected = false;
		};

		this.#ws.onerror = err => {
			console.error(`[${this.#type}] WebSocket error`, err);
		};
	}

	close() {
		if (!this.#ws) {
			return;
		}

		this.#ws.close();

		window.removeEventListener("beforeunload", this.#beforeunload);
	}

	disconnect() {
		this.close();
	}

	#trigger(type, data = null, length = 0) {
		if (type === "ping") {
			this.emit("pong");

			return;
		}

		if (type !== "message") {
			console.log(`[${this.#type}]${this.#connected ? ` ${(Math.floor(Date.now() / 100) - this.#connected) / 10}s -` : ""} Received "${type}" event${length > 0 ? `(${length} bytes)` : ""}`);
		}

		const listeners = this.#listeners[type];

		if (!listeners?.length) {
			return;
		}

		for (const cb of listeners) {
			cb(data);
		}
	}

	on(type, cb) {
		if (type in this.#listeners) {
			this.#listeners[type].push(cb);
		} else {
			this.#listeners[type] = [cb];
		}
	}

	emit(type, data) {
		if (!this.#ws) {
			return;
		}

		this.#ws.send(
			pack({
				t: type,
				d: data,
			})
		);
	}
}

export default function io(type, url, options) {
	const connection = new IO(type, url, options);

	const connect = () => setTimeout(() => connection.connect(), 0);

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", connect);
	} else {
		connect();
	}

	return connection;
}
