import { defineConfig } from "vite";
export default defineConfig({
  server: {
    host: "127.0.0.1",
    allowedHosts: ["web"],
    watch: { usePolling: process.env.CHOKIDAR_USEPOLLING === "true" },
    proxy: {
      "/api": {
        target: process.env.CB_API_TARGET || "http://127.0.0.1:8080",
        changeOrigin: false,
      },
    },
  },
});
