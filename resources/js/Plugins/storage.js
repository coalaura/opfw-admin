const Storage = {
	async install(Vue, options) {
		const pageId = (prefix = "") => {
			let id = window.location.pathname.replace(/[^\w]+/g, "_").replace(/^_+|_+$/gm, "");

			if (prefix) {
				id = `${prefix}_${id}`;
			}

			return id;
		};

		Vue.prototype.pageStore = {
			get: (key, def = null) => {
				return localStorage.getItem(pageId(key)) || def;
			},
			set: (key, value) => {
				return localStorage.setItem(pageId(key), value);
			},
			remove: key => {
				return localStorage.removeItem(pageId(key));
			},
		};
	},
};

export default Storage;
