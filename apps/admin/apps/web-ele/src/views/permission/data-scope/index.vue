<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { RoleRow, RoleSavePayload } from '#/api/system/role';

import { nextTick, ref } from 'vue';

import { Page, useVbenDrawer } from '@vben/common-ui';

import { ElMessage } from 'element-plus';

import { useVbenForm } from '#/adapter/form';
import { useVbenVxeGrid } from '#/adapter/vxe-table';
import { fetchRolePage, updateRole } from '#/api/system/role';

import { buildDataScopeFormSchema, useColumns, useGridFormSchema } from './data';

const editingId = ref<number | null>(null);

const [Form, formApi] = useVbenForm({
  schema: buildDataScopeFormSchema(),
  showDefaultActions: false,
});

const [Drawer, drawerApi] = useVbenDrawer({
  async onConfirm() {
    const { valid } = await formApi.validate();
    if (!valid) {
      return;
    }
    const values = (await formApi.getValues()) as RoleSavePayload;
    if (!editingId.value) {
      return;
    }
    drawerApi.lock();
    try {
      await updateRole(editingId.value, {
        data_scope: values.data_scope,
        remark: values.remark,
      });
      ElMessage.success('数据范围已更新');
      drawerApi.close();
      gridApi.query();
    } catch {
      drawerApi.unlock();
    } finally {
      drawerApi.unlock();
    }
  },
});

function onActionClick({ code, row }: OnActionClickParams<RoleRow>) {
  if (code === 'edit') {
    openEdit(row);
  }
}

function openEdit(row: RoleRow) {
  editingId.value = row.id;
  formApi.resetForm();
  drawerApi.setState({ title: '数据范围' }).open();
  nextTick(() => {
    formApi.setValues({
      data_scope: row.data_scope,
      name: row.name,
      remark: row.remark ?? '',
    });
  });
}

const [Grid, gridApi] = useVbenVxeGrid({
  formOptions: {
    schema: useGridFormSchema(),
    submitButtonOptions: { content: '查询' },
    submitOnChange: false,
  },
  gridOptions: {
    columns: useColumns(onActionClick),
    height: 'auto',
    keepSource: true,
    proxyConfig: {
      ajax: {
        query: async ({ page }, formValues) => {
          return await fetchRolePage({
            page: page.currentPage,
            per_page: page.pageSize,
            ...formValues,
          });
        },
      },
    },
    rowConfig: {
      keyField: 'id',
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
    <Drawer class="w-[480px]">
      <Form />
    </Drawer>
    <Grid />
  </Page>
</template>
