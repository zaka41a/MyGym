import { defineConfig, loadEnv } from "vite";
import react from "@vitejs/plugin-react-swc";
import tsconfigPaths from "vite-tsconfig-paths";

export default defineConfig(({ command, mode }) => {
  const env = loadEnv(mode, process.cwd(), "");
  return {
    base: command === "build" ? "/MyGym/frontend/" : "/",
    plugins: [react(), tsconfigPaths()],
    define: {
      __APP_ENV__: JSON.stringify(env.APP_ENV ?? mode)
    },
    build: {
      outDir: "dist",
      assetsDir: "assets",
      sourcemap: false,
      rollupOptions: {
        output: {
          manualChunks: {
            vendor: ['react', 'react-dom', 'react-router-dom'],
            ui: ['framer-motion', 'lucide-react']
          }
        }
      }
    },
    server: {
      port: 5173,
      proxy: {
        '/MyGym/backend': {
          target: 'http://localhost',
          changeOrigin: true,
          secure: false
        }
      }
    },
    test: {
      globals: true,
      environment: "jsdom",
      setupFiles: "./vitest.setup.ts",
      include: ["src/**/*.test.ts", "src/**/*.test.tsx"]
    }
  };
});
