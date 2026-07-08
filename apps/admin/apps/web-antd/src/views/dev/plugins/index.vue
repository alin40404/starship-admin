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
    okText: '确定',
    cancelText: '取消',
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
    void runAction(row.slug, () => installPlugin(row.slug), '安装成功');
    return;
  }

  if (code === 'enable') {
    void runAction(
      row.slug,
      () => enablePlugin(row.slug),
      '已启用，请重启后端服务后生效',
    );
    return;
  }

  if (code === 'disable') {
    confirmAction('禁用插件', `确定禁用插件【${row.name}】吗？禁用后需重启后端服务。`, async () => {
      await runAction(
        row.slug,
        () => disablePlugin(row.slug),
        '已禁用，请重启后端服务后生效',
      );
    });
    return;
  }

  if (code === 'uninstall') {
    confirmAction('卸载插件', `确定卸载插件【${row.name}】吗？卸载后保留配置数据。`, async () => {
      await runAction(row.slug, () => uninstallPlugin(row.slug), '卸载成功');
    });
  }
}

async function onDiscover() {
  discovering.value = true;
  try {
    const result = await discoverPlugins();
    message.success(
      `发现 ${result.discovered} 个插件，新建 ${result.created} 个，更新 ${result.updated} 个`,
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
        message="扫描 plugins/ 目录下的 plugin.json，在此统一管理插件的安装、启用、禁用与配置。启用/禁用后需重启后端服务。"
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
              发现插件
            </Button>
          </template>
        </Grid>
      </div>
    </div>
  </Page>
</template>
