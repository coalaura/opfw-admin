const Localization = {
    async install(Vue, options) {
        const base = require('../locales/en-us.json');

        let lang = {},
            activeLocale = "en-us";

        function searchObject(object, key) {
            if (!object) {
                return null;
            } else if (!key.includes('.')) {
                return key in object ? object[key] : null;
            }

            let path = key.split('.');
            const current = path.shift();

            return current in object ? searchObject(object[current], path.join('.')) : null;
        }

        Vue.prototype.loadLocale = function (locale) {
            locale = locale.toLowerCase();

            if (locale === "en-us") {
                lang = base;

                return;
            }

            const start = performance.now();

            try {
                lang = require('../locales/' + locale + '.json');
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

            try {
                Vue.prototype.$moment.locale("en-us");
            } catch (e) {
                console.error('Failed to load moment locale "' + locale + '"', e);
            }
        };

        Vue.prototype.t = function (key, ...params) {
            let val = lang ? searchObject(lang, key) : null;

            if (!val) {
                console.error(`${key} not found in locale ${activeLocale}!`);

                val = searchObject(base, key);

                if (!val) {
                    console.error(`${key} not found in fallback locale en-us!`);

                    return 'MISSING_LOCALE';
                }
            }

            if (Array.isArray(params) && typeof val === 'string') {
                for (let x = 0; x < params.length; x++) {
                    val = val.replaceAll('{' + x + '}', params[x]);
                }
            }

            return val;
        };

        Vue.prototype.numberFormat = function (number, decimals, asCurrency) {
            let options = {
                minimumFractionDigits: 0,
                maximumFractionDigits: decimals && Number.isInteger(decimals) ? decimals : 2
            };
            if (asCurrency) {
                options['style'] = 'currency';
                options['currency'] = 'USD';
            }

            const formatter = new Intl.NumberFormat("en-US", options);

            return formatter.format(number);
        };

        Vue.prototype.bytesFormat = function(bytes) {
            if (!+bytes) return '0 bytes'

            const k = 1000,
                dm = decimals < 0 ? 0 : decimals,
                sizes = ['bytes', 'kb', 'mb', 'gb', 'tb', 'pb', 'eb', 'zb', 'yb'];

            const i = Math.floor(Math.log(bytes) / Math.log(k));

            return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`
        };
    },
}

export default Localization;
