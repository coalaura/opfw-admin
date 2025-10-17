import custom_icons from "../../data/vehicles.json";
import blip_map from "../../data/blip-map.json";
import L from "leaflet";

export class Character {
	static fromRaw(rawData) {
		if (!rawData.character) {
			return null;
		}

		const characterFlags = Character.getCharacterFlags(rawData.character);

		const c = new Character();

		c.id = rawData.character.id;
		c.name = rawData.character.name;
		c.isDead = characterFlags.dead;
		c.invisible = characterFlags.invisible;
		c.invincible = characterFlags.invincible;
		c.frozen = characterFlags.frozen;
		c.inShell = characterFlags.shell;
		c.inTrunk = characterFlags.trunk;
		c.isDriving = rawData.vehicle?.driving;

		return c;
	}

	static getCharacterFlags(character) {
		const flags = character?.flags ? character.flags : 0;

		return {
			spawned: !!(flags & 64),
			frozen: !!(flags & 32),
			invincible: !!(flags & 16),
			invisible: !!(flags & 8),
			shell: !!(flags & 4),
			trunk: !!(flags & 2),
			dead: !!(flags & 1),
		};
	}
}

export class Vehicle {
	static fromRaw(rawData) {
		if (!rawData.vehicle) {
			return null;
		}

		const v = new Vehicle();

		v.id = rawData.vehicle.id;
		v.name = rawData.vehicle.name;
		v.model = `${rawData.vehicle.model}`;

		let type = "car";
		let size = 23;

		for (const typ in custom_icons) {
			const cfg = custom_icons[typ];

			if (cfg.models.includes(v.model)) {
				type = typ;
				size = cfg.size;
			}
		}

		v.icon = {
			type: type,
			size: size,
		};

		return v;
	}

	getIcon(className = null) {
		if (this.model in blip_map) {
			return new L.Icon({
				iconUrl: `/images/icons/gta/Blip_${blip_map[this.model]}.png`,
				iconSize: [28, 28],
				className: className,
			});
		}

		return new L.Icon({
			iconUrl: `/images/icons/${this.icon.type}.png`,
			iconSize: [this.icon.size, this.icon.size],
			className: className,
		});
	}
}
