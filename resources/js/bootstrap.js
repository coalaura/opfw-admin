import Vue from "vue";

// day.js
import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import timezone from "dayjs/plugin/timezone";
import duration from "dayjs/plugin/duration";
import relativeTime from "dayjs/plugin/relativeTime";
import advancedFormat from "dayjs/plugin/advancedFormat";

dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.extend(duration);
dayjs.extend(relativeTime);
dayjs.extend(advancedFormat);

window.dayjs = dayjs;

Vue.prototype.dayjs = dayjs;

// jquery i guess :(
import $ from "jquery-slim";

window.$ = $;

// Mini HTTP API
import "./scripts/fetch.js";