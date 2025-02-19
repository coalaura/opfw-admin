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

// popper.js
import Popper from "popper.js";

window.Popper = Popper;

// aos.js
import aos from "aos/dist/aos";

window.AOS = aos;

window.AOS.init({
	offset: 120,
	delay: 0,
	duration: 1000,
	easing: "ease",
	once: false,
	mirror: false,
});

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
