import "./bootstrap.js";

import { InertiaApp } from "@inertiajs/inertia-vue";
import Vue from "vue";
import PortalVue from "portal-vue";
import Toast from "vue-toastification";
import NProgress from "nprogress";

import Localization from "./Plugins/localization.js";
import MasterTab from "./Plugins/master-tab.js";
import Theme from "./Plugins/theme.js";
import Markdown from "./Plugins/markdown.js";
import Chunked from "./Plugins/chunked.js";
import Identifiers from "./Plugins/identifiers.js";
import Copy from "./Plugins/copy-text.js";
import Escape from "./Plugins/escape.js";
import MapEncoder from "./Plugins/map-encoder.js";
import Wait from "./Plugins/wait.js";
import Interceptor from "./Plugins/interceptor.js";
import Socket from "./Plugins/socket.js";
import Updates from "./Plugins/updates.js";
import UserAgent from "./Plugins/user-agent.js";
import Dictionary from "./Plugins/dictionary.js";
import Permissions from "./Plugins/permissions.js";
import Settings from "./Plugins/settings.js";
import Storage from "./Plugins/storage.js";
import Spreadsheet from "./Plugins/spreadsheet.js";
import Style from "./Plugins/style.js";
import Flags from "./Plugins/flags.js";
import Search from "./Plugins/search.js";

import humanizeSeconds from "./Filters/humanizeSeconds.js";
import formatSeconds from "./Filters/formatSeconds.js";
import formatTime from "./Filters/formatTime.js";
import formatDate from "./Filters/formatDate.js";
import formatGender from "./Filters/formatGender.js";

import linkify from "vue-linkify";

import "@fortawesome/fontawesome-free/css/all.min.css";
import "leaflet/dist/leaflet.css";
import "leaflet-gesture-handling/dist/leaflet-gesture-handling.css";
import "leaflet-fullscreen/dist/leaflet.fullscreen.css";
import "vue-toastification/dist/index.css";
import "vue-search-select/dist/VueSearchSelect.css";
import "nprogress/nprogress.css";

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
Vue.use(MasterTab);
Vue.use(Localization);
Vue.use(Storage);
Vue.use(Spreadsheet);
Vue.use(Copy);
Vue.use(Escape);
Vue.use(MapEncoder);
Vue.use(Wait);
Vue.use(Chunked);
Vue.use(Socket);
Vue.use(Updates);
Vue.use(UserAgent);
Vue.use(Dictionary);
Vue.use(Markdown);
Vue.use(Identifiers);
Vue.use(Flags);
Vue.use(Permissions, page);
Vue.use(Settings, page);
Vue.use(Style);
Vue.use(Search);
Vue.use(Toast, {
	transition: "Vue-Toastification__slideBlurred",
	maxToasts: 10,
	newestOnTop: true,
});

// Custom filters.
Vue.filter("humanizeSeconds", humanizeSeconds);
Vue.filter("formatSeconds", formatSeconds);
Vue.filter("formatTime", formatTime);
Vue.filter("formatDate", formatDate);
Vue.filter("formatGender", formatGender);

// For using filters as functions (add/remove as needed)
Vue.prototype.formatSeconds = formatSeconds;
Vue.prototype.formatTime = formatTime;

Vue.directive("handle-error", {
	bind: (el, binding) => {
		const fallbackUrl = binding.value,
			emptyPx = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";

		el.handleLoadEvent = () => {
			if (el.src === emptyPx) {
				return;
			}

			el.classList.remove("errored");
		};

		el.handleErrorEvent = () => {
			if (fallbackUrl) {
				el.src = fallbackUrl;
			} else {
				el.src = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";

				el.classList.add("errored");

				el.addEventListener("load", el.handleLoadEvent);
			}

			el.removeAttribute("srcset");

			delete el.dataset.lazy;
		};

		el.addEventListener("error", el.handleErrorEvent);
	},
	unbind: el => {
		el.removeEventListener("load", el.handleLoadEvent);
		el.removeEventListener("error", el.handleErrorEvent);

		delete el.handleLoadEvent;
		delete el.handleErrorEvent;
	},
});

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

NProgress.configure({
	showSpinner: true,
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
