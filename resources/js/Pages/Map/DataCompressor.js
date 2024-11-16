import { unpack } from "msgpackr";

class DataCompressor {
    #data = {};

    decompressData(type, data) {
        data = this.#update(unpack(data));

        let isValid, result;

        switch (type) {
            case "world":
                isValid = 'v' in data && Array.isArray(data.v) && 'p' in data && typeof data.p === "object" && 'i' in data && typeof data.i === "number";

                if (isValid) {
                    result = {
                        players: this.decompressPlayers(data.p),
                        viewers: data.v,
                        instance: data.i
                    };
                }
            case "staff":
                isValid = data && Array.isArray(data);

                if (isValid) {
                    result = data;
                }
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
            for (const key in nw) {
                const oldValue = dt[key],
                    newValue = nw[key];

                const oldType = typeof oldValue,
                    newType = typeof newValue;

                if (newValue === null) {
                    delete dt[key];
                } else if (newType === "object") {
                    if (Array.isArray(newValue)) {
                        dt[key] = newValue;
                    } else {
                        dt[key] = update(dt[key] || {}, newValue);
                    }
                } else if (oldType !== newType) {
                    dt[key] = newValue;
                } else {
                    dt[key] = newValue;
                }
            }

            return dt;
        };

        this.#data = update(this.#data, data);

        return this.#copy(this.#data);
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

    reset(data) {
        let unpacked = {};

        if (data) {
            unpacked = unpack(data);
        }

        this.#data = unpacked;
    }

    decompressPlayers(players) {
        for (const source in players) {
            players[source] = this.decompress(players[source]);
        }

        return players;
    }

    decompress(player) {
        this.player = player;

        const character = 'b' in this.player ? {
            flags: this.get('a', 0, this.player['b']),
            fullName: this.get('b', '', this.player['b']),
            id: this.get('c', 0, this.player['b'])
        } : false;

        const vehicle = 'i' in this.player ? {
            driving: this.get('a', false, this.player['i']),
            id: this.get('b', 0, this.player['i']),
            model: this.get('c', '', this.player['i']),
            name: this.get('d', '', this.player['i']),
        } : false;

        const duty = 'e' in this.player ? {
            type: this.get('a', false, this.player['e']),
            department: this.get('b', false, this.player['e'])
        } : false;

        const coordsArray = 'c' in this.player ? this.player['c'].split(',') : [],
            coords = coordsArray.length >= 3 ? {
                x: parseFloat(coordsArray[0]),
                y: parseFloat(coordsArray[1]),
                z: parseFloat(coordsArray[2])
            } : { x: 0, y: 0, z: 0 };

        return {
            character: character,
            coords: coords,
            heading: coordsArray.length >= 4 ? parseFloat(coordsArray[3]) : 0.0,
            flags: this.get('d', 0),
            duty: duty,
            name: this.get('f', ''),
            source: this.get('g', 0),
            speed: coordsArray.length >= 5 ? parseFloat(coordsArray[4]) : 0.0,
            licenseIdentifier: this.get('h', ''),
            vehicle: vehicle,
            instance: this.get('j', 0)
        };
    }

    get(key, def, obj) {
        if (!obj) {
            obj = this.player;
        }

        if (obj && key in obj) {
            return obj[key];
        }
        return def;
    }
}

export default DataCompressor;
