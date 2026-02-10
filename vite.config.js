import { defineConfig } from "vite";
import { normalize, resolve } from "node:path";
import fs from "node:fs";

const entryPath = normalize(resolve(__dirname, "assets/js/index.js"));
const outDir = "public/build";

function symfonyManifestPlugin({ outDir }) {
    return {
        name: "symfony-manifest",
        closeBundle() {
            const viteManifestPath = normalize(resolve(__dirname, outDir, ".vite/manifest.json"));
            const symfonyManifestPath = normalize(resolve(__dirname, outDir, "manifest.json"));

            const viteManifest = JSON.parse(fs.readFileSync(viteManifestPath, "utf-8"));

            // Convert: key -> object{file: "..."}  into: key -> "..."
            const flat = {};
            for (const [key, val] of Object.entries(viteManifest)) {
                if (!val?.file) continue;

                // Symfony wants logical paths as keys (your assets/js/...)
                // Skip absolute filesystem keys (e.g. /home/... ?commonjs-*)
                if (key.startsWith("/")) continue;

                flat[key] = val.file;
            }

            fs.writeFileSync(symfonyManifestPath, JSON.stringify(flat, null, 2));
        },
    };
}

export default defineConfig(({ mode }) => {
    const isDevBuild = mode === "development";
    return {
        publicDir: false,
        build: {
            outDir,
            manifest: true,
            emptyOutDir: true,
            sourcemap: isDevBuild,
            minify: isDevBuild ? false : "esbuild",
            target: "es2018",
            lib: {
                entry: entryPath,
                formats: ["es"],
                fileName: () => "js/index.js",
            },
            rollupOptions: {
                preserveEntrySignatures: "exports-only",
                output: {
                    format: "es",
                    preserveModules: true,
                    preserveModulesRoot: normalize(resolve(__dirname, "assets/js")),
                    entryFileNames: "js/[name]-[hash].js",
                    chunkFileNames: "js/chunk/[name]-[hash].js",
                    assetFileNames: "assets/[name]-[hash][extname]",
                }
            }
        },
        plugins: [symfonyManifestPlugin({ outDir })],
    };
});