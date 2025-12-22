import "es-arraybuffer-base64/auto";

function writeInt24LE(buf, value) {
	value = Math.max(Math.min(value, 0x7fffff), -0x800000);

	buf.push(value & 0xff, (value >> 8) & 0xff, (value >> 16) & 0xff);
}

function writeString(buf, value) {
	if (!value) {
		buf.push(0);

		return;
	}

	const encoded = new TextEncoder().encode(value);

	buf.push(encoded.length, ...encoded);
}

const MapEncoder = {
	install: async (Vue, options) => {
		Vue.prototype.buildMapUrl = (connect, points) => {
			const buffer = [connect ? 1 : 0];

			for (const point of points) {
				writeInt24LE(buffer, Math.round(point.x * 100));
				writeInt24LE(buffer, Math.round(point.y * 100));
				writeString(buffer, point.label || "");
			}

			const base64 = Uint8Array.from(buffer)
				.toBase64({
					alphabet: "base64url",
				})
				.replace(/=/g, "");

			return `https://map.opfw.net/#${base64}`;
		};
	},
};

export default MapEncoder;
