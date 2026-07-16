<script lang="ts" setup>
import type {
  PluginRow,
  PluginTypeOption,
} from '#/features/system/api/dev-plugin';

import { computed, onMounted, ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { IconifyIcon } from '@vben/icons';

import { Button, Input, message, Modal, Spin } from 'ant-design-vue';

import {
  disablePlugin,
  enablePlugin,
  fetchPlugins,
  installPlugin,
  uninstallPlugin,
} from '#/features/system/api/dev-plugin';
import {
  PLUGIN_PERMISSIONS,
  PLUGIN_STATUS,
} from '#/features/system/constants/plugin-permissions';
import { $t } from '#/locales';

import ConfigDrawer from './modules/config-drawer.vue';
import DetailDrawer from './modules/detail-drawer.vue';
import FlowView from './modules/flow-view.vue';

const detailDrawerRef = ref<InstanceType<typeof DetailDrawer>>();
const configDrawerRef = ref<InstanceType<typeof ConfigDrawer>>();
const loading = ref(false);
const keyword = ref('');
const typeFilter = ref('all');
const allRows = ref<PluginRow[]>([]);
const typeCatalog = ref<PluginTypeOption[]>([]);
const statusLoading = ref<Record<string, boolean>>({});

const { hasAccessByCodes } = useAccess();

const pluginAccess = {
  can: (code: string) =>
    hasAccessByCodes([PLUGIN_PERMISSIONS.MANAGE, code]),
};

const typeOptions = computed(() => [
  { label: $t('dev.plugins.typeAll'), value: 'all' },
  ...typeCatalog.value,
]);

const TYPE_ICONS: Record<string, string> = {
  all: 'lucide:layout-grid',
  payment: 'lucide:credit-card',
  sms: 'lucide:message-square-text',
  email: 'lucide:mail',
  oauth: 'lucide:key-round',
  storage: 'lucide:hard-drive',
  ai: 'lucide:sparkles',
  feature: 'lucide:puzzle',
  other: 'lucide:package',
};

function typeIcon(type: string) {
  return TYPE_ICONS[type] ?? 'lucide:box';
}

const keywordMatchedRows = computed(() => {
  const key = keyword.value.trim().toLowerCase();
  if (!key) {
    return allRows.value;
  }

  return allRows.value.filter((row) => {
    const haystack = [
      row.slug,
      row.name,
      row.description ?? '',
      row.type ?? '',
      row.type_label ?? '',
      row.author ?? '',
    ]
      .join(' ')
      .toLowerCase();
    return haystack.includes(key);
  });
});

const typeCounts = computed(() => {
  const counts: Record<string, number> = {
    all: keywordMatchedRows.value.length,
  };

  for (const row of keywordMatchedRows.value) {
    const type = row.type?.trim();
    if (!type) {
      continue;
    }
    counts[type] = (counts[type] ?? 0) + 1;
  }

  return counts;
});

const filteredRows = computed(() => {
  if (typeFilter.value === 'all') {
    return keywordMatchedRows.value;
  }

  return keywordMatchedRows.value.filter(
    (row) => row.type === typeFilter.value,
  );
});

async function reload() {
  loading.value = true;
  try {
    const result = await fetchPlugins();
    allRows.value = result.items;
    typeCatalog.value = result.types;
  } finally {
    loading.value = false;
  }
}

async function runAction(
  action: () => Promise<unknown>,
  successMessage: string,
) {
  await action();
  message.success(successMessage);
  await reload();
}

function confirmAction(
  title: string,
  content: string,
  onOk: () => Promise<void>,
) {
  Modal.confirm({
    title,
    content,
    okText: $t('common.confirm'),
    cancelText: $t('common.cancel'),
    onOk,
  });
}

function onAction(code: string, row: PluginRow) {
  if (code === 'detail') {
    detailDrawerRef.value?.open(row);
    return;
  }

  if (code === 'config') {
    configDrawerRef.value?.open(row);
    return;
  }

  if (code === 'install') {
    void runAction(
      () => installPlugin(row.slug),
      $t('dev.shared.installSuccess'),
    );
    return;
  }

  if (code === 'uninstall') {
    confirmAction(
      $t('dev.shared.uninstallPluginTitle'),
      $t('dev.shared.uninstallPluginConfirm', [row.name]),
      async () => {
        await runAction(
          () => uninstallPlugin(row.slug),
          $t('dev.shared.uninstallSuccess'),
        );
      },
    );
  }
}

async function onStatusChange(enabled: boolean, row: PluginRow) {
  if (statusLoading.value[row.slug]) {
    return;
  }

  const nextEnabled = enabled;
  const currentlyEnabled = row.status === PLUGIN_STATUS.ENABLED;

  if (nextEnabled === currentlyEnabled) {
    return;
  }

  if (!nextEnabled) {
    confirmAction(
      $t('dev.shared.disablePluginTitle'),
      $t('dev.shared.disablePluginConfirm', [row.name]),
      async () => {
        statusLoading.value = { ...statusLoading.value, [row.slug]: true };
        try {
          await runAction(
            () => disablePlugin(row.slug),
            $t('dev.plugins.disabledSuccess'),
          );
        } finally {
          const next = { ...statusLoading.value };
          delete next[row.slug];
          statusLoading.value = next;
        }
      },
    );
    return;
  }

  statusLoading.value = { ...statusLoading.value, [row.slug]: true };
  try {
    await runAction(
      () => enablePlugin(row.slug),
      $t('dev.plugins.enabledSuccess'),
    );
  } finally {
    const next = { ...statusLoading.value };
    delete next[row.slug];
    statusLoading.value = next;
  }
}

onMounted(() => {
  void reload();
});
</script>

<template>
  <Page auto-content-height>
    <div class="flex h-full min-h-0 flex-col">
      <DetailDrawer ref="detailDrawerRef" />
      <ConfigDrawer
        ref="configDrawerRef"
        :access="pluginAccess"
        @success="reload()"
      />

      <div class="plugin-shell bg-card">
        <aside class="plugin-type-aside">
          <!-- 标题：插件（不可点击，字号加大） -->
          <div class="plugin-type-aside__title">
            {{ $t('dev.plugins.entity') }}
          </div>
          <!-- 类型菜单：往下显示，保持不变 -->
          <nav class="plugin-type-aside__list">
            <button
              v-for="item in typeOptions"
              :key="item.value"
              class="plugin-type-aside__item"
              :class="{ 'is-active': typeFilter === item.value }"
              type="button"
              @click="typeFilter = item.value"
            >
              <span class="plugin-type-aside__main">
                <IconifyIcon
                  class="plugin-type-aside__icon"
                  :icon="typeIcon(item.value)"
                />
                <span class="plugin-type-aside__label">{{ item.label }}</span>
              </span>
              <span class="plugin-type-aside__count">
                {{ typeCounts[item.value] ?? 0 }}
              </span>
            </button>
          </nav>
        </aside>

        <div class="plugin-main">
          <div class="plugin-toolbar-row">
            <div class="plugin-toolbar">
              <Input
                v-model:value="keyword"
                allow-clear
                class="plugin-toolbar__input"
                :placeholder="$t('dev.plugins.searchPlaceholder')"
              >
                <template #prefix>
                  <IconifyIcon
                    class="plugin-toolbar__prefix-icon"
                    icon="lucide:search"
                  />
                </template>
              </Input>
              <span class="plugin-toolbar__total">
                {{ $t('dev.plugins.totalCount', [filteredRows.length]) }}
              </span>
            </div>
            <Button :loading="loading" @click="reload">
              <IconifyIcon class="size-4" icon="lucide:refresh-cw" />
              {{ $t('common.refresh') }}
            </Button>
          </div>

          <div class="plugin-content relative min-h-0 flex-1 p-4">
            <div
              v-if="loading && allRows.length === 0"
              class="absolute inset-0 z-10 flex items-center justify-center bg-card/60"
            >
              <Spin />
            </div>
            <FlowView
              :access="pluginAccess"
              :loading="loading && allRows.length === 0"
              :rows="filteredRows"
              :status-loading="statusLoading"
              @action="onAction"
              @status-change="onStatusChange"
            />
          </div>
        </div>
      </div>
    </div>
  </Page>
</template>

<style scoped>
.plugin-shell {
  display: flex;
  min-height: 0;
  flex: 1;
  overflow: hidden;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
}

.plugin-type-aside {
  display: flex;
  width: 200px;
  flex-shrink: 0;
  flex-direction: column;
  overflow: auto;
  border-right: 1px solid hsl(var(--border));
  background: hsl(var(--background));
}

.plugin-type-aside__title {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  min-height: 56px;
  padding: 0 16px;
  border-bottom: 1px solid hsl(var(--border));
  color: hsl(var(--foreground));
  font-size: 18px;
  font-weight: 700;
  line-height: 1.2;
  pointer-events: none;
  user-select: none;
}

.plugin-type-aside__list {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 10px 8px;
}

.plugin-type-aside__item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  width: 100%;
  min-height: 40px;
  padding: 10px 12px;
  border: 1px solid transparent;
  border-radius: var(--radius);
  background: transparent;
  color: hsl(var(--foreground));
  font-size: 13px;
  line-height: 1.4;
  text-align: left;
  cursor: pointer;
}

