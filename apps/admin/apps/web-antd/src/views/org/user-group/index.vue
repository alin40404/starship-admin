<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { UserGroupRow } from '#/api/system/user-group';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import {
  batchDeleteUserGroups,
  deleteUserGroup,
  fetchUserGroupPage,
} from '#/api/system/user-group';
import { USER_GROUP_PERMISSIONS } from '#/constants/user-group-permissions';

import {
  buildUserGroupMobileCrudConfig,
  useColumns,
  useGridFormSchema,
} from './data';
import UserGroupForm from './modules/form.vue';

type UserGroupFormExpose = {
  open: (data?: UserGroupRow | Record<string, never>) => void;
};

const userGroupFormRef = ref<UserGroupFormExpose>();

const { hasAccessByCodes } = useAccess();

const groupAccess = {
  can: (code: string) => hasAccessByCodes([code]),
};

function onCreate() {
  userGroupFormRef.value?.open({});
}

function onEdit(row: UserGroupRow) {
  userGroupFormRef.value?.open(row);
}

function onActionClick({ code, row }: OnActionClickParams<UserGroupRow>) {
  if (code === 'edit') onEdit(row);
  else if (code === 'delete') onDelete(row);
}

const columns = useColumns(onActionClick, groupAccess);

const [CrudView, crudApi] = useResponsiveCrud<UserGroupRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: '查询' },
  },
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: columns as VxeTableGridOptions<UserGroupRow>['columns'],
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
  mobile: buildUserGroupMobileCrudConfig({
    columns,
    query: async ({ filters, keyword, page, pageSize, sortField, sortOrder }) => {
      const result = await fetchUserGroupPage({
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

const { clearSelection, reload, selectedRows } = crudApi;

function onRefresh() {
  reload();
}

async function onDelete(row: UserGroupRow) {
  await deleteUserGroup(row.id);
  message.success('已删除');
  clearSelection();
  onRefresh();
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) return;
  Modal.confirm({
    content: `确定删除选中的 ${ids.length} 个用户组吗？`,
    okText: '删除',
    okType: 'danger',
    cancelText: '取消',
    title: '批量删除',
    async onOk() {
      await batchDeleteUserGroups(ids);
      message.success('已批量删除');
      clearSelection();
      onRefresh();
    },
  });
}
</script>

<template>
  <Page auto-content-height>
    <UserGroupForm ref="userGroupFormRef" @success="onRefresh" />
    <CrudView>
      <template #toolbar-actions>
        <div v-if="selectedRows.length > 0" class="vxe-grid-batch-toolbar">
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
        </div>
        <Button
          v-else-if="groupAccess.can(USER_GROUP_PERMISSIONS.CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          新增用户组
        </Button>
      </template>
    </CrudView>
  </Page>
</template>
