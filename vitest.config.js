import { defineConfig } from "vitest/config";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { packageDedupes } from "./vite.dedupe.js";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    server: {
        fs: {
            allow: [path.resolve(__dirname, "../../laravel/app")],
        },
    },
    test: {
        environment: "jsdom",
        globals: true,
        include: [
            "resources/js/**/*.test.{ts,tsx}",
            "../../laravel/app/packages/dashboard-core/resources/js/**/*.test.{ts,tsx}",
        ],
        setupFiles: ["./resources/js/test-setup.ts"],
        coverage: {
            provider: "v8",
            reporter: ["text", "lcov"],
            include: [
                "resources/js/**/*.{ts,tsx}",
                "../../laravel/app/packages/dashboard-core/resources/js/**/*.{ts,tsx}",
            ],
            exclude: [
                "resources/js/**/__tests__/**",
                "resources/js/**/*.test.*",
                "resources/js/Components/ui/**",
                "../../laravel/app/packages/dashboard-core/resources/js/**/__tests__/**",
                "../../laravel/app/packages/dashboard-core/resources/js/**/*.test.*",
                "../../laravel/app/packages/dashboard-core/resources/js/Components/ui/**",
            ],
        },
    },
    resolve: {
        dedupe: packageDedupes,
        alias: {
            "@": path.resolve(__dirname, "../../laravel/app/packages/dashboard-core/resources/js"),
        },
    },
});
