import Vector3 from "./Vector3.js";
import { shouldIgnoreInvisible, mapNumber } from "./helper.js";
import { Character, Vehicle } from "./Objects.js";
import L from "leaflet";
import Bounds from "./map.config.js";

const IconSizes = {
	circle: 17,
	circle_yellow: 17,
	skull: 17,
	skull_red: 12,
	circle_red: 12,
	circle_green: 13,
};

class Player {
	constructor(rawData, staffMembers) {
		this.update(rawData, staffMembers);
	}

	static fixData(rawData) {
		const flags = Player.getPlayerFlags(rawData);

		if (flags.identityOverride) {
			rawData.license = `${rawData.license}a`;
		}

		return rawData;
	}

	update(rawData, staffMembers) {
		const flags = Player.getPlayerFlags(rawData);

		this.instance = rawData.instance.id;
		this.instanceName = rawData.instance.name;

		this.player = {
			name: rawData.name,
			license: rawData.license,
			source: rawData.source,
			isStaff: !flags.identityOverride && staffMembers.includes(rawData.license),
			isFake: flags.identityOverride,
		};

		this.damage = Player.getDamageFlags(rawData.damage);

		this.character = Character.fromRaw(rawData);
		this.vehicle = Vehicle.fromRaw(rawData);

		this.lastHeading = this.heading;

		this.location = Vector3.fromGameCoords(rawData.coords.x, rawData.coords.y, rawData.coords.z);
		this.bearing = rawData.heading < 0 ? rawData.heading + 360 : rawData.heading;
		this.speed = Math.round(rawData.speed * 2.236936); // Convert to mph

		this.heading = mapNumber(-rawData.coords.w, -180, 180, 0, 360) - 180; // <- leaflet is weird

		const invisible = this.character?.invisible;

		this.invisible = {
			raw: invisible,
			value: invisible && !shouldIgnoreInvisible(staffMembers, rawData, this.character),
		};

		this.onDuty = rawData.duty ? rawData.duty.type : "none";

		this.icon = {
			dead: this.character?.isDead,
			driving: this.character?.isDriving,
			passenger: this.character && !this.character.isDriving && this.vehicle,
			invisible: this.invisible.raw,
		};

		this.attributes = [
			this.icon.invisible ? "invisible" : null,
			this.icon.dead ? "dead" : null,
			this.player.isStaff ? "staff" : null,
			this.icon.driving ? `driving (${this.vehicle.model})` : null,
			this.icon.passenger ? "passenger" : null,
			!this.icon.passenger && !this.icon.driving ? "on foot" : null,
			this.onDuty === "police" ? "on duty (police)" : null,
			this.onDuty === "medical" ? "on duty (medical)" : null,
		].filter(a => !!a);
	}

	static getPlayerFlags(player) {
		const flags = player.flags ? player.flags : 0;

		return {
			modifiedCameraCoords: !!(flags & 8),
			inMiniGame: !!(flags & 4),
			fakeDisconnected: !!(flags & 2),
			identityOverride: !!(flags & 1),
		};
	}

	static getDamageFlags(flags) {
        flags = flags || 0;

		return {
			shot: !!(flags & 1),
			melee: !!(flags & 2),
			throwable: !!(flags & 4),
			damaged: !!(flags & 8),
		};
	}

	static getPlayerID(rawData) {
		return rawData.license;
	}

    static getDamageIcons(damage) {
        return [
            damage.shot ? `<i class="dmg_icon_shot" title="just shot a gun"></i>` : null,
            damage.melee ? `<i class="dmg_icon_melee" title="just used a melee weapon"></i>` : null,
            damage.throwable ? `<i class="dmg_icon_throwable" title="just used a throwable"></i>` : null,
            damage.damaged ? `<i class="dmg_icon_damaged" title="just damaged a player"></i>` : null,
        ].filter(Boolean);
    }

	getTitle(useHtml) {
		const name = this.character ? this.character.name : "N/A";

		if (useHtml) {
			return `${name}<sup>${this.player.source}</sup>`;
		}

		return `${name} (${this.player.source})`;
	}

	getVehicleID() {
		if (this.character && this.vehicle) {
			return this.vehicle.id;
		}

		return null;
	}

	getCharacterID() {
		return this.character ? this.character.id : null;
	}

