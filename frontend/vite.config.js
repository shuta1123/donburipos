import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";

export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      "/api": {
        target: "http://localhost:8888",
        changeOrigin: true,
        secure: false,
        // /api を /github/.../backend/api に付け替える
        rewrite: (path) =>
          path.replace(/^\/api/, "/github/donburipos/backend/api"),
      },
    },
  },
});
