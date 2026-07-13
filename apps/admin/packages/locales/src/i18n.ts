import type { App } from 'vue';
import type { Locale } from 'vue-i18n';

import type {
  ImportLocaleFn,
  LoadMessageFn,
  LocaleSetupOptions,
  SupportedLanguagesType,
} from './typing';

import { unref } from 'vue';
import { createI18n } from 'vue-i18n';

import { useSimpleLocale } from '@vben-core/composables';

const i18n = createI18n({
  globalInjection: true,
  legacy: false,
  locale: '',
  messages: {},
});

const modules = import.meta.glob('./langs/**/*.json');

const { setSimpleLocale } = useSimpleLocale();

const localesMap = loadLocalesMapFromDir(
  /\.\/langs\/([^/]+)\/(.*)\.json$/,
  modules,
);
let loadMessages: LoadMessageFn;

/**
 * Load locale modules
 * @param modules
 */
function loadLocalesMap(modules: Record<string, () => Promise<unknown>>) {
  const localesMap: Record<Locale, ImportLocaleFn> = {};

  for (const [path, loadLocale] of Object.entries(modules)) {
    const key = path.match(/([\w-]*)\.(json)/)?.[1];
    if (key) {
      localesMap[key] = loadLocale as ImportLocaleFn;
    }
  }
  return localesMap;
}

/**
 * Load locale modules with directory structure
 * @param regexp - Regular expression to match language and file names
 * @param modules - The modules object containing paths and import functions
 * @returns A map of locales to their corresponding import functions
 */
function loadLocalesMapFromDir(
  regexp: RegExp,
  modules: Record<string, () => Promise<unknown>>,
): Record<Locale, ImportLocaleFn> {
  const localesRaw: Record<Locale, Record<string, () => Promise<unknown>>> = {};
  const localesMap: Record<Locale, ImportLocaleFn> = {};

  // Iterate over the modules to extract language and file names
  for (const path in modules) {
    const match = path.match(regexp);
    if (match) {
      const [_, locale, fileName] = match;
      if (locale && fileName) {
        if (!localesRaw[locale]) {
          localesRaw[locale] = {};
        }
        if (modules[path]) {
          localesRaw[locale][fileName] = modules[path];
        }
      }
    }
  }

  // Convert raw locale data into async import functions
  for (const [locale, files] of Object.entries(localesRaw)) {
    localesMap[locale] = async () => {
      const messages: Record<string, any> = {};
      for (const [fileName, importFn] of Object.entries(files)) {
        messages[fileName] = ((await importFn()) as any)?.default;
      }
      return { default: nestDottedLocaleKeys(messages) };
    };
  }

  return localesMap;
}

function deepMergeLocale(
  target: Record<string, unknown>,
  source: Record<string, unknown>,
): Record<string, unknown> {
  const result: Record<string, unknown> = { ...target };

  for (const [key, value] of Object.entries(source)) {
    const existing = result[key];
    if (
      typeof existing === 'object' &&
      existing !== null &&
      !Array.isArray(existing) &&
      typeof value === 'object' &&
      value !== null &&
      !Array.isArray(value)
    ) {
      result[key] = deepMergeLocale(
        existing as Record<string, unknown>,
        value as Record<string, unknown>,
      );
    } else {
      result[key] = value;
    }
  }

  return result;
}

/**
 * 将 `module.entity` 形式的扁平 key 嵌套为 vue-i18n 可解析的结构。
 * 例如 `generator.products` → `generator.products.columns.name`
 */
function nestDottedLocaleKeys(
  messages: Record<string, unknown>,
): Record<string, unknown> {
  const result: Record<string, unknown> = {};

  for (const [key, value] of Object.entries(messages)) {
    if (!key.includes('.')) {
      const existing = result[key];
      if (
        typeof existing === 'object' &&
        existing !== null &&
        !Array.isArray(existing) &&
        typeof value === 'object' &&
        value !== null &&
        !Array.isArray(value)
      ) {
        result[key] = deepMergeLocale(
          existing as Record<string, unknown>,
          value as Record<string, unknown>,
        );
      } else {
        result[key] = value;
      }
      continue;
    }

    const parts = key.split('.');
    let cursor: Record<string, unknown> = result;

    for (let i = 0; i < parts.length - 1; i++) {
      const part = parts[i]!;
      const existing = cursor[part];
      if (
        typeof existing !== 'object' ||
        existing === null ||
        Array.isArray(existing)
      ) {
        cursor[part] = {};
      }
      cursor = cursor[part] as Record<string, unknown>;
    }

    const leafKey = parts[parts.length - 1]!;
    const existingLeaf = cursor[leafKey];
    if (
      typeof existingLeaf === 'object' &&
      existingLeaf !== null &&
      !Array.isArray(existingLeaf) &&
      typeof value === 'object' &&
      value !== null &&
      !Array.isArray(value)
    ) {
      cursor[leafKey] = deepMergeLocale(
        existingLeaf as Record<string, unknown>,
        value as Record<string, unknown>,
      );
    } else {
      cursor[leafKey] = value;
    }
  }

  return result;
}

/**
 * Set i18n language
 * @param locale
 */
function setI18nLanguage(locale: Locale) {
  i18n.global.locale.value = locale;

  document?.querySelector('html')?.setAttribute('lang', locale);
}

async function setupI18n(app: App, options: LocaleSetupOptions = {}) {
  const { defaultLocale = 'zh-CN' } = options;
  // app可以自行扩展一些第三方库和组件库的国际化
  loadMessages = options.loadMessages || (async () => ({}));
  app.use(i18n);
  await loadLocaleMessages(defaultLocale);

  // 在控制台打印警告
  i18n.global.setMissingHandler((locale, key) => {
    if (options.missingWarn && key.includes('.')) {
      console.warn(
        `[intlify] Not found '${key}' key in '${locale}' locale messages.`,
      );
    }
  });
}

/**
 * Load locale messages
 * @param lang
 */
async function loadLocaleMessages(lang: SupportedLanguagesType) {
  if (unref(i18n.global.locale) === lang) {
    return setI18nLanguage(lang);
  }
  setSimpleLocale(lang);

  const message = await localesMap[lang]?.();

  if (message?.default) {
    i18n.global.setLocaleMessage(lang, message.default);
  }

  const mergeMessage = await loadMessages(lang);
  i18n.global.mergeLocaleMessage(lang, mergeMessage);

  return setI18nLanguage(lang);
}

export {
  i18n,
  loadLocaleMessages,
  loadLocalesMap,
  loadLocalesMapFromDir,
  setupI18n,
};
