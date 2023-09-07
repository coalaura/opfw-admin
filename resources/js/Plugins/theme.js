const Theme = {
	async install(Vue, options) {
		let darkMode = false;

		function refreshTheme () {
			const cachedTheme = localStorage.getItem('theme') ? localStorage.getItem('theme') : false;
			const userPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

			darkMode = cachedTheme ? cachedTheme === 'dark' : userPrefersDark;

			if (darkMode) {
				$('html').addClass('dark');
			} else {
				$('html').removeClass('dark');
			}
		};

		Vue.prototype.toggleTheme = function () {
			if (darkMode) {
				localStorage.setItem('theme', 'light');
			} else {
				localStorage.setItem('theme', 'dark');
			}

			refreshTheme();
		};

		Vue.prototype.isDarkMode = () => darkMode;

		refreshTheme();
	},
}

export default Theme;
