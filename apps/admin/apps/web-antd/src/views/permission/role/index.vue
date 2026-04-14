<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { RoleRow } from '#/api/system/role';

import { Page, useVbenDrawer } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import { deleteRole, fetchRolePage, updateRole } from '#/api/system/role';

import { useColumns, useGridFormSchema } from './data';
import RoleForm from './modules/form.vue';

const [RoleFormDrawer, roleFormDrawerApi] = useVbenDrawer({
  connectedComponent: RoleForm,
  destroyOnClose: true,
});

function onCreate() {
  roleFormDrawerApi.setData({}).open();
}

function onEdit(row: RoleRow) {
  roleFormDrawerApi.setData(row).open();
}

/** 超级管理员：只读查看（与 web-ele 一致） */
function onView(row: RoleRow) {
  roleFormDrawerApi.setData({ ...row, _mode: 'view' as const }).open();
}

function onRefresh() {
  gridApi.query();
}

function onActionClick({ code, row }: OnActionClickParams<RoleRow>) {
  if (code === 'view') {
    onView(row);
  } else if (code === 'edit') {
    onEdit(row);
  } else if (code === 'delete') {
    onDelete(row);
  }
}

async function onRoleStatusToggle(nextStatus: number, row: RoleRow) {
  if (row.code === 'super_admin') {
    return false;
  }
  return new Promise<boolean>((resolve) => {
    const label = nextStatus === 1 ? '启用' : '禁用';
    Modal.confirm({
      content: `确定将「${row.name}」的状态切换为【${label}】吗？`,
      okText: '确定',
      cancelText: '取消',
      title: '切换状态',
      async onOk() {
        try {
          await updateRole(row.id, { status: nextStatus });
          message.success('状态已更新');
          resolve(true);
        } catch {
          resolve(false);
        }
      },
      onCancel() {
        resolve(false);
      },
    });
  });
}

async function onDelete(row: RoleRow) {
  await deleteRole(row.id);
  message.success('已删除');
  gridApi.query();
}

const [Grid, gridApi] = useVbenVxeGrid<RoleRow>({
  formOptions: {
    commonConfig: {
      componentProps: {
        class: 'w-full',
      },
    },
    schema: useGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: {
      content: '查询',
    },
  },
  gridOptions: {
    columns: useColumns(onActionClick, onRoleStatusToggle),
    height: 'auto',
    keepSource: true,
    proxyConfig: {
      ajax: {
        query: async ({ page, sort }, formValues) => {
          const order = sort?.order;
          return await fetchRolePage({
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
    rowConfig: {
      keyField: 'id',
    },
    sortConfig: {
      remote: true,
    },
    toolbarConfig: {
      custom: true,
      refresh: true,
      search: true,
      zoom: true,
    },
  } as VxeTableGridOptions<RoleRow>,
});
</script>

<template>
  <Page auto-content-height>
    <RoleFormDrawer class="max-w-[720px] w-[92vw]" @success="onRefresh" />
    <Grid>
      <template #toolbar-actions>
        <Button type="primary" @click="onCreate">
          <Plus class="size-4" />
          新增角色
        </Button>
      </template>
    </Grid>
  </Page>
</template>
