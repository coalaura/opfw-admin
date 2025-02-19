// Formatting seconds to human readable time
export default function (value) {
    return dayjs.duration(parseInt(value), 'seconds').humanize();
};
