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
			body.set(key, data[key]);
		}
	}

	return body;
}

function request(method) {
	const isQuery = method === "GET";

	return async (url, data = null, text = false) => {
		const options = {
			method: method,
		};

		if (data._signal) {
			options.signal = data._signal;

			data._signal = null;
		} else if (data._timeout) {
			options.signal = AbortSignal.timeout(data._timeout);

			data._timeout = null;
		}

		if (data) {
			if (isQuery) {
				url += `?${query(data)}`;
			} else {
				options.body = form(data);
			}
		}

		return await fetch(url, options).then(response => {
			if (text) {
				return response.text();
			}

			return response.json();
		});
	};
}

window._get = request("GET");
window._post = request("POST");
window._put = request("PUT");
window._patch = request("PATCH");
window._delete = request("DELETE");
