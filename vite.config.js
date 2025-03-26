import path from "node:path";
import { exec } from "node:child_process";

import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue2";

function clearViewCachePlugin() {
	return {
		name: "clear-view-cache",
		writeBundle() {
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

const isDev = process.env.IS_DEV === "true";

export default defineConfig({
	publicDir: "public",
	plugins: [
		laravel({
			input: ["resources/js/app.js", "resources/css/app.pcss"],
			hotFile: "public/hot",
			refresh: true,
		}),
		vue({
			transformAssetUrls: {
				includeAbsolute: false,
			},
		}),
		clearViewCachePlugin(),
	],
	server: {
		hmr: true,
		host: "localhost",
		port: 5173,
	},
	resolve: {
		alias: {
			"@": path.resolve(__dirname, "resources/js"),
		},
	},
	build: {
		chunkSizeWarningLimit: 1024,
		manifest: "manifest.json",
		minify: isDev ? false : "esbuild",
		copyPublicDir: false,
		emptyOutDir: true,
		outDir: "public/build",
		sourcemap: isDev,
		rollupOptions: {
			output: {
				entryFileNames: "assets/[hash].js",
				chunkFileNames: "assets/[hash].js",
				assetFileNames: "assets/[hash].[ext]",
				generatedCode: !isDev
					? {
							arrowFunctions: true,
							constBindings: true,
							objectShorthand: true,
							reservedNamesAsProps: true,
						}
					: {},
				hashCharacters: "hex",
			},
			external: [/^\/images\//m],
		},
	},
});
