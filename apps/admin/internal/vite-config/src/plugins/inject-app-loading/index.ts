import type { PluginOption } from 'vite';

import fs from 'node:fs';
import fsp from 'node:fs/promises';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';

import { readPackageJSON } from '@vben/node-utils';

/**
 * 用于生成将loading样式注入到项目中
 * 为多app提供loading样式，无需在每个 app -> index.html单独引入
 */
async function viteInjectAppLoadingPlugin(
  isBuild: boolean,
  env: Record<string, any> = {},
  loadingTemplate = 'loading.html',
): Promise<PluginOption | undefined> {
  const { version } = await readPackageJSON(process.cwd());
  const envRaw = isBuild ? 'prod' : 'dev';
  const namespace = String(env.VITE_APP_NAMESPACE || '');
  const preferredCacheKey = namespace
    ? `${namespace}-${version}-${envRaw}-preferences-theme`
    : '';

  // 尽早根据 localStorage 偏好给 <html> 打上 dark，避免首屏 Loading 闪浅色
  // StorageManager 存的是 JSON：{"value":"dark"|"light"|"auto"}
  const injectScript = `
  <script data-app-loading="inject-js">
  (function () {
    try {
      var preferredKey = ${JSON.stringify(preferredCacheKey)};
      var raw = preferredKey ? localStorage.getItem(preferredKey) : null;
      if (!raw) {
        for (var i = 0; i < localStorage.length; i++) {
          var k = localStorage.key(i);
          if (k && k.slice(-18) === '-preferences-theme') {
            raw = localStorage.getItem(k);
            if (raw) break;
          }
        }
      }
      var mode = raw;
      if (raw) {
        try {
          var parsed = JSON.parse(raw);
          if (parsed && typeof parsed === 'object' && 'value' in parsed) {
            mode = parsed.value;
          }
        } catch (e) {}
      }
      var isDark = false;
      if (mode === 'dark') {
        isDark = true;
      } else if (mode === 'light') {
        isDark = false;
      } else if (mode === 'auto' || mode == null || mode === '') {
        isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      } else if (typeof mode === 'string' && /dark/.test(mode) && !/light/.test(mode)) {
        // 兼容未解析到 value 的旧缓存串
        isDark = true;
      }
      document.documentElement.classList.toggle('dark', isDark);
    } catch (e) {}
  })();
  </script>
`;

  let loadingHtml = '';

  return {
    configResolved: async (config) => {
      loadingHtml = await getLoadingRawByHtmlTemplate(
        loadingTemplate,
        config.root,
      );
    },
    enforce: 'pre',
    name: 'vite:inject-app-loading',
    transformIndexHtml: {
      handler(html) {
        if (!loadingHtml) {
          return html;
        }
        // 主题脚本放进 head，尽量赶在首次绘制前；Loading 节点仍注入 body
        let result = html;
        if (result.includes('</head>')) {
          result = result.replace('</head>', `${injectScript}</head>`);
        }
        const re = /<body\s*>/;
        if (re.test(result)) {
          result = result.replace(re, `<body>${loadingHtml}`);
        }
        return result;
      },
      order: 'pre',
    },
  };
}

/**
 * 用于获取loading的html模板
 * 优先读取应用根目录（Vite config.root）下的 loading.html
 */
async function getLoadingRawByHtmlTemplate(
  loadingTemplate: string,
  appRoot = process.cwd(),
) {
  const candidates = [
    join(appRoot, loadingTemplate),
    join(process.cwd(), loadingTemplate),
  ];

  for (const candidate of candidates) {
    if (fs.existsSync(candidate)) {
      return await fsp.readFile(candidate, 'utf8');
    }
  }

  const __dirname = fileURLToPath(new URL('.', import.meta.url));
  return await fsp.readFile(join(__dirname, './default-loading.html'), 'utf8');
}

export { viteInjectAppLoadingPlugin };
