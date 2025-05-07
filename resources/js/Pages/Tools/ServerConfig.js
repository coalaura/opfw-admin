function parseArray(pRaw, pSeparator, pCast = false) {
	if (!pRaw) {
		return [];
	}

	const result = [],
		entries = pRaw.split(pSeparator);

	for (const entry of entries) {
		const value = entry.trim();

		result.push(pCast ? pCast(value) : value);
	}

	return result;
}

function parseMap(pRaw, pSeparator, pAssigner, pCast = false) {
	if (!pRaw) {
		return {};
	}

	const result = {};

	let isKey = true,
		key = "",
		value = "";

	for (const char of pRaw) {
		if (isKey) {
			if (char === pAssigner) {
				isKey = false;

				key = key.trim();

				value = "";
			} else {
				key += char;
			}
		} else {
			if (char === pSeparator) {
				isKey = true;

				value = value.trim();

				result[key] = pCast ? pCast(value) : value;

				key = "";
			} else {
				value += char;
			}
		}
	}

	if (!isKey && value !== "") {
		value = value.trim();

		result[key] = pCast ? pCast(value) : value;
	}

	return result;
}

function asString(value) {
    return String(value);
}

export default class ServerConfig {
	#value;

	constructor(pLine = "") {
		this.#value = String(pLine);
	}

	/**
	 * A simple array. The result is an array.
	 *
	 * value1;value2;value3
	 */
	array() {
		return parseArray(this.#value, ";");
	}

    asArray(data, join = ";", formatter = asString) {
        return data.map(value => formatter(value)).join(join);
    }

	/**
	 * A key=value map. The result is a map.
	 *
	 * key1=value1;key2=value2;key3=value3
	 */
	map() {
		return parseMap(this.#value, ";", "=");
	}

    asMap(data, assign = "=", join = ";", formatter = asString) {
        return Object.entries(data).map(([key, value]) => `${key}${assign}${formatter(value)}`).join(join);
    }

	/**
	 * An array of maps. The result is an array. Each entry of that array is a map.
	 *
	 * sub11:value11,sub12:value12,sub13:value13; <- index 1
	 * sub21:value21,sub22:value22,sub23:value32 <- index 2
	 */
	arrayMap() {
		return parseArray(this.#value, ";", pRaw => parseMap(pRaw, ",", ":"));
	}

    asArrayMap(data) {
        return this.asArray(data, ";", sub => this.asMap(sub, ":", ","));
    }

	/**
	 * A map of arrays. The result is a map. Each value of that map is an array.
	 *
	 * key1=value11,value12,value13;
	 * key2=value21,value22,value23
	 */
	mapArray() {
		return parseMap(this.#value, ";", "=", pRaw => parseArray(pRaw, ","));
	}

    asMapArray(data) {
        return this.asMap(data, "=", ";", sub => this.asArray(sub, ","));
    }

	/**
	 * An array of arrays. The result is an array. Each entry in the array is an array.
	 *
	 * value11,value12,value13; <- index 1
	 * value21,value22,value23 <- index 2
	 */
	arrayArray() {
		return parseArray(this.#value, ";", pRaw => parseArray(pRaw, ","));
	}

    asArrayArray(data) {
        return this.asArray(data, ";", sub => this.asArray(sub, ","));
    }

	/**
	 * A map of maps. The result is a map. Each value of the result is its own map.
	 *
	 * key1=
	 *     sub11:value11,sub12:value12,sub13:value13;
	 * key2=
	 *     sub21:value21,sub22:value22,sub23:value23
	 */
	mapMap() {
		return parseMap(this.#value, ";", "=", pRaw => parseMap(pRaw, ",", ":"));
	}

    asMapMap(data) {
        return this.asMap(data, "=", ";", sub => this.asMap(sub, ":", ","));
    }

	/**
	 * A map of arrays of maps. The result is a map. Each value of the result is one array. Each entry in that array is a map.
     *
     * key1=
     *     sub111:value111|sub112:value112|sub113:value113, <- index 1
     *     sub121:value121|sub122:value122|sub123:value123; <- index 2
     * key2=
     *     sub211:value211|sub212:value212, <- index 1
     *     sub221:value221 <- index 2
	 */
	mapArrayMap() {
		return parseMap(this.#value, ";", "=", pRaw => parseArray(pRaw, ",", pRaw => parseMap(pRaw, "|", ":")));
	}

    asMapArrayMap(data) {
        return this.asMap(data, "=", ";", sub1 => this.asArray(sub1, ",", sub2 => this.asMap(sub2, ":", "|")));
    }
}
