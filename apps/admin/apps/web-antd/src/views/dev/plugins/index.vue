<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { PluginRow } from '#/api/dev-plugin';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { RefreshCw } from '@vben/icons';

import { Alert, Button, message } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import { discoverPlugins, fetchPlugins } from '#/api/dev-plugin';
import { PLUGIN_PERMISSIONS } from '#/constants/plugin-permissions';

import { useColumns } from './data';
import DetailDrawer from './modules/detail-drawer.vue';

const detailDrawerRef = ref<InstanceType<typeof DetailDrawer>>();
const discovering = ref(false);

const { hasAccessByCodes } = useAccess();

const pluginAccess = {
  can: (code: string) =>
    hasAccessByCodes([PLUGIN_PERMISSIONS.MANAGE, code]),
};

function onActionClick({ code, row }: OnActionClickParams<PluginRow>) {
  if (code === 'detail') {
    detailDrawerRef.value?.open(row);
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
    <Alert
      class="mb-4"
      message="扫描 plugins/ 目录下的 plugin.json，在此统一管理插件的安装、启用、禁用与配置。启用/禁用后需重启后端服务。"
      show-icon
      type="info"
    />

    <DetailDrawer
      ref="detailDrawerRef"
      :access="pluginAccess"
      @success="gridApi.reload()"
    />

    <Grid>
      <template #toolbar-tools>
        <Button
          v-if="pluginAccess.can(PLUGIN_PERMISSIONS.DISCOVER)"
          :loading="discovering"
          type="primary"
          @click="onDiscover"
        >
          <RefreshCw class="size-4" />
          发现插件
        </Button>
      </template>
    </Grid>
  </Page>
</template>
