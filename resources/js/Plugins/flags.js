import ct from 'countries-and-timezones';

const Flags = {
    install(Vue, options) {
        Vue.prototype.flagFromTZ = tz => {
            const countries = ct.getCountriesForTimezone(tz);

            if (countries.length > 0) {
                return countries[0].id.toLowerCase();
            }

            return 'un';
        }
    },
}

export default Flags;
