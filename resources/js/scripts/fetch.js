function query(data) {
	const query = new URLSearchParams();

	for (const key in data) {
		const value = data[key];

		if (value !== null) {
			query.set(key, data[key]);
		}
	}

	return query.toString();
}

function form(data) {
	const body = new FormData();

	for (const key in data) {
		const value = data[key];

		if (value !== null) {
			if (typeof value === "object") {
				body.set(key, JSON.stringify(value));
			} else {
				body.set(key, value);
			}
		}
	}

	return body;
}

function request(method) {
	const isQuery = method === "GET";

	return async (url, data = null, asText = false) => {
		const options = {
			method: method,
			headers: {
				"X-Requested-With": "XMLHttpRequest",
			}
		};

		if (data) {
			if (data._signal) {
				options.signal = data._signal;

				data._signal = null;
			} else if (data._timeout) {
				options.signal = AbortSignal.timeout(data._timeout);

				data._timeout = null;
			}

			if (typeof data === "string") {
				options.headers["Content-Type"] = "application/json";

				options.body = data;
			} else if (isQuery) {
				url += `?${query(data)}`;
			} else {
				options.body = form(data);
			}
		}

		return await fetch(url, options).then(response => {
			if (asText) {
				return response.text();
			}

			return response.json();
		});
	};
}

window._get = request("GET", false, false);
window._post = request("POST", false, false);
window._put = request("PUT", false, false);
window._patch = request("PATCH", false, false);
window._delete = request("DELETE", false, false);
