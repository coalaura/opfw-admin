import Vue from 'vue';

// Formatting seconds to human readable time
export default function (value, includeSeconds) {
    const format = includeSeconds ? 'MMM DD, YYYY h:mm:ss A' : 'MMM D YYYY',
        date = dayjs.utc(value);

    if (Vue.prototype.setting("relativeTime")) {
        return date.fromNow();
    }

    return date.local().format(format);
};
