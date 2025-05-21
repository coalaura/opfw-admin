import base from "../locales/en-us.json";

import cave from "../locales/en-cave.json";
import southern from "../locales/en-us_s.json";
import uwu from "../locales/en-uwu.json";
import hindi from "../locales/hi-lat.json";
import norwegian from "../locales/no.json";

const locales = {
	"en-cave": cave,
	"en-us_s": southern,
	"en-uwu": uwu,
	"hi-lat": hindi,
	no: norwegian,
};

const Localization = {
	async install(Vue, options) {
		let lang = {},
			activeLocale = "en-us";

		function searchObject(object, key) {
			if (!object) {
				return null;
			} else if (!key.includes(".")) {
				return key in object ? object[key] : null;
			}

			const path = key.split(".");
			const current = path.shift();

			return current in object ? searchObject(object[current], path.join(".")) : null;
		}

		Vue.prototype.loadLocale = async locale => {
			locale = locale.toLowerCase();

			if (locale === "en-us") {
				lang = base;

				return;
			}

			const start = performance.now();

			try {
				lang = locales[locale];

				activeLocale = locale;

				console.info(`Loaded locale ${locale} in ${performance.now() - start}ms`);
			} catch (e) {
				console.error(`Failed to load locale ${locale} after ${performance.now() - start}ms`);

				try {
					lang = base;
				} catch (e) {
					console.error('Failed to load fallback locale "en-us"');
				}
			}
		};

		Vue.prototype.t = (key, ...params) => {
			let val = lang ? searchObject(lang, key) : null;

			if (!val) {
				console.error(`${key} not found in locale ${activeLocale}!`);

				val = searchObject(base, key);

				if (!val) {
					console.error(`${key} not found in fallback locale en-us!`);

					return "MISSING_LOCALE";
				}
			}

			if (Array.isArray(params) && typeof val === "string") {
				for (let x = 0; x < params.length; x++) {
					val = val.replaceAll(`{${x}}`, params[x]);
				}
			}

			return val;
		};

		Vue.prototype.numberFormat = (number, decimals, asCurrency, minDecimals) => {
			if (number === null || number === undefined || number === false) {
				return "-";
			}

			const options = {
				minimumFractionDigits: minDecimals && Number.isInteger(minDecimals) ? minDecimals : 0,
				maximumFractionDigits: decimals && Number.isInteger(decimals) ? decimals : 2,
			};

			if (asCurrency) {
				options["style"] = "currency";
				options["currency"] = "USD";
			}

			return new Intl.NumberFormat("en-US", options).format(number);
		};

		Vue.prototype.bytesFormat = (bytes, decimals = 2) => {
			if (!+bytes) return "0 bytes";

			const k = 1000,
				dm = decimals < 0 ? 0 : decimals,
				sizes = ["bytes", "kb", "mb", "gb", "tb", "pb", "eb", "zb", "yb"];

			const i = Math.floor(Math.log(bytes) / Math.log(k));

			return `${parseFloat((bytes / k ** i).toFixed(dm))} ${sizes[i]}`;
		};

		Vue.prototype.truncate = (text, length) => {
			if (text.length <= length) {
				return text;
			}

			return `${text.substr(0, length - 3)}...`;
		};
	},
};

export default Localization;
