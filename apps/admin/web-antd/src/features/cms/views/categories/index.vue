<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { CategoryRow } from '#/features/cms/api';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import {
  deleteCategory,
  fetchCategoryPage,
  updateCategoryStatus,
} from '#/features/cms/api';
import { CMS_PERMISSIONS } from '#/features/cms/constants/cms-permissions';
import { $t } from '#/locales';

import { useColumns, useGridFormSchema } from './data';
import CategoryForm from './modules/form.vue';

type CategoryFormExpose = {
  open: (data?: CategoryRow | Record<string, never>) => void;
};

const categoryFormRef = ref<CategoryFormExpose>();
const { hasAccessByCodes } = useAccess();
const access = { can: (code: string) => hasAccessByCodes([code]) };

function onCreate() {
  categoryFormRef.value?.open({});
}

function onEdit(row: CategoryRow) {
  categoryFormRef.value?.open(row);
}

function onActionClick({ code, row }: OnActionClickParams<CategoryRow>) {
  if (code === 'edit') onEdit(row);
  else if (code === 'delete') onDelete(row);
}

async function onStatusBeforeChange(newVal: number, row: CategoryRow) {
  try {
    await updateCategoryStatus(row.id, newVal);
    message.success($t('crud.saved'));
    return true;
  } catch {
    return false;
  }
}

const columns = useColumns(onActionClick, access, onStatusBeforeChange);

const [CrudView, crudApi] = useResponsiveCrud<CategoryRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: $t('common.query') },
  },
  gridOptions: {
    columns: columns as VxeTableGridOptions<CategoryRow>['columns'],
    height: 'auto',
    proxyConfig: {
      ajax: {
        query: async ({ page }, formValues) =>
          fetchCategoryPage({
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
    rowConfig: { keyField: 'id' },
    toolbarConfig: { custom: true, refresh: true, search: true, zoom: true },
  } as VxeTableGridOptions<CategoryRow>,
});

const { reload } = crudApi;

function onRefresh() {
  reload();
}

function onDelete(row: CategoryRow) {
  Modal.confirm({
    content: `确认删除栏目「${row.name}」？`,
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('common.prompt'),
    async onOk() {
      await deleteCategory(row.id);
      message.success($t('crud.deleted'));
      onRefresh();
    },
  });
}
</script>

<template>
  <Page auto-content-height>
    <CategoryForm ref="categoryFormRef" @success="onRefresh" />
    <CrudView>
      <template #toolbar-actions>
        <Button
          v-if="access.can(CMS_PERMISSIONS.CATEGORIES_CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          新增栏目
        </Button>
      </template>
    </CrudView>
  </Page>
</template>
