import Vue from 'vue';
import moment from 'moment';

// Formatting seconds to human readable time
export default function (value, includeSeconds) {
    const format = includeSeconds ? 'MMM DD, YYYY h:mm:ss A' : 'lll',
        date = moment.utc(value);

    if (Vue.prototype.setting("relativeTime")) {
        return date.fromNow();
    }

    return date.local().format(format);
};
