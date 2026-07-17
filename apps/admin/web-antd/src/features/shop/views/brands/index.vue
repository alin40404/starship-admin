<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { BrandRow } from '#/features/shop/api';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import { deleteBrand, fetchBrandPage } from '#/features/shop/api';
import { SHOP_PERMISSIONS } from '#/features/shop/constants/shop-permissions';
import { $t } from '#/locales';

import { useColumns, useGridFormSchema } from './data';
import BrandForm from './modules/form.vue';

type BrandFormExpose = { open: (data?: BrandRow | Record<string, never>) => void };

const brandFormRef = ref<BrandFormExpose>();
const { hasAccessByCodes } = useAccess();
const access = { can: (code: string) => hasAccessByCodes([code]) };

function onCreate() {
  brandFormRef.value?.open({});
}

function onEdit(row: BrandRow) {
  brandFormRef.value?.open(row);
}

function onActionClick({ code, row }: OnActionClickParams<BrandRow>) {
  if (code === 'edit') onEdit(row);
  else if (code === 'delete') onDelete(row);
}

const columns = useColumns(onActionClick, access);

const [CrudView, crudApi] = useResponsiveCrud<BrandRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: $t('common.query') },
  },
  gridOptions: {
    columns: columns as VxeTableGridOptions<BrandRow>['columns'],
    height: 'auto',
    proxyConfig: {
      ajax: {
        query: async ({ page }, formValues) =>
          fetchBrandPage({
            page: page.currentPage,
            per_page: page.pageSize,
            keyword: formValues?.keyword || undefined,
          }),
      },
    },
    rowConfig: { keyField: 'id' },
    toolbarConfig: { custom: true, refresh: true, search: true, zoom: true },
  } as VxeTableGridOptions<BrandRow>,
});

const { reload } = crudApi;

function onRefresh() {
  reload();
}

function onDelete(row: BrandRow) {
  Modal.confirm({
    content: `确认删除品牌「${row.name}」？`,
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('common.prompt'),
    async onOk() {
      await deleteBrand(row.id);
      message.success($t('crud.deleted'));
      onRefresh();
    },
  });
}
</script>

<template>
  <Page auto-content-height>
    <BrandForm ref="brandFormRef" @success="onRefresh" />
    <CrudView>
      <template #toolbar-actions>
        <Button
          v-if="access.can(SHOP_PERMISSIONS.BRANDS_CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          新增品牌
        </Button>
      </template>
    </CrudView>
  </Page>
</template>
