<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { UserGroupRow } from '#/api/system/user-group';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page, useVbenModal } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  batchDeleteUserGroups,
  deleteUserGroup,
  fetchUserGroupPage,
} from '#/api/system/user-group';
import { USER_GROUP_PERMISSIONS } from '#/constants/user-group-permissions';

import { useColumns, useGridFormSchema } from './data';
import UserGroupForm from './modules/form.vue';

const { hasAccessByCodes } = useAccess();

const groupAccess = {
  can: (code: string) => hasAccessByCodes([code]),
};

const [UserGroupFormModal, groupFormModalApi] = useVbenModal({
  connectedComponent: UserGroupForm,
  destroyOnClose: true,
});

const selectedRows = ref<UserGroupRow[]>([]);

function syncSelection() {
  const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
  selectedRows.value = records as UserGroupRow[];
}

function clearSelection() {
  gridApi?.grid?.clearCheckboxRow?.();
  selectedRows.value = [];
}

function onCreate() {
  groupFormModalApi.setData({}).open();
}

function onEdit(row: UserGroupRow) {
  groupFormModalApi.setData(row).open();
}

function onRefresh() {
  gridApi.query();
}

function onActionClick({ code, row }: OnActionClickParams<UserGroupRow>) {
  if (code === 'edit') onEdit(row);
  else if (code === 'delete') onDelete(row);
}

async function onDelete(row: UserGroupRow) {
  await deleteUserGroup(row.id);
  message.success('已删除');
  clearSelection();
  gridApi.query();
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) return;
  Modal.confirm({
    content: `确定删除选中的 ${ids.length} 个用户组吗？`,
    okText: '删除',
    okType: 'danger',
    title: '批量删除',
    async onOk() {
      await batchDeleteUserGroups(ids);
      message.success('已批量删除');
      clearSelection();
      onRefresh();
    },
  });
}

const [Grid, gridApi] = useVbenVxeGrid<UserGroupRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: '查询' },
  },
  gridEvents: {
    checkboxAll: syncSelection,
    checkboxChange: syncSelection,
  },
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: useColumns(onActionClick, groupAccess),
    height: 'auto',
    keepSource: true,
    proxyConfig: {
      ajax: {
        query: async ({ page, sort }, formValues) => {
          const order = sort?.order;
          return await fetchUserGroupPage({
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
  } as VxeTableGridOptions<UserGroupRow>,
});
</script>

<template>
  <Page auto-content-height>
    <UserGroupFormModal @success="onRefresh" />
    <Grid>
      <template #toolbar-actions>
        <template v-if="selectedRows.length > 0">
          <Button
            v-if="groupAccess.can(USER_GROUP_PERMISSIONS.BATCH_DELETE)"
            danger
            @click="onBatchDelete"
          >
            批量删除（{{ selectedRows.length }}）
          </Button>
          <Button class="ml-2" type="link" @click="clearSelection">
            取消选择
          </Button>
        </template>
        <Button
          v-else-if="groupAccess.can(USER_GROUP_PERMISSIONS.CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          新增用户组
        </Button>
      </template>
    </Grid>
  </Page>
</template>
