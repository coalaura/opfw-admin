import Popper from "popper.js";
import aos from "aos/dist/aos";
import $ from "jquery-slim";

try {
	window.Popper = Popper;
} catch (e) {}

/**
 * We'll load the AOS library which allows us to easily animate elements.
 */

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
