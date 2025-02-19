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
		vue({
			transformAssetUrls: {
				includeAbsolute: false,
			},
		}),
		clearViewCachePlugin(),
	],
	resolve: {
		alias: {
			"@": path.resolve(__dirname, "resources/js"),
		},
	},
	build: {
		chunkSizeWarningLimit: 1024,
		manifest: "manifest.json",
		minify: "esbuild",
		copyPublicDir: false,
		emptyOutDir: true,
		outDir: "public/build",
		rollupOptions: {
			output: {
				sourcemap: false,
				entryFileNames: "assets/[hash].js",
				chunkFileNames: "assets/[hash].js",
				assetFileNames: "assets/[hash].[ext]",
				generatedCode: {
					arrowFunctions: true,
					constBindings: true,
					objectShorthand: true,
					reservedNamesAsProps: true,
				},
				hashCharacters: "hex",
			},
			external: [/^\/images\//m],
		},
	},
});
