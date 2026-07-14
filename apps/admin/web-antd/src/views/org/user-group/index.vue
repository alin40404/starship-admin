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
import { $t } from '#/locales';

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
    submitButtonOptions: { content: $t('common.query') },
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
  message.success($t('crud.deleted'));
  clearSelection();
  onRefresh();
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) return;
  Modal.confirm({
    content: $t('org.userGroup.batchDeleteConfirm', [ids.length]),
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchDelete'),
    async onOk() {
      await batchDeleteUserGroups(ids);
      message.success($t('crud.batchDeleted'));
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
            {{ $t('system.shared.batchDeleteWithCount', [selectedRows.length]) }}
          </Button>
          <Button class="ml-2" type="link" @click="clearSelection">
            {{ $t('system.shared.clearSelection') }}
          </Button>
        </div>
        <Button
          v-else-if="groupAccess.can(USER_GROUP_PERMISSIONS.CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          {{ $t('org.userGroup.createGroup') }}
        </Button>
      </template>
    </CrudView>
  </Page>
</template>
