// Formatting seconds to string
export default function (value, full = false) {
    if (value <= 0) {
        return "0s";
    } else if (value < 60) {
        return "less than a minute"
    }

    const multipliers = [
        [31557600, "Y"],
        [2629800, "M"],
        [86400, "d"],
        [3600, "h"],
        [60, "m"],
    ];

    const result = [],
        current = full ? multipliers : multipliers.slice(2);

    for (const multiplier of current) {
        const amount = Math.floor(value / multiplier[0]);

        if (amount > 0) {
            result.push(amount + multiplier[1]);
            value -= amount * multiplier[0];
        }
    }

    return result.join(" ");
};
