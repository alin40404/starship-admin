<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { PluginRow } from '#/features/system/api/dev-plugin';

import { ref, watch } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { IconifyIcon } from '@vben/icons';
import { usePreferences } from '@vben/preferences';

import { useDebounceFn } from '@vueuse/core';
import { Alert, Button, Input, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import {
  disablePlugin,
  enablePlugin,
  fetchPlugins,
  installPlugin,
  uninstallPlugin,
} from '#/features/system/api/dev-plugin';
import { PLUGIN_PERMISSIONS, PLUGIN_STATUS } from '#/features/system/constants/plugin-permissions';
import { $t } from '#/locales';

import { buildPluginMobileCrudConfig, useColumns } from './data';
import ConfigDrawer from './modules/config-drawer.vue';
import DetailDrawer from './modules/detail-drawer.vue';

const detailDrawerRef = ref<InstanceType<typeof DetailDrawer>>();
const configDrawerRef = ref<InstanceType<typeof ConfigDrawer>>();
const keyword = ref('');
const pluginTotal = ref(0);

const { isMobile } = usePreferences();
const { hasAccessByCodes } = useAccess();

const pluginAccess = {
  can: (code: string) =>
    hasAccessByCodes([PLUGIN_PERMISSIONS.MANAGE, code]),
};

function filterPlugins(rows: PluginRow[], search?: string) {
  const key = search?.trim().toLowerCase();
  if (!key) {
    return rows;
  }

  return rows.filter((row) => {
    const haystack = [
      row.slug,
      row.name,
      row.description ?? '',
      row.type ?? '',
    ]
      .join(' ')
      .toLowerCase();
    return haystack.includes(key);
  });
}

async function loadPluginRows(search?: string) {
  const all = await fetchPlugins();
  pluginTotal.value = all.length;
  return filterPlugins(all, search);
}

function filterEligibleRows(statuses: number[]) {
  return selectedRows.value.filter((row) => statuses.includes(row.status));
}

async function runBatchAction(
  rows: PluginRow[],
  action: (slug: string) => Promise<unknown>,
  successMessage: string,
) {
  let succeeded = 0;
  let failed = 0;

  for (const row of rows) {
    try {
      await action(row.slug);
      succeeded += 1;
    } catch {
      failed += 1;
    }
  }

  if (failed > 0) {
    message.warning($t('dev.plugins.batchPartial', [succeeded, failed]));
  } else {
    message.success(successMessage);
  }

  clearSelection();
  await reload();
}

function confirmBatchAction(
  title: string,
  content: string,
  rows: PluginRow[],
  action: (slug: string) => Promise<unknown>,
  successMessage: string,
) {
  if (rows.length === 0) {
    message.warning($t('dev.plugins.batchNoEligible'));
    return;
  }

  Modal.confirm({
    title,
    content,
    okText: $t('common.confirm'),
    cancelText: $t('common.cancel'),
    async onOk() {
      await runBatchAction(rows, action, successMessage);
    },
  });
}

function onBatchInstall() {
  const rows = filterEligibleRows([PLUGIN_STATUS.DISCOVERED]);
  confirmBatchAction(
    $t('dev.shared.install'),
    $t('dev.plugins.batchInstallConfirm', [rows.length]),
    rows,
    installPlugin,
    $t('dev.shared.installSuccess'),
  );
}

function onBatchEnable() {
  const rows = filterEligibleRows([
    PLUGIN_STATUS.INSTALLED,
    PLUGIN_STATUS.DISABLED,
  ]);
  confirmBatchAction(
    $t('system.shared.batchEnable'),
    $t('dev.plugins.batchEnableConfirm', [rows.length]),
    rows,
    enablePlugin,
    $t('dev.plugins.enabledSuccess'),
  );
}

function onBatchDisable() {
  const rows = filterEligibleRows([PLUGIN_STATUS.ENABLED]);
  confirmBatchAction(
    $t('system.shared.batchDisable'),
    $t('dev.plugins.batchDisableConfirm', [rows.length]),
    rows,
    disablePlugin,
    $t('dev.plugins.disabledSuccess'),
  );
}

function onBatchUninstall() {
  const rows = filterEligibleRows([
    PLUGIN_STATUS.INSTALLED,
    PLUGIN_STATUS.DISABLED,
  ]);
  confirmBatchAction(
    $t('dev.shared.uninstall'),
    $t('dev.plugins.batchUninstallConfirm', [rows.length]),
    rows,
    uninstallPlugin,
    $t('dev.shared.uninstallSuccess'),
  );
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

function onActionClick({ code, row }: OnActionClickParams<PluginRow>) {
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

  if (code === 'enable') {
    void runAction(
      () => enablePlugin(row.slug),
      $t('dev.plugins.enabledSuccess'),
    );
    return;
  }

  if (code === 'disable') {
    confirmAction(
      $t('dev.shared.disablePluginTitle'),
      $t('dev.shared.disablePluginConfirm', [row.name]),
      async () => {
        await runAction(
          () => disablePlugin(row.slug),
          $t('dev.plugins.disabledSuccess'),
        );
      },
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

const columns = useColumns(onActionClick, pluginAccess);

const [CrudView, crudApi] = useResponsiveCrud<PluginRow>({
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: columns as VxeTableGridOptions<PluginRow>['columns'],
    height: 'auto',
    pagerConfig: { enabled: false },
    proxyConfig: {
      ajax: {
        query: async () => await loadPluginRows(keyword.value),
      },
    },
    rowConfig: { keyField: 'slug' },
    toolbarConfig: { custom: true, refresh: true, zoom: true },
  } as VxeTableGridOptions<PluginRow>,
  mobile: buildPluginMobileCrudConfig({
    columns,
    query: async ({ keyword: mobileKeyword, page, pageSize }) => {
      const data = await loadPluginRows(mobileKeyword);
      const start = (page - 1) * pageSize;
      return {
        data: data.slice(start, start + pageSize),
        total: data.length,
      };
    },
  }),
});

const { clearSelection, reload, selectedRows } = crudApi;

const debouncedReload = useDebounceFn(() => {
  void reload();
}, 300);

watch(keyword, () => {
  if (isMobile.value) {
    return;
  }
  debouncedReload();
});
</script>

<template>
  <Page auto-content-height>
    <div class="flex h-full min-h-0 flex-col">
      <Alert
        class="mb-4 shrink-0"
        :message="$t('dev.plugins.hint')"
        show-icon
        type="info"
      />

      <DetailDrawer ref="detailDrawerRef" />
      <ConfigDrawer
        ref="configDrawerRef"
        :access="pluginAccess"
        @success="reload()"
      />

      <div class="min-h-0 flex-1">
        <CrudView class="h-full">
          <template #toolbar-actions>
            <div
              v-if="selectedRows.length > 0"
              class="vxe-grid-batch-toolbar"
            >
              <Button
                v-if="pluginAccess.can(PLUGIN_PERMISSIONS.INSTALL)"
                class="plugin-batch-btn-install"
                @click="onBatchInstall"
              >
                {{ $t('dev.plugins.batchInstall') }}
              </Button>
              <Button
                v-if="pluginAccess.can(PLUGIN_PERMISSIONS.ENABLE)"
                class="plugin-batch-btn-enable ml-2"
                @click="onBatchEnable"
              >
                {{ $t('system.shared.batchEnable') }}
              </Button>
              <Button
                v-if="pluginAccess.can(PLUGIN_PERMISSIONS.DISABLE)"
                class="plugin-batch-btn-disable ml-2"
                @click="onBatchDisable"
              >
                {{ $t('system.shared.batchDisable') }}
              </Button>
              <Button
                v-if="pluginAccess.can(PLUGIN_PERMISSIONS.UNINSTALL)"
                class="ml-2"
                danger
                @click="onBatchUninstall"
              >
                {{ $t('dev.plugins.batchUninstall') }}
              </Button>
              <Button class="ml-2" type="link" @click="clearSelection">
                {{ $t('system.shared.clearSelection') }}
              </Button>
            </div>
            <div v-else class="plugin-toolbar-search">
              <Input
                v-if="!isMobile"
                v-model:value="keyword"
                allow-clear
                class="plugin-toolbar-search__input"
                :placeholder="$t('dev.plugins.searchPlaceholder')"
                @press-enter="reload()"
              >
                <template #prefix>
                  <IconifyIcon
                    class="plugin-toolbar-search__prefix-icon"
                    icon="lucide:search"
                  />
                </template>
              </Input>
              <span class="plugin-toolbar-search__total">
                {{ $t('dev.plugins.totalCount', [pluginTotal]) }}
              </span>
            </div>
          </template>
        </CrudView>
      </div>
    </div>
  </Page>
</template>

<style scoped>
.plugin-toolbar-search {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.plugin-toolbar-search__input {
  width: min(280px, 46vw);
}

.plugin-toolbar-search__input.ant-input-affix-wrapper {
  height: 32px;
  border-radius: var(--radius);
}

.plugin-toolbar-search__prefix-icon {
  width: 0.875rem;
  height: 0.875rem;
  color: hsl(var(--muted-foreground));
}

.plugin-toolbar-search__total {
  flex-shrink: 0;
  color: hsl(var(--muted-foreground));
  font-size: 0.875rem;
  line-height: 1.5;
  white-space: nowrap;
}

.vxe-grid .plugin-batch-btn-install.ant-btn,
.mobile-crud-list .plugin-batch-btn-install.ant-btn {
  color: #fff;
  background-color: #1677ff;
  border-color: #1677ff;
}

.vxe-grid .plugin-batch-btn-install.ant-btn:not(:disabled):hover,
.mobile-crud-list .plugin-batch-btn-install.ant-btn:not(:disabled):hover {
  color: #fff;
  background-color: #4096ff;
  border-color: #4096ff;
}

.vxe-grid .plugin-batch-btn-enable.ant-btn,
.mobile-crud-list .plugin-batch-btn-enable.ant-btn {
  color: #fff;
  background-color: #52c41a;
  border-color: #52c41a;
}

.vxe-grid .plugin-batch-btn-enable.ant-btn:not(:disabled):hover,
.mobile-crud-list .plugin-batch-btn-enable.ant-btn:not(:disabled):hover {
  color: #fff;
  background-color: #73d13d;
  border-color: #73d13d;
}

.vxe-grid .plugin-batch-btn-disable.ant-btn,
.mobile-crud-list .plugin-batch-btn-disable.ant-btn {
  color: #fff;
  background-color: #fa8c16;
  border-color: #fa8c16;
}

.vxe-grid .plugin-batch-btn-disable.ant-btn:not(:disabled):hover,
.mobile-crud-list .plugin-batch-btn-disable.ant-btn:not(:disabled):hover {
  color: #fff;
  background-color: #ffa940;
  border-color: #ffa940;
}
</style>
