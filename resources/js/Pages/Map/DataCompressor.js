import { unpack } from "msgpackr";

class DataCompressor {
    #data = {};

    decompressData(type, compressed) {
        const data = this.#update(unpack(compressed));

        let isValid, result;

        switch (type) {
            case "world":
                isValid = 'viewers' in data && Array.isArray(data.viewers) && 'players' in data && typeof data.players === "object" && 'instance' in data && typeof data.instance === "number";

                if (isValid) {
                    result = data;
                }

                break;
            case "staff":
                isValid = data && Array.isArray(data);

                if (isValid) {
                    result = data;
                }

                break;
        }

        if (!isValid || !result) {
            console.debug("Failed decompress", data);

            return data;
        }

        return result;
    }

    #update(data) {
        // dt = data, nw = new
        const update = (dt, nw) => {
            if (Array.isArray(nw)) {
                dt = nw;

                return dt;
            } else if (typeof nw === "object") {
                if (!dt || typeof dt !== "object") {
                    dt = {};
                }

                for (const key in nw) {
                    const oldValue = dt[key],
                        newValue = nw[key];

                    const oldType = typeof oldValue,
                        newType = typeof newValue;

                    if (newValue === null) {
                        delete dt[key];
                    } else if (newType === "object") {
                        dt[key] = update(dt[key] || {}, newValue);
                    } else if (oldType !== newType) {
                        dt[key] = newValue;
                    } else {
                        dt[key] = newValue;
                    }
                }

                return dt;
            }

            return nw;
        };

        this.#data = update(this.#data, data);

        return this.data();
    }

    #copy(object) {
        const copy = obj => {
            if (Array.isArray(obj)) {
                return obj.map(copy);
            } else if (typeof obj === "object") {
                return Object.entries(obj).reduce((obj, [key, value]) => {
                    obj[key] = copy(value);

                    return obj;
                }, {});
            } else {
                return obj;
            }
        };

        return copy(object);
    }

    data() {
        return this.#copy(this.#data);
    }

    reset() {
        this.#data = {};
    }
}

export default DataCompressor;
