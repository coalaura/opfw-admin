import conf from './map.config.js';
import {mapNumber} from './helper.js';

class Vector3 {
    static fromGameCoords(x, y, z) {
        const v = new Vector3();

        v.x = x;
        v.y = y;
        v.z = z ? Math.round(z) : 0;

        v.mapCoords = null;

        return v;
    }

    static fromMapCoords(lng, lat, alt) {
        const v = new Vector3();

        v.x = mapNumber(lng, conf.map.bounds.min.x, conf.map.bounds.max.x, conf.game.bounds.min.x, conf.game.bounds.max.x);
        v.y = mapNumber(lat, conf.map.bounds.min.y, conf.map.bounds.max.y, conf.game.bounds.min.y, conf.game.bounds.max.y);
        v.z = Math.round(alt);

        return v;
    }

    toMap() {
        if (!this.mapCoords) {
            const x = mapNumber(this.x, conf.game.bounds.min.x, conf.game.bounds.max.x, conf.map.bounds.min.x, conf.map.bounds.max.x);
            const y = mapNumber(this.y, conf.game.bounds.min.y, conf.game.bounds.max.y, conf.map.bounds.min.y, conf.map.bounds.max.y);

            this.mapCoords = {
                lat: y,
                lng: x
            };
        }

        return this.mapCoords;
    }

    toGame() {
        return {
            x: this.x,
            y: this.y,
            z: this.z
        };
    }

    toStringGame() {
        return `${Math.round(this.x)} ${Math.round(this.y)} ${Math.round(this.z)}`;
    }
}

export default Vector3;
