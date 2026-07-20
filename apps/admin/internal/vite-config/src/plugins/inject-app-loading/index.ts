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
  const cacheName = `'${env.VITE_APP_NAMESPACE}-${version}-${envRaw}-preferences-theme'`;

  // 获取缓存的主题
  // 保证黑暗主题下，刷新页面时，loading也是黑暗主题
  const injectScript = `
  <script data-app-loading="inject-js">
  var theme = localStorage.getItem(${cacheName});
  document.documentElement.classList.toggle('dark', /dark/.test(theme));
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
        const re = /<body\s*>/;
        return html.replace(re, `<body>${injectScript}${loadingHtml}`);
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
