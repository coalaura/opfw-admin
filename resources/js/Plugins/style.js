import ColorThief from 'colorthief';

// Rebuild style on version change
const Iteration = 7;

const colors = {
	'gray-100': { l: 96 },
	'gray-200': { l: 91 },
	'gray-300': { l: 84 },
	'gray-400': { l: 65 },
	'gray-500': { l: 46 },
	'gray-600': { l: 34 },
	'gray-700': { l: 27 },
	'gray-800': { l: 17 },
	'gray-900': { l: 11 },

	'gray-900v': { s: 47, l: 34 },
	'gray-700v': { s: 58, l: 48 },

	'secondary': { l: 96 }, // gray-100
	'dark-secondary': { l: 27 }, // gray-700
};

const Style = {
	async install(Vue) {
		function rgbToHsl(r, g, b) {
			r /= 255, g /= 255, b /= 255;

			let max = Math.max(r, g, b), min = Math.min(r, g, b);
			let h, s, l = (max + min) / 2;

			if (max == min) {
				h = s = 0; // achromatic
			} else {
				let delta = max - min;
				s = l > 0.5 ? delta / (2 - max - min) : delta / (max + min);

				switch (max) {
					case r: h = (g - b) / delta + (g < b ? 6 : 0); break;
					case g: h = (b - r) / delta + 2; break;
					case b: h = (r - g) / delta + 4; break;
				}

				h /= 6;
			}

			return {
				h: (h * 360 + 0.5) | 0,
				s: (s * 100 + 0.5) | 0,
				l: (l * 100 + 0.5) | 0
			};
		}

		function buildStyle(hsl, url, useAlpha) {
			const { h, s } = hsl;

			// Background and border colors.
			let style = Object.entries(colors).map(([name, hue]) => {
				if (s === 0) hue.s = 0;

				const background = `background-color:hsla(${h},${hue.s || s}%,${hue.l}%,${useAlpha ? 0.6 : 1})`,
					border = `border-color:hsl(${h},${hue.s || s}%,${hue.l}%)`;

				return [
					`.bg-${name}{${background}}.dark .dark\\:bg-${name}{${background}}.hover\\:bg-${name}:hover{${background}!important}.dark .dark\\:hover\\:bg-${name}:hover{${background}!important}`,
					`.border-${name}{${border}}.dark .dark\\:border-${name}{${border}}.hover\\:border-${name}:hover{${border}!important}.dark .dark\\:hover\\:border-${name}:hover{${border}!important}`
				].join('');
			});

			// Black and white text colors.
			const black = `color:hsl(${h},${s}%,10%)`,
				white = `color:hsl(${h},${s}%,90%)`;

			style.push(`.text-white{${white}}.dark .dark\\:text-white{${white}}.hover\\:text-white:hover{${white}!important}.dark .dark\\:hover\\:text-white:hover{${white}!important}`),
				style.push(`.text-black{${black}}.dark .dark\\:text-black{${black}}.hover\\:text-black:hover{${black}!important}.dark .dark\\:hover\\:text-black:hover{${black}!important}`);

			// Input and placeholder colors.
			style.push(`[type="text"],[type="url"],[type="number"],input,select,textarea{border-color:hsl(${h},${s}%,45%)}input::placeholder,textarea::placeholder{color:hsl(${h},${s}%,50%)}`);

			// Link colors.
			const ls = s === 0 ? 0 : 70;

			style.push(`a[class*="text-indigo-"],a[class*="text-blue-"]{color:hsl(${h},${ls}%,40%)!important}.dark a[class*="text-indigo-"],.dark a[class*="text-blue-"]{color:hsl(${h},${ls}%,80%)!important}`);

			// Actual banner styles.
			style.push(`.banner-bg{background-image:url(${url});background-size:cover;background-position:center;background-repeat:no-repeat}.sidebar,.navbar{background-color:transparent!important}`);

			// Backdrop blur.
			style.push(`.banner-bg::before{content:'';position:absolute;top:0;left:0;bottom:0;right:0;backdrop-filter:blur(20px);z-index:-1}`);

			return style.join("");
		}

		function loadStyle(url, useAlpha) {
			return new Promise((resolve, reject) => {
				const banner = new Image();

				banner.onload = () => {
					const thief = new ColorThief(),
						color = thief.getColor(banner);

					const hsl = rgbToHsl(color[0], color[1], color[2]);

					if (hsl.s < 20) {
						hsl.s = 0;
						hsl.h = 0;
					} else {
						hsl.s = 35;
					}

					const style = buildStyle(hsl, url, useAlpha);

					localStorage.setItem('banner', JSON.stringify({
						url: url,
						style: style,
						v: Iteration,
						alpha: useAlpha
					}));

					resolve(style);
				};

				banner.onerror = reject;

				banner.src = url;
			});
		};

		Vue.prototype.refreshStyle = async function () {
			$("#bannerTheme").remove();

			const banner = this.setting('banner'),
				bannerAlpha = this.setting('bannerAlpha');

			let data;

			try {
				data = JSON.parse(localStorage.getItem('banner'));
			} catch (e) { }

			if (!banner) {
				if (data) localStorage.removeItem('banner');

				return banner;
			}

			let style;

			if (!data || !data.style || data.url !== banner || data.v !== Iteration || data.alpha !== bannerAlpha || window.location.hostname === "localhost") {
				style = await loadStyle(banner, bannerAlpha);

				console.log("Rebuilt style.");
			} else {
				style = data.style;
			}

			$('head').append(`<style id="bannerTheme">${style}</style>`);

			return banner;
		};
	}
}

export default Style;