	getIcon(trackServerId) {
		if (Bounds.calibrating) {
			return new L.Icon({
				iconUrl: "/images/icons/calibrate.png",
				iconSize: [20, 20],
			});
		}

		let className = [];

		this.damage.shot && className.push("dmg_shot");
		this.damage.melee && className.push("dmg_melee");
		this.damage.throwable && className.push("dmg_throwable");
		this.damage.damaged && className.push("dmg_damaged");

        className = className.length ? className.join(" ") : null;

		let icon = new L.Icon({
			iconUrl: "/images/icons/circle.png",
			iconSize: [IconSizes.circle, IconSizes.circle],
			className: className,
		});

		if (this.icon.invisible) {
			icon = new L.Icon({
				iconUrl: "/images/icons/circle_green.png",
				iconSize: [IconSizes.circle_green, IconSizes.circle_green],
				className: className,
			});
		} else if (this.icon.driving) {
			icon = this.vehicle.getIcon(className);
		} else if (this.icon.passenger && this.icon.dead) {
			icon = new L.Icon({
				iconUrl: "/images/icons/skull_red.png",
				iconSize: [IconSizes.skull_red, IconSizes.skull_red],
				className: className,
			});
		} else if (this.icon.passenger) {
			icon = new L.Icon({
				iconUrl: "/images/icons/circle_red.png",
				iconSize: [IconSizes.circle_red, IconSizes.circle_red],
				className: className,
			});
		} else if (this.icon.dead) {
			icon = new L.Icon({
				iconUrl: "/images/icons/skull.png",
				iconSize: [IconSizes.skull, IconSizes.skull],
				className: className,
			});
		} else if (this.player.source === trackServerId) {
			icon = new L.Icon({
				iconUrl: "/images/icons/circle_yellow.png",
				iconSize: [IconSizes.circle_yellow, IconSizes.circle_yellow],
				className: className,
			});
		} else if (this.onDuty === "police") {
			icon = new L.Icon({
				iconUrl: "/images/icons/circle_police.png",
				iconSize: [IconSizes.circle, IconSizes.circle],
				className: className,
			});
		} else if (this.onDuty === "medical") {
			icon = new L.Icon({
				iconUrl: "/images/icons/circle_ems.png",
				iconSize: [IconSizes.circle, IconSizes.circle],
				className: className,
			});
		}

		return icon;
	}

	getZIndex(trackServerId) {
		if (this.player.source === trackServerId) {
			return 200;
		}

		if (this.icon.passenger) {
			return 102;
		}

		if (!this.icon.driving) {
			return 101;
		}

		return 100;
	}

	static newMarker() {
		const marker = L.marker({ lat: 0, lng: 0 }, {});

		marker.bindPopup("", {
			autoPan: false,
		});

		return marker;
	}

	updateMarker(marker, trackServerId, vehicles) {
		marker.setIcon(this.getIcon(trackServerId));
		marker.setLatLng(this.location.toMap());

		// Reset transition for icon
		if (marker._icon) {
			marker._icon.style.transition = "inherit";
		}

		// Check if we have a last heading otherwise just set the rotation
		if ((this.lastHeading || this.lastHeading === 0) && marker._icon) {
			// Calculate the difference between the last and the new heading
			const headingDiff = this.lastHeading - this.heading;

			// Are we doing a 360?
			if (Math.abs(headingDiff) >= 180) {
				// Calculate how the heading should be relative to the old one and set it
				const newHeading = headingDiff > 0 ? this.heading + 360 : this.heading - 360;
				marker.setRotationAngle(newHeading);

				// Wait for the animation to finish (300ms)
				setTimeout(() => {
					if (!marker._icon) {
						return;
					}

					// Set the transition to 0s so we dont see a 360
					marker._icon.style.transition = "0s";

					// Update the icons rotation with the actual heading while we still have no transition
					marker._icon.style.transform = marker._icon.style.transform.replace(/(?<=rotateZ\().+?(?=\))/gm, `${this.heading}deg`);
				}, 300);
			} else {
				// We are not doing a 360 so no fancy stuff needed
				marker.setRotationAngle(this.heading);
			}
		} else {
			marker.setRotationAngle(this.heading);
		}

		const attributes = this.attributes.map(a => `<span class="text-xxs italic block leading-3">- is ${a}</span>`);

		let vehicleInfo = "";

		const vehicle = this.getVehicleID();

		if (vehicle && vehicle in vehicles) {
			const formatInfo = info => `<a href="/players/${info.license}" target="_blank">${info.name}</a>`;

			vehicleInfo = `<span class="block mt-1 text-xxs leading-3 border-t border-gray-700 pt-1"><b>Driver:</b> ${vehicles[vehicle].driver ? formatInfo(vehicles[vehicle].driver) : "N/A"}</span><span class="block text-xxs leading-3"><b>Passengers:</b> ${
				vehicles[vehicle].passengers.length > 0 ? vehicles[vehicle].passengers.map(i => formatInfo(i)).join(", ") : "N/A"
			}</span>`;
		}

        const damageIcons = Player.getDamageIcons(this.damage);

		const popup = [
			`<a href="/players/${this.player.license}" target="_blank" class="font-bold block border-b border-gray-700 mb-1">${this.getTitle(true)}</a>`,
			`<span class="block"><b>Altitude:</b> ${this.location.z}m</span>`,
			`<span class="block mb-1 border-b border-gray-700 pb-1"><b>Speed:</b> ${this.speed}mph</span>`,
			attributes.join(""),
			vehicleInfo,
            damageIcons.length ? `<span class="flex gap-1 mt-1 border-t border-gray-700 pt-2">${damageIcons.join("")}</span>` : ""
		].join("");

		marker._popup.setContent(popup);

		marker.options.forceZIndex = this.getZIndex(this.trackServerId);

		return marker;
	}
}

export default Player;
