const ChunkBorder = "\r\n\r\n";

function request(method, url, data = null, callback) {
	let lastChunkBorder = 0,
		receivedEnd = false;

	function parseChunk(text) {
		if (receivedEnd) return;

		let nextBorder;

		do {
			nextBorder = text.indexOf(ChunkBorder, lastChunkBorder);

			if (nextBorder === -1) return;

			const chunk = text.substring(lastChunkBorder, nextBorder);

			// Update the lastChunkBorder to the end of the current chunk.
			lastChunkBorder = nextBorder + ChunkBorder.length;

			// Final chunk, pog!
			if (chunk === "0") {
				receivedEnd = true;

				return;
			}

			callback(JSON.parse(chunk));
		} while (nextBorder !== -1);
	}

	function parseResponse(text) {
		text = text.substring(lastChunkBorder);

		try {
			return JSON.parse(text);
		} catch (e) {
			return text;
		}
	}

	return new Promise((resolve, reject) => {
		const xhr = new XMLHttpRequest();

		xhr.open(method, url, true);

		xhr.onprogress = () => {
			parseChunk(xhr.responseText);
		};

		xhr.onload = () => {
			if (xhr.readyState !== 4) return;

			if (xhr.status === 200) {
				resolve(parseResponse(xhr.responseText));
			} else {
				reject(xhr.statusText);
			}
		};

		xhr.onerror = () => reject(xhr.statusText);

		if (data) {
			xhr.setRequestHeader('Content-Type', 'application/json');

			data = JSON.stringify(data);
		}

		xhr.send(data);
	});
}

const Chunked = {
	async install(Vue, options) {
		Vue.prototype.chunked = {
			get: (url, callback) => request('GET', url, null, callback),
			post: (url, data, callback) => request('POST', url, data, callback),
			put: (url, data, callback) => request('PUT', url, data, callback),
			delete: (url, callback) => request('DELETE', url, null, callback),
		};
	},
}

export default Chunked;
