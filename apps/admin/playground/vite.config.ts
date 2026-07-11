import { defineConfig } from '@vben/vite-config';
import type { DefineApplicationOptions, VbenViteConfig } from '@vben/vite-config';

const applicationConfig: DefineApplicationOptions = async () => ({
  application: {},
  vite: {
    server: {
      proxy: {
        '/api': {
          changeOrigin: true,
          rewrite: (path) => path.replace(/^\/api/, ''),
          // mock代理目标地址
          target: 'http://localhost:5320/api',
          ws: true,
        },
      },
    },
  },
});

const viteConfig: VbenViteConfig = defineConfig(applicationConfig);

export default viteConfig;
