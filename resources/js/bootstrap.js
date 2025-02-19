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

// jquery i guess :(
import $ from "jquery-slim";

window.$ = $;

// Some fetch helpers
window.post_data = data => {
	console.log("post_data", data);
	const body = new FormData();

	for (const key in data) {
		body.set(key, data[key]);
	}

	return body;
};
