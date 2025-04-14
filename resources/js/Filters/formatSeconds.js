// Formatting seconds to string
export default function (value, allowed = "dhm", named = false) {
    if (value <= 0) {
        return "0s";
    } else if (value < 60) {
        return `${value}s`;
    }

    const multipliers = [
        [31557600, "Y", "year"],
        [2629800, "M", "month"],
        [86400, "d", "day"],
        [3600, "h", "hour"],
        [60, "m", "minute"],
    ];

    if (allowed && !allowed.includes("m") && value < 60*60) {
        allowed += "m";
    }

    const result = [];

    for (const multiplier of multipliers) {
        if (allowed && !allowed.includes(multiplier[1])) {
            continue;
        }

        const amount = Math.floor(value / multiplier[0]);

        if (amount > 0) {
            let label = named ? multiplier[2] : multiplier[1];

            if (named) {
                label = ` ${label}`;

                if (amount > 1) {
                    label += "s";
                }
            }

            result.push(amount + label);

            value -= amount * multiplier[0];
        }
    }

    return result.join(" ");
};
