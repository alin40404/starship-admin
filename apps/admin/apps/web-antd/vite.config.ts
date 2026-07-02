import { defineConfig } from '@vben/vite-config';

export default defineConfig(async () => {
  return {
    application: {},
    vite: {
      server: {
        proxy: {
          // Starship Admin 后端（Laravel `php artisan serve` 默认 8000）
          '/admin-api': {
            changeOrigin: true,
            target: 'http://127.0.0.1:8000',
            ws: true,
          },
          // 公共附件（public 磁盘）走 Laravel public/storage 软链
          '/storage': {
            changeOrigin: true,
            target: 'http://127.0.0.1:8000',
          },
        },
      },
    },
  };
});
