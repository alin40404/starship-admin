<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { PluginRow } from '#/api/dev-plugin';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { IconifyIcon } from '@vben/icons';

import { Alert, Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  disablePlugin,
  discoverPlugins,
  enablePlugin,
  fetchPlugins,
  installPlugin,
  uninstallPlugin,
} from '#/api/dev-plugin';
import { PLUGIN_PERMISSIONS, PLUGIN_STATUS } from '#/constants/plugin-permissions';
import { $t } from '#/locales';

import { useColumns } from './data';
import ConfigDrawer from './modules/config-drawer.vue';
import DetailDrawer from './modules/detail-drawer.vue';

const detailDrawerRef = ref<InstanceType<typeof DetailDrawer>>();
const configDrawerRef = ref<InstanceType<typeof ConfigDrawer>>();
const discovering = ref(false);
const selectedRows = ref<PluginRow[]>([]);

const { hasAccessByCodes } = useAccess();

const pluginAccess = {
  can: (code: string) =>
    hasAccessByCodes([PLUGIN_PERMISSIONS.MANAGE, code]),
};

function syncSelection() {
  const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
  selectedRows.value = records as PluginRow[];
}

function clearSelection() {
  gridApi?.grid?.clearCheckboxRow?.();
  selectedRows.value = [];
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
  await gridApi.reload();
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
  slug: string,
  action: () => Promise<unknown>,
  successMessage: string,
) {
  await action();
  message.success(successMessage);
  await gridApi.reload();
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
      row.slug,
      () => installPlugin(row.slug),
      $t('dev.shared.installSuccess'),
    );
    return;
  }

  if (code === 'enable') {
    void runAction(
      row.slug,
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
          row.slug,
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
          row.slug,
          () => uninstallPlugin(row.slug),
          $t('dev.shared.uninstallSuccess'),
        );
      },
    );
  }
}

async function onDiscover() {
  discovering.value = true;
  try {
    const result = await discoverPlugins();
    message.success(
      $t('dev.shared.discoverResult', [
        result.discovered,
        result.created,
        result.updated,
      ]),
    );
    await gridApi.reload();
  } finally {
    discovering.value = false;
  }
}

const columns = useColumns(onActionClick, pluginAccess);

const [Grid, gridApi] = useVbenVxeGrid<PluginRow>({
  gridEvents: {
    checkboxAll: syncSelection,
    checkboxChange: syncSelection,
  },
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: columns as VxeTableGridOptions<PluginRow>['columns'],
    height: 'auto',
    pagerConfig: { enabled: false },
    proxyConfig: {
      ajax: {
        query: async () => await fetchPlugins(),
      },
    },
    rowConfig: { keyField: 'slug' },
    toolbarConfig: { custom: true, refresh: true, zoom: true },
  } as VxeTableGridOptions<PluginRow>,
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
        @success="gridApi.reload()"
      />

      <div class="min-h-0 flex-1">
        <Grid class="h-full">
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
            <Button
              v-else-if="pluginAccess.can(PLUGIN_PERMISSIONS.DISCOVER)"
              :loading="discovering"
              type="primary"
              @click="onDiscover"
            >
              <IconifyIcon class="size-4" icon="ep:search" />
              {{ $t('dev.shared.discoverPlugins') }}
            </Button>
          </template>
        </Grid>
      </div>
    </div>
  </Page>
</template>

<style scoped>
/** 批量安装 / 启用 / 禁用（工具栏） */
.vxe-grid .plugin-batch-btn-install.ant-btn {
  color: #fff;
  background-color: #1677ff;
  border-color: #1677ff;
}

.vxe-grid .plugin-batch-btn-install.ant-btn:not(:disabled):hover {
  color: #fff;
  background-color: #4096ff;
  border-color: #4096ff;
}

.vxe-grid .plugin-batch-btn-enable.ant-btn {
  color: #fff;
  background-color: #52c41a;
  border-color: #52c41a;
}

.vxe-grid .plugin-batch-btn-enable.ant-btn:not(:disabled):hover {
  color: #fff;
  background-color: #73d13d;
  border-color: #73d13d;
}

.vxe-grid .plugin-batch-btn-disable.ant-btn {
  color: #fff;
  background-color: #fa8c16;
  border-color: #fa8c16;
}

.vxe-grid .plugin-batch-btn-disable.ant-btn:not(:disabled):hover {
  color: #fff;
  background-color: #ffa940;
  border-color: #ffa940;
}
</style>