.plugin-type-aside__item:hover {
  background: hsl(var(--accent));
}

.plugin-type-aside__item.is-active {
  background: hsl(var(--primary) / 0.12);
  color: hsl(var(--primary));
}

.plugin-type-aside__main {
  display: flex;
  min-width: 0;
  align-items: center;
  gap: 8px;
}

.plugin-type-aside__icon {
  flex-shrink: 0;
  width: 1rem;
  height: 1rem;
  color: hsl(var(--muted-foreground));
}

.plugin-type-aside__item.is-active .plugin-type-aside__icon {
  color: hsl(var(--primary));
}

.plugin-type-aside__label {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.plugin-type-aside__count {
  flex-shrink: 0;
  min-width: 1.25rem;
  padding: 0 6px;
  border-radius: 999px;
  background: hsl(var(--muted) / 0.85);
  color: hsl(var(--muted-foreground));
  font-size: 11px;
  font-weight: 500;
  line-height: 1.45;
  text-align: center;
}

.plugin-type-aside__item.is-active .plugin-type-aside__count {
  background: hsl(var(--primary) / 0.14);
  color: hsl(var(--primary));
}

.plugin-main {
  display: flex;
  min-width: 0;
  min-height: 0;
  flex: 1;
  flex-direction: column;
}

.plugin-toolbar-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  min-height: 56px;
  padding: 0 16px;
  border-bottom: 1px solid hsl(var(--border));
}

