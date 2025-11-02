import Vue from "vue";

// day.js
import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import timezone from "dayjs/plugin/timezone";
import duration from "dayjs/plugin/duration";
import relativeTime from "dayjs/plugin/relativeTime";
import advancedFormat from "dayjs/plugin/advancedFormat";
import isoWeek from "dayjs/plugin/isoWeek";

dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.extend(duration);
dayjs.extend(relativeTime);
dayjs.extend(advancedFormat);
dayjs.extend(isoWeek);

window.dayjs = dayjs;

Vue.prototype.dayjs = dayjs;

// jquery i guess :(
import $ from "jquery-slim";

window.$ = $;

// Mini HTTP API
import "./scripts/fetch.js";

// AI text detector
Vue.prototype.isAIGenerated = text => {
	const aiThings = [
		/[—…’“”]/, // AI tends to love these
		/, (or|and)[^\n,.]+,/, // [...]Whether it was leading high-stakes heists, smuggling operations, or turf wars, Chadwick[...]

		// Less common, still useful
		/By the time (he|she) was \d+/i, // [...]By the time he was 18, Vladislove had[...]
		/(?:Growing up|Raised) (?:in|on) .*, (he|she) (?:learned|saw|witnessed)/i, // [...]raised in the shadow of a broken city, one that was never truly safe for anyone[...]
	];

	return !!aiThings.find(thing => thing.test(text));
};
