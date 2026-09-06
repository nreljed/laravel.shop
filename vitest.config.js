import { defineConfig } from "vitest/config";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { packageDedupes } from "./vite.dedupe.js";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    test: {
        environment: "jsdom",
        globals: true,
        pool: "threads",
        include: [
            "resources/js/**/*.test.{ts,tsx}",
        ],
        setupFiles: ["./resources/js/test-setup.ts"],
        coverage: {
            provider: "v8",
            reporter: ["text", "lcov"],
            include: [
                "resources/js/**/*.{ts,tsx}",
            ],
            exclude: [
                "resources/js/**/__tests__/**",
                "resources/js/**/*.test.*",
                "resources/js/Components/ui/**",
            ],
        },
    },
    resolve: {
        dedupe: packageDedupes,
        alias: {
            "@dashboard": path.resolve(__dirname, "vendor/nreljed/dashboard-core/resources/js"),
            "@dashboard-blog": path.resolve(__dirname, "vendor/nreljed/dashboard-core/src/Blog/Resources/js"),
            "@dashboard-themes": path.resolve(__dirname, "vendor/nreljed/dashboard-core/resources/themes"),
            "@": path.resolve(__dirname, "vendor/nreljed/dashboard-core/resources/js"),
            "@shop": path.resolve(__dirname, "vendor/nreljed/shop-module/src/Resources/js"),
        },
    },
});
