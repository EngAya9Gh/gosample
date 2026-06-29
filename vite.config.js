const vite = require("vite");
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import { viteStaticCopy } from "vite-plugin-static-copy";
const lodash = require("lodash");

export default vite.defineConfig({
    build: {
        manifest: true,
        rtl: true,
        outDir: "public/build/",
        cssCodeSplit: true,
        rollupOptions: {
            output: {
                assetFileNames: (css) => {
                    if (css.name.split(".").pop() == "css") {
                        return "css/" + `[name]` + ".min." + "css";
                    } else {
                        return "icons/" + css.name;
                    }
                },
                entryFileNames: (chunkInfo) => {
                    // Add hash to Vue main.js for cache busting, keep legacy files unhashed
                    if (chunkInfo.name === 'main') {
                        return "js/[name]-[hash].js";
                    }
                    return "js/[name].js";
                },
            },
        },
    },
    plugins: [
        laravel({
            input: [
                "resources/scss/bootstrap.scss",
                "resources/scss/icons.scss",
                "resources/scss/app.scss",
                "resources/js/app.js",
                "resources/scss/app.rtl.scss",
                "resources/scss/custom.scss",
                // New Vue 3 + Tailwind SPA entry (frontend migration)
                "resources/js/vue/main.js",
            ],
            refresh: true,
        }),
        vue(),
        viteStaticCopy({
            targets: [
                {
                    src: "resources/fonts",
                    dest: "",
                },
                {
                    src: "resources/images",
                    dest: "",
                },
                {
                    src: "resources/js",
                    dest: "",
                },
                {
                    src: "resources/json",
                    dest: "",
                },
                {
                    src: "resources/libs",
                    dest: "",
                },
            ],
        }),
    ],
    resolve: {
        alias: {
            $: "jQuery",
        },
    },
});
