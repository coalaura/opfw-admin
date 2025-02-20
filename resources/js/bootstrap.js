import Vue from "vue";

// day.js
import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import timezone from "dayjs/plugin/timezone";
import duration from "dayjs/plugin/duration";
import relativeTime from "dayjs/plugin/relativeTime";

dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.extend(duration);
dayjs.extend(relativeTime);

window.dayjs = dayjs;

Vue.prototype.dayjs = dayjs;

// jquery i guess :(
import $ from "jquery-slim";

window.$ = $;

// Some fetch helpers
window.get_data = data => {
	const query = new URLSearchParams();

	for (const key in data) {
		const value = data[key];

		if (value !== null) {
			query.set(key, data[key]);
		}
	}

	return query.toString();
};

window.post_data = data => {
	const body = new FormData();

	for (const key in data) {
		const value = data[key];

		if (value !== null) {
			body.set(key, data[key]);
		}
	}

	return body;
};
