const Identifiers = {
    async install(Vue, options) {
        const types = [
            "discord",
            "fivem",
            "license2",
            "steam",
            "xbl",
            "live"
        ];

        Vue.prototype.getIdentifierTypes = () => {
            return types;
        };

        Vue.prototype.isIdentifierOfType = (identifier, type) => {
            if (!identifier || !types.includes(type)) return false;

            if (identifier.includes(":")) {
                const parts = identifier.split(":");

                if (parts.length !== 2) return false;

                return parts[0] === type;
            }

            // Easy to match ones
            if (type === "steam") {
                return identifier.match(/^[a-f0-9]{15}$/mi);
            } else if (type === "license2") {
                return identifier.match(/^[a-f0-9]{40}$/mi);
            } else if (type === "fivem") {
                return identifier.match(/^\d{4,8}$/mi);
            } else if (type === "xbl") {
                return identifier.match(/^\d{16}$/mi);
            } else if (type === "live") {
                return identifier.match(/^\d{15,16}$/mi);
            }

            if (type === "discord") {
                if (!identifier.match(/^\d{17,19}$/mi)) return false;

                const min = 1420070400000; // 2015-01-01 00:00:00
                const max = Date.now();

                const timestamp = Number(BigInt(parseInt(identifier)) >> 22n) + 1420070400000;

                return timestamp && timestamp >= min && timestamp <= max;
            }

            return false;
        };

        Vue.prototype.detectIdentifierType = function (identifier) {
            if (!identifier) return false;

            for (const type of types) {
                if (this.isIdentifierOfType(identifier, type)) {
                    return type;
                }
            }

            return false;
        };
    }
}

export default Identifiers;
