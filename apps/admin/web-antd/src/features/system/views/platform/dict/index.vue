<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { DictTypeRow } from '#/features/system/api/dict';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page, useVbenDrawer } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { $t } from '@vben/locales';

import { Button, message } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import { deleteDictType, fetchDictTypePage } from '#/features/system/api/dict';
import { DICT_PERMISSIONS } from '#/features/system/constants/dict-permissions';

import {
  buildDictTypeMobileCrudConfig,
  useDictTypeColumns,
  useDictTypeGridFormSchema,
} from './data';
import DictItemsDrawer from './modules/items-drawer.vue';
import DictTypeForm from './modules/type-form.vue';

const typeFormRef = ref<InstanceType<typeof DictTypeForm>>();

const { hasAccessByCodes } = useAccess();

const dictAccess = {
  can: (code: string) => hasAccessByCodes([code]),
};

const [DictItemsPanel, itemsDrawerApi] = useVbenDrawer({
  connectedComponent: DictItemsDrawer,
  destroyOnClose: true,
});

function onCreate() {
  typeFormRef.value?.open({});
}

function onEdit(row: DictTypeRow) {
  typeFormRef.value?.open(row);
}

function onManageItems(row: DictTypeRow) {
  itemsDrawerApi.setData(row).open();
}

function onActionClick({ code, row }: OnActionClickParams<DictTypeRow>) {
  if (code === 'items') onManageItems(row);
  else if (code === 'edit') onEdit(row);
  else if (code === 'delete') onDelete(row);
}

async function onDelete(row: DictTypeRow) {
  await deleteDictType(row.id);
  message.success($t('crud.deleted'));
  onRefresh();
}

const columns = useDictTypeColumns(onActionClick, dictAccess);

const [CrudView, crudApi] = useResponsiveCrud<DictTypeRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useDictTypeGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: $t('common.query') },
  },
  gridOptions: {
    columns: columns as VxeTableGridOptions<DictTypeRow>['columns'],
    height: 'auto',
    proxyConfig: {
      ajax: {
        query: async ({ page, sort }, formValues) => {
          const order = sort?.order;
          return await fetchDictTypePage({
            page: page.currentPage,
            per_page: page.pageSize,
            code: formValues?.code || undefined,
            name: formValues?.name || undefined,
            sort_field: sort?.field,
            sort_order:
              order === 'asc' || order === 'desc' ? order : undefined,
            status:
              formValues?.status === undefined || formValues?.status === null
                ? undefined
                : formValues.status,
          });
        },
      },
      sort: true,
    },
    rowConfig: { keyField: 'id' },
    sortConfig: { remote: true },
    toolbarConfig: { custom: true, refresh: true, search: true, zoom: true },
  } as VxeTableGridOptions<DictTypeRow>,
  mobile: buildDictTypeMobileCrudConfig({
    columns,
        query: async ({ filters, keyword, page, pageSize, sortField, sortOrder }) => {
      const result = await fetchDictTypePage({
        page,
        per_page: pageSize,
        sort_field: sortField,
        sort_order: sortOrder,
        keyword: keyword || undefined,
        code: filters?.code || undefined,
        name: filters?.name || undefined,
        status:
          filters?.status === undefined || filters?.status === null
            ? undefined
            : filters.status,
      });
      return {
        data: result.data,
        total: result.total,
      };
    },
  }),
});

const { reload } = crudApi;

function onRefresh() {
  reload();
}
</script>

<template>
  <Page auto-content-height>
    <DictTypeForm ref="typeFormRef" @success="onRefresh" />
    <DictItemsPanel @success="onRefresh" />
    <CrudView>
      <template #toolbar-actions>
        <Button
          v-if="dictAccess.can(DICT_PERMISSIONS.CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          {{ $t('platform.dict.createDict') }}
        </Button>
      </template>
    </CrudView>
  </Page>
</template>