.plugin-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
  flex: 1;
}

.plugin-toolbar__input {
  width: min(280px, 46vw);
}

.plugin-toolbar__input.ant-input-affix-wrapper {
  height: 32px;
  border-radius: var(--radius);
}

.plugin-toolbar__prefix-icon {
  width: 0.875rem;
  height: 0.875rem;
  color: hsl(var(--muted-foreground));
}

.plugin-toolbar__total {
  flex-shrink: 0;
  color: hsl(var(--muted-foreground));
  font-size: 0.875rem;
  line-height: 1.5;
  white-space: nowrap;
}

.plugin-content {
  min-width: 0;
}

@media (max-width: 768px) {
  .plugin-shell {
    flex-direction: column;
  }

  .plugin-type-aside {
    width: 100%;
    border-right: 0;
    border-bottom: 1px solid hsl(var(--border));
    overflow: hidden;
  }

  .plugin-type-aside__title {
    display: none;
  }

  .plugin-type-aside__list {
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 6px;
    padding: 8px 12px;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
  }

  .plugin-type-aside__list::-webkit-scrollbar {
    display: none;
  }

  .plugin-type-aside__item {
    flex: 0 0 auto;
    width: auto;
    min-height: 36px;
    padding: 6px 12px;
    border-color: hsl(var(--border));
    white-space: nowrap;
  }

  .plugin-type-aside__item.is-active {
    border-color: hsl(var(--primary) / 0.35);
  }
}
</style>
