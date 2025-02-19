import "./bootstrap.js";

import { InertiaApp } from "@inertiajs/inertia-vue";
import Vue from "vue";
import PortalVue from "portal-vue";
import Toast from "vue-toastification";

import Localization from "./Plugins/localization.js";
import Theme from "./Plugins/theme.js";
import Markdown from "./Plugins/markdown.js";
import Chunked from "./Plugins/chunked.js";
import Identifiers from "./Plugins/identifiers.js";
import Copy from "./Plugins/copy-text.js";
import Wait from "./Plugins/wait.js";
import Interceptor from "./Plugins/interceptor.js";
import Socket from "./Plugins/socket.js";
import UserAgent from "./Plugins/user-agent.js";
import Dictionary from "./Plugins/dictionary.js";
import Permissions from "./Plugins/permissions.js";
import Settings from "./Plugins/settings.js";
import Storage from "./Plugins/storage.js";
import Spreadsheet from "./Plugins/spreadsheet.js";
import Style from "./Plugins/style.js";
import Flags from "./Plugins/flags.js";
import humanizeSeconds from "./Filters/humanizeSeconds.js";
import formatTime from "./Filters/formatTime.js";
import formatGender from "./Filters/formatGender.js";
import linkify from "vue-linkify";

import "leaflet/dist/leaflet.css";
import "leaflet-gesture-handling/dist/leaflet-gesture-handling.css";
import "leaflet-fullscreen/dist/leaflet.fullscreen.css";
import "vue-toastification/dist/index.css";
import "vue-search-select/dist/VueSearchSelect.css";

import "./scripts/overrides.js";
import "./scripts/chartjs-highlights.js";

// Directives.
Vue.directive("linkified", linkify);

// Get page
const app = document.getElementById("app"),
	page = JSON.parse(app.dataset.page);

// Create global event bus.
Vue.prototype.$bus = new Vue();

// Plugins.
Vue.use(InertiaApp);
Vue.use(Interceptor);
Vue.use(Theme);
Vue.use(PortalVue);
Vue.use(Localization);
Vue.use(Storage);
Vue.use(Spreadsheet);
Vue.use(Copy);
Vue.use(Wait);
Vue.use(Chunked);
Vue.use(Socket);
Vue.use(UserAgent);
Vue.use(Dictionary);
Vue.use(Markdown);
Vue.use(Identifiers);
Vue.use(Flags);
Vue.use(Permissions, page);
Vue.use(Settings, page);
Vue.use(Style);
Vue.use(Toast, {
	transition: "Vue-Toastification__slideBlurred",
	maxToasts: 10,
	newestOnTop: true,
});

// Properties / methods.
Vue.prototype.$moment = dayjs;

// Custom filters.
Vue.filter("humanizeSeconds", humanizeSeconds);
Vue.filter("formatTime", formatTime);
Vue.filter("formatGender", formatGender);

Vue.directive("click-outside", {
	bind: (el, binding, vnode) => {
		el.clickOutsideEvent = event => {
			if (!(el === event.target || el.contains(event.target))) {
				vnode.context[binding.expression](event);
			}
		};

		document.body.addEventListener("click", el.clickOutsideEvent);
	},
	unbind: el => {
		document.body.removeEventListener("click", el.clickOutsideEvent);
	},
});

Vue.component("scoped-style", {
	render: function (createElement) {
		return createElement("style", this.$slots.default);
	},
});

// Create Vue.
const pages = import.meta.glob("./Pages/**/*.vue");

new Vue({
	el: app,
	render: h =>
		h(InertiaApp, {
			props: {
				initialPage: page,
				resolveComponent: name => {
					const importPage = pages[`./Pages/${name}.vue`];

					if (importPage) {
						return importPage().then(module => module.default);
					}

					return Promise.reject(new Error(`Page not found: ${name}.vue`));
				},
			},
		}),
});
