const Escape = {
	install(Vue, options) {
		Vue.prototype.escapeHtml = unsafe => {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        };
	},
};

export default Escape;
