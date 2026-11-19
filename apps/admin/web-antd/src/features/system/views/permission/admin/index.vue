<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { AdminRow } from '#/features/system/api/admin';

import { computed } from 'vue';

import { useAccess } from '@vben/access';
import { Page, useVbenDrawer } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { $t } from '@vben/locales';

import { Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import {
  batchDeleteAdmins,
  batchUpdateAdminStatus,
  deleteAdmin,
  fetchAdminPage,
  updateAdminStatus,
} from '#/features/system/api/admin';
import { ADMIN_PERMISSIONS } from '#/features/system/constants/admin-permissions';
import { filterRowsForStatusChange } from '#/utils/batch-status';

import {
  buildAdminMobileCrudConfig,
  useColumns,
  useGridFormSchema,
} from './data';
import AdminForm from './modules/form.vue';

const { hasAccessByCodes } = useAccess();

const adminAccess = {
  can: (code: string) => hasAccessByCodes([code]),
};

const [AdminFormDrawer, adminFormDrawerApi] = useVbenDrawer({
  connectedComponent: AdminForm,
});

function onCreate() {
  adminFormDrawerApi.setData({}).open();
}

function onEdit(row: AdminRow) {
  adminFormDrawerApi.setData(row).open();
}

function onActionClick({ code, row }: OnActionClickParams<AdminRow>) {
  if (code === 'edit') {
    onEdit(row);
  } else if (code === 'delete') {
    onDelete(row);
  }
}

async function onAdminStatusToggle(nextStatus: number, row: AdminRow) {
  if (row.is_super === 1) {
    return false;
  }
  return new Promise<boolean>((resolve) => {
    const label =
      nextStatus === 1
        ? $t('system.shared.status.enable')
        : $t('system.shared.status.disable');
    Modal.confirm({
      content: $t('system.shared.toggleStatusConfirm', [
        row.username,
        label,
      ]),
      okText: $t('common.confirm'),
      cancelText: $t('common.cancel'),
      title: $t('system.shared.toggleStatusTitle'),
      async onOk() {
        try {
          await updateAdminStatus(row.id, nextStatus as 0 | 1);
          message.success($t('crud.statusUpdated'));
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

const columns = useColumns(onActionClick, onAdminStatusToggle, adminAccess);

const [CrudView, crudApi] = useResponsiveCrud<AdminRow>({
  formOptions: {
    commonConfig: {
      componentProps: {
        class: 'w-full',
      },
    },
    fieldMappingTime: [
      [
        'updatedTime',
        ['updated_start', 'updated_end'],
        ['YYYY-MM-DD', 'YYYY-MM-DD'],
      ],
    ],
    schema: useGridFormSchema(),
    submitOnChange: true,
  },
  gridOptions: {
    checkboxConfig: {
      checkMethod: ({ row }) => (row as AdminRow).is_super !== 1,
      highlight: true,
    },
    columns: columns as VxeTableGridOptions<AdminRow>['columns'],
    height: 'auto',
    keepSource: true,
    proxyConfig: {
      ajax: {
        query: async ({ page, sort }, formValues) => {
          const order = sort?.order;
          return await fetchAdminPage({
            page: page.currentPage,
            per_page: page.pageSize,
            sort_field: sort?.field,
            sort_order:
              order === 'asc' || order === 'desc' ? order : undefined,
            dept_id: formValues?.dept_id,
            email: formValues?.email || undefined,
            mobile: formValues?.mobile || undefined,
            name: formValues?.name || undefined,
            updated_end: formValues?.updated_end || undefined,
            updated_start: formValues?.updated_start || undefined,
            username: formValues?.username || undefined,
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
  } as VxeTableGridOptions<AdminRow>,
  mobile: buildAdminMobileCrudConfig({
    columns,
    query: async ({ filters, keyword, page, pageSize, sortField, sortOrder }) => {
      const result = await fetchAdminPage({
        page,
        per_page: pageSize,
        sort_field: sortField,
        sort_order: sortOrder,
        dept_id: filters?.dept_id,
        email: filters?.email || undefined,
        keyword: keyword || undefined,
        mobile: filters?.mobile || undefined,
        name: filters?.name || undefined,
        updated_end: filters?.updated_end || undefined,
        updated_start: filters?.updated_start || undefined,
        username: filters?.username || undefined,
      });
      return {
        data: result.data,
        total: result.total,
      };
    },
  }),
});

const { clearSelection, reload, selectedRows } = crudApi;

const enableTargetRows = computed(() =>
  filterRowsForStatusChange(selectedRows.value, 1),
);
const disableTargetRows = computed(() =>
  filterRowsForStatusChange(selectedRows.value, 0),
);

function onRefresh() {
  reload();
}

async function onDelete(row: AdminRow) {
  await deleteAdmin(row.id);
  message.success($t('crud.deleted'));
  clearSelection();
  onRefresh();
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) {
    return;
  }
  Modal.confirm({
    content: $t('permission.admin.batchDeleteConfirm', [ids.length]),
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchDelete'),
    async onOk() {
      await batchDeleteAdmins(ids);
      message.success($t('crud.batchDeleted'));
      clearSelection();
      onRefresh();
    },
  });
}

function onBatchUpdateStatus(status: 0 | 1) {
  const targets = filterRowsForStatusChange(selectedRows.value, status);
  const ids = targets.map((row) => row.id);
  if (ids.length === 0) {
    return;
  }
  const label =
    status === 1
      ? $t('system.shared.status.enable')
      : $t('system.shared.status.disable');
  Modal.confirm({
    content: $t('permission.admin.batchStatusConfirm', [
      ids.length,
      label,
    ]),
    okText: $t('common.confirm'),
    cancelText: $t('common.cancel'),
    title:
      status === 1
        ? $t('system.shared.batchEnable')
        : $t('system.shared.batchDisable'),
    async onOk() {
      await batchUpdateAdminStatus(ids, status);
      message.success($t('system.shared.batchStatusDone', [label]));
      clearSelection();
      onRefresh();
    },
  });
}
</script>

<template>
  <Page auto-content-height>
    <AdminFormDrawer class="w-[560px]" @success="onRefresh" />
    <CrudView>
      <template #toolbar-actions>
        <div
          v-if="selectedRows.length > 0"
          class="vxe-grid-batch-toolbar"
        >
          <Button
            v-if="adminAccess.can(ADMIN_PERMISSIONS.BATCH_DELETE)"
            danger
            @click="onBatchDelete"
          >
            {{ $t('system.shared.batchDeleteWithCount', [selectedRows.length]) }}
          </Button>
          <Button
            v-if="adminAccess.can(ADMIN_PERMISSIONS.BATCH_STATUS)"
            class="batch-btn-enable ml-2"
            :disabled="enableTargetRows.length === 0"
            @click="onBatchUpdateStatus(1)"
          >
            {{ $t('system.shared.batchEnableWithCount', [enableTargetRows.length]) }}
          </Button>
          <Button
            v-if="adminAccess.can(ADMIN_PERMISSIONS.BATCH_STATUS)"
            class="batch-btn-disable ml-2"
            :disabled="disableTargetRows.length === 0"
            @click="onBatchUpdateStatus(0)"
          >
            {{ $t('system.shared.batchDisableWithCount', [disableTargetRows.length]) }}
          </Button>
          <Button class="ml-2" type="link" @click="clearSelection">
            {{ $t('system.shared.clearSelection') }}
          </Button>
        </div>
        <Button
          v-else-if="adminAccess.can(ADMIN_PERMISSIONS.CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          {{ $t('permission.admin.createAdmin') }}
        </Button>
      </template>
    </CrudView>
  </Page>
</template>

