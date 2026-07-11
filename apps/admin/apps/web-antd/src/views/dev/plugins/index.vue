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
import { PLUGIN_PERMISSIONS } from '#/constants/plugin-permissions';
import { $t } from '#/locales';

import { useColumns } from './data';
import ConfigDrawer from './modules/config-drawer.vue';
import DetailDrawer from './modules/detail-drawer.vue';

const detailDrawerRef = ref<InstanceType<typeof DetailDrawer>>();
const configDrawerRef = ref<InstanceType<typeof ConfigDrawer>>();
const discovering = ref(false);

const { hasAccessByCodes } = useAccess();

const pluginAccess = {
  can: (code: string) =>
    hasAccessByCodes([PLUGIN_PERMISSIONS.MANAGE, code]),
};

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
      $t('dev.shared.restartBackendHint'),
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
          $t('dev.shared.restartBackendHint'),
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
  gridOptions: {
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
            <Button
              v-if="pluginAccess.can(PLUGIN_PERMISSIONS.DISCOVER)"
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
