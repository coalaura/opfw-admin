import path from "node:path";
import { exec } from "node:child_process";

import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue2";

function clearViewCachePlugin() {
	return {
		name: "clear-view-cache",
		buildEnd() {
			exec("php artisan view:clear", error => {
				if (error) {
					console.error("Error clearing view cache:", error);

					return;
				}

				console.log("Laravel view cache cleared");
			});
		},
	};
}

export default defineConfig({
	publicDir: "public",
	plugins: [
		laravel({
			input: ["resources/js/app.js", "resources/css/app.pcss"],
			refresh: true,
		}),
		vue(),
		clearViewCachePlugin(),
	],
	resolve: {
		alias: {
			"@": path.resolve(__dirname, "resources/js"),
		},
	},
	build: {
		manifest: true,
	},
});
