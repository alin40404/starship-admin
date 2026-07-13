<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { DevModuleRow } from '#/api/dev-module';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';

import { Alert, Tag, Tooltip } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import { fetchDevModules } from '#/api/dev-module';
import { MODULE_PERMISSIONS } from '#/constants/module-permissions';
import { $t } from '#/locales';
import {
  devOnlyModuleTagColor,
  isDevOnlyModuleUnavailable,
} from '#/utils/dev-only-module';

import { useColumns } from './data';
import DetailDrawer from './modules/detail-drawer.vue';

const detailDrawerRef = ref<InstanceType<typeof DetailDrawer>>();

const { hasAccessByCodes } = useAccess();

const moduleAccess = {
  can: (code: string) =>
    hasAccessByCodes([MODULE_PERMISSIONS.MANAGE, code]),
};

function onActionClick({ code, row }: OnActionClickParams<DevModuleRow>) {
  if (code === 'detail') {
    detailDrawerRef.value?.open(row);
  }
}

const columns = useColumns(onActionClick, moduleAccess);

const [Grid, gridApi] = useVbenVxeGrid<DevModuleRow>({
  gridOptions: {
    columns: columns as VxeTableGridOptions<DevModuleRow>['columns'],
    height: 'auto',
    pagerConfig: { enabled: false },
    proxyConfig: {
      ajax: {
        query: async () => await fetchDevModules(),
      },
    },
    rowConfig: { keyField: 'name' },
    toolbarConfig: { custom: true, refresh: true, zoom: true },
  } as VxeTableGridOptions<DevModuleRow>,
});
</script>

<template>
  <Page auto-content-height>
    <div class="flex h-full min-h-0 flex-col">
      <Alert
        class="mb-4 shrink-0"
        :message="$t('dev.modules.hint')"
        show-icon
        type="info"
      />

      <DetailDrawer
        ref="detailDrawerRef"
        :can-update="moduleAccess.can(MODULE_PERMISSIONS.UPDATE)"
        @success="gridApi.reload()"
      />

      <div class="min-h-0 flex-1">
        <Grid class="h-full">
          <template #devOnly="{ row }">
            <Tooltip
              v-if="row.dev_only"
              :title="$t('dev.shared.devOnlyTooltip')"
            >
              <Tag :color="devOnlyModuleTagColor(row)">
                {{
                  isDevOnlyModuleUnavailable(row)
                    ? $t('dev.shared.devOnlyUnavailableTag')
                    : $t('dev.shared.devOnlyTag')
                }}
              </Tag>
            </Tooltip>
            <span v-else class="text-muted-foreground">—</span>
          </template>
        </Grid>
      </div>
    </div>
  </Page>
</template>
