function parseLine(pLine) {
    pLine = pLine.trim();

    if (pLine === "") {
        return null;
    }

    let isKey = true,
        isQuoted = false,
        isEscaped = false;

    let key = "",
        value = "";

    for (const char of pLine) {
        if (isKey) {
            if (char === "#") {
                return null; // comment
            } else if (char === "=") {
                isKey = false;

                key = key.trim();
                value = "";
            } else if (char !== " " && char !== "\t") {
                key += char;
            }
        } else {
            if (isEscaped) {
                isEscaped = false;

                value += char;
            } else if (char === "\\") {
                isEscaped = true;
            } else if (char === "\"") {
                if (isQuoted) {
                    break; // end of string
                }

                isQuoted = true;
            } else if (isQuoted) {
                value += char;
            } else if (char === "#") {
                break; // comment
            } else {
                value += char;
            }
        }
    }

    if (isKey) {
        return null; // no key found
    }

    value = value.trim();

    // not a string, parse as boolean or number
    if (!isQuoted) {
        if (value === "true") {
            value = true;
        } else if (value === "false") {
            value = false;
        } else {
            value = Number(value);
        }
    }

    return value;
}

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

export default class ServerConfig {
	#value;

	constructor(pLine) {
		const value = parseLine(pLine);

		if (value === null) {
			throw new Error(`Invalid server config line: "${pLine}"`);
		}

		this.#value = value;
	}

	// value1;value2;value3
	array() {
		return parseArray(this.#value, ";");
	}

	// key1=value1;key2=value2;key3=value3
	map() {
		return parseMap(this.#value, ";", "=");
	}

	// sub11:value11,sub12:value12;sub21:value21,sub22:value22
	arrayMap() {
		return parseArray(this.#value, ";", (pRaw) => parseMap(pRaw, ",", ":"));
	}

	// key11=value11,value12;key21=value21,value22
	mapArray() {
		return parseMap(this.#value, ";", "=", (pRaw) => parseArray(pRaw, ","));
	}

	// sub11,sub12;sub21,sub22
	arrayArray() {
		return parseArray(this.#value, ";", (pRaw) => parseArray(pRaw, ","));
	}

	// key11=sub11:value11,sub12:value12;key21=sub21:value21,sub22:value22
	mapMap() {
		return parseMap(this.#value, ";", "=", (pRaw) => parseMap(pRaw, ",", ":"));
	}
};