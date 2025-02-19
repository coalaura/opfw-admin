// Formatting seconds to string
export default function (value) {
    if (value <= 0) {
        return "0s";
    }

    return dayjs.duration(value, 'seconds').format('D[d] H[h] m[m]').replace(/(?<=\s|^)0\w/gm, '');
};
