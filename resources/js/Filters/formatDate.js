export default function (value) {
    return dayjs.utc(value).local().format("MMM DD YYYY");
};
