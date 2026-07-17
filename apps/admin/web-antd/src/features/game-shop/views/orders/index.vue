<script lang="ts" setup>
import type { VxeTableGridOptions } from '#/adapter/vxe-table';
import type { GameOrderRow } from '#/features/game-shop/api';

import { Page } from '@vben/common-ui';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import { fetchGameOrderPage } from '#/features/game-shop/api';
import { $t } from '#/locales';

import { useColumns, useGridFormSchema } from './data';

const columns = useColumns();

const [CrudView] = useResponsiveCrud<GameOrderRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: $t('common.query') },
  },
  gridOptions: {
    columns: columns as VxeTableGridOptions<GameOrderRow>['columns'],
    height: 'auto',
    proxyConfig: {
      ajax: {
        query: async ({ page }, formValues) =>
          fetchGameOrderPage({
            page: page.currentPage,
            per_page: page.pageSize,
            keyword: formValues?.keyword || undefined,
            status:
              formValues?.status === undefined || formValues?.status === null
                ? undefined
                : formValues.status,
          }),
      },
    },
  },
});
</script>

<template>
  <Page auto-content-height>
    <CrudView table-title="游戏订单" />
  </Page>
</template>
