<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { ThemeRow } from '#/api/dev-theme';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { IconifyIcon } from '@vben/icons';

import { Alert, Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  disableTheme,
  discoverThemes,
  enableTheme,
  fetchThemes,
  installTheme,
  switchTheme,
  uninstallTheme,
} from '#/api/dev-theme';
import { THEME_PERMISSIONS, THEME_STATUS } from '#/constants/theme-permissions';
import { $t } from '#/locales';

import { useColumns } from './data';
import DetailDrawer from './modules/detail-drawer.vue';

const detailDrawerRef = ref<InstanceType<typeof DetailDrawer>>();
const discovering = ref(false);
const selectedRows = ref<ThemeRow[]>([]);

const { hasAccessByCodes } = useAccess();

const themeAccess = {
  can: (code: string) =>
    hasAccessByCodes([THEME_PERMISSIONS.MANAGE, code]),
};

function syncSelection() {
  const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
  selectedRows.value = records as ThemeRow[];
}

function clearSelection() {
  gridApi?.grid?.clearCheckboxRow?.();
  selectedRows.value = [];
}

function filterEligibleRows(statuses: number[]) {
  return selectedRows.value.filter((row) => statuses.includes(row.status));
}

async function runBatchAction(
  rows: ThemeRow[],
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
    message.warning($t('dev.themes.batchPartial', [succeeded, failed]));
  } else {
    message.success(successMessage);
  }

  clearSelection();
  await gridApi.reload();
}

function confirmBatchAction(
  title: string,
  content: string,
  rows: ThemeRow[],
  action: (slug: string) => Promise<unknown>,
  successMessage: string,
) {
  if (rows.length === 0) {
    message.warning($t('dev.themes.batchNoEligible'));
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
  const rows = filterEligibleRows([THEME_STATUS.DISCOVERED]);
  confirmBatchAction(
    $t('dev.shared.install'),
    $t('dev.themes.batchInstallConfirm', [rows.length]),
    rows,
    installTheme,
    $t('dev.shared.installSuccess'),
  );
}

function onBatchEnable() {
  const rows = filterEligibleRows([
    THEME_STATUS.INSTALLED,
    THEME_STATUS.DISABLED,
  ]);
  confirmBatchAction(
    $t('system.shared.batchEnable'),
    $t('dev.themes.batchEnableConfirm', [rows.length]),
    rows,
    enableTheme,
    $t('dev.themes.enabledSuccess'),
  );
}

function onBatchDisable() {
  const rows = filterEligibleRows([THEME_STATUS.ENABLED]);
  confirmBatchAction(
    $t('system.shared.batchDisable'),
    $t('dev.themes.batchDisableConfirm', [rows.length]),
    rows,
    disableTheme,
    $t('dev.themes.disabledSuccess'),
  );
}

function onBatchUninstall() {
  const rows = filterEligibleRows([
    THEME_STATUS.INSTALLED,
    THEME_STATUS.DISABLED,
  ]);
  confirmBatchAction(
    $t('dev.shared.uninstall'),
    $t('dev.themes.batchUninstallConfirm', [rows.length]),
    rows,
    uninstallTheme,
    $t('dev.shared.uninstallSuccess'),
  );
}

async function runAction(
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

function onActionClick({ code, row }: OnActionClickParams<ThemeRow>) {
  if (code === 'detail') {
    detailDrawerRef.value?.open(row);
    return;
  }

  if (code === 'install') {
    void runAction(
      () => installTheme(row.slug),
      $t('dev.shared.installSuccess'),
    );
    return;
  }

  if (code === 'enable') {
    void runAction(
      () => enableTheme(row.slug),
      $t('dev.themes.enabledSuccess'),
    );
    return;
  }

  if (code === 'switch') {
    confirmAction(
      $t('dev.themes.switchTitle'),
      $t('dev.themes.switchConfirm', [row.title]),
      async () => {
        await runAction(
          () => switchTheme(row.slug, row.type),
          $t('dev.themes.switchedSuccess'),
        );
      },
    );
    return;
  }

  if (code === 'disable') {
    confirmAction(
      $t('dev.themes.disableTitle'),
      $t('dev.themes.disableConfirm', [row.title]),
      async () => {
        await runAction(
          () => disableTheme(row.slug),
          $t('dev.themes.disabledSuccess'),
        );
      },
    );
    return;
  }

  if (code === 'uninstall') {
    confirmAction(
      $t('dev.themes.uninstallTitle'),
      $t('dev.themes.uninstallConfirm', [row.title]),
      async () => {
        await runAction(
          () => uninstallTheme(row.slug),
          $t('dev.shared.uninstallSuccess'),
        );
      },
    );
  }
}

async function onDiscover() {
  discovering.value = true;
  try {
    const result = await discoverThemes();
    message.success(
      $t('dev.themes.discoverResult', [
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

const columns = useColumns(onActionClick, themeAccess);

const [Grid, gridApi] = useVbenVxeGrid<ThemeRow>({
  gridEvents: {
    checkboxAll: syncSelection,
    checkboxChange: syncSelection,
  },
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: columns as VxeTableGridOptions<ThemeRow>['columns'],
    height: 'auto',
    pagerConfig: { enabled: false },
    proxyConfig: {
      ajax: {
        query: async () => await fetchThemes(),
      },
    },
    rowConfig: { keyField: 'slug' },
    toolbarConfig: { custom: true, refresh: true, zoom: true },
  } as VxeTableGridOptions<ThemeRow>,
});
</script>

<template>
  <Page auto-content-height>
    <div class="flex h-full min-h-0 flex-col">
      <Alert
        class="mb-4 shrink-0"
        :message="$t('dev.themes.hint')"
        show-icon
        type="info"
      />

      <DetailDrawer ref="detailDrawerRef" />

      <div class="min-h-0 flex-1">
        <Grid class="h-full">
          <template #toolbar-actions>
            <div
              v-if="selectedRows.length > 0"
              class="vxe-grid-batch-toolbar"
            >
              <Button
                v-if="themeAccess.can(THEME_PERMISSIONS.INSTALL)"
                class="theme-batch-btn-install"
                @click="onBatchInstall"
              >
                {{ $t('dev.themes.batchInstall') }}
              </Button>
              <Button
                v-if="themeAccess.can(THEME_PERMISSIONS.ENABLE)"
                class="theme-batch-btn-enable ml-2"
                @click="onBatchEnable"
              >
                {{ $t('system.shared.batchEnable') }}
              </Button>
              <Button
                v-if="themeAccess.can(THEME_PERMISSIONS.DISABLE)"
                class="theme-batch-btn-disable ml-2"
                @click="onBatchDisable"
              >
                {{ $t('system.shared.batchDisable') }}
              </Button>
              <Button
                v-if="themeAccess.can(THEME_PERMISSIONS.UNINSTALL)"
                class="ml-2"
                danger
                @click="onBatchUninstall"
              >
                {{ $t('dev.themes.batchUninstall') }}
              </Button>
              <Button class="ml-2" type="link" @click="clearSelection">
                {{ $t('system.shared.clearSelection') }}
              </Button>
            </div>
            <Button
              v-else-if="themeAccess.can(THEME_PERMISSIONS.DISCOVER)"
              :loading="discovering"
              type="primary"
              @click="onDiscover"
            >
              <IconifyIcon class="size-4" icon="ep:search" />
              {{ $t('dev.themes.discoverThemes') }}
            </Button>
          </template>
        </Grid>
      </div>
    </div>
  </Page>
</template>

<style scoped>
.vxe-grid .theme-batch-btn-install.ant-btn {
  color: #fff;
  background-color: #1677ff;
  border-color: #1677ff;
}

.vxe-grid .theme-batch-btn-install.ant-btn:not(:disabled):hover {
  color: #fff;
  background-color: #4096ff;
  border-color: #4096ff;
}

.vxe-grid .theme-batch-btn-enable.ant-btn {
  color: #fff;
  background-color: #52c41a;
  border-color: #52c41a;
}

.vxe-grid .theme-batch-btn-enable.ant-btn:not(:disabled):hover {
  color: #fff;
  background-color: #73d13d;
  border-color: #73d13d;
}

.vxe-grid .theme-batch-btn-disable.ant-btn {
  color: #fff;
  background-color: #fa8c16;
  border-color: #fa8c16;
}

.vxe-grid .theme-batch-btn-disable.ant-btn:not(:disabled):hover {
  color: #fff;
  background-color: #ffa940;
  border-color: #ffa940;
}
</style>
