<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { MemberRow } from '#/features/member/api';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import {
  batchDeleteMembers,
  batchUpdateMemberStatus,
  deleteMember,
  fetchMemberPage,
  updateMemberStatus,
} from '#/features/member/api';
import { MEMBER_PERMISSIONS } from '#/features/member/constants/member-permissions';
import { $t } from '#/locales';

import {
  buildMemberMobileCrudConfig,
  useColumns,
  useGridFormSchema,
} from './data';
import MemberForm from './modules/form.vue';

type MemberFormExpose = {
  open: (data?: MemberRow | Record<string, never>) => void;
};

const memberFormRef = ref<MemberFormExpose>();
const { hasAccessByCodes } = useAccess();

const memberAccess = {
  can: (code: string) => hasAccessByCodes([code]),
};

function onCreate() {
  memberFormRef.value?.open({});
}

function onEdit(row: MemberRow) {
  memberFormRef.value?.open(row);
}

function onActionClick({ code, row }: OnActionClickParams<MemberRow>) {
  if (code === 'edit') onEdit(row);
  else if (code === 'delete') onDelete(row);
}

async function onStatusBeforeChange(newVal: number, row: MemberRow) {
  try {
    await updateMemberStatus(row.id, newVal);
    message.success($t('crud.saved'));
    return true;
  } catch {
    return false;
  }
}

const columns = useColumns(onActionClick, memberAccess, onStatusBeforeChange);

const [CrudView, crudApi] = useResponsiveCrud<MemberRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
  },
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: columns as VxeTableGridOptions<MemberRow>['columns'],
    height: 'auto',
    keepSource: true,
    proxyConfig: {
      ajax: {
        query: async ({ page, sort }, formValues) => {
          const order = sort?.order;
          return await fetchMemberPage({
            page: page.currentPage,
            per_page: page.pageSize,
            username: formValues?.username || undefined,
            mobile: formValues?.mobile || undefined,
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
  } as VxeTableGridOptions<MemberRow>,
  mobile: buildMemberMobileCrudConfig({
    columns,
    query: async ({ filters, keyword, page, pageSize, sortField, sortOrder }) => {
      const result = await fetchMemberPage({
        page,
        per_page: pageSize,
        sort_field: sortField,
        sort_order: sortOrder,
        keyword: keyword || undefined,
        username: filters?.username || undefined,
        mobile: filters?.mobile || undefined,
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

async function onDelete(row: MemberRow) {
  Modal.confirm({
    content: `确认删除会员「${row.username}」？`,
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('common.prompt'),
    async onOk() {
      await deleteMember(row.id);
      message.success($t('crud.deleted'));
      clearSelection();
      onRefresh();
    },
  });
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) {
    return;
  }
  Modal.confirm({
    content: `确认批量删除选中的 ${ids.length} 个会员？`,
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchDelete'),
    async onOk() {
      await batchDeleteMembers(ids);
      message.success($t('crud.batchDeleted'));
      clearSelection();
      onRefresh();
    },
  });
}

function onBatchUpdateStatus(status: 0 | 1) {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) {
    return;
  }
  const label =
    status === 1
      ? $t('system.shared.status.enable')
      : $t('system.shared.status.disable');
  Modal.confirm({
    content: `确认将选中的 ${ids.length} 个会员设为「${label}」？`,
    okText: $t('common.confirm'),
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchSet', [label]),
    async onOk() {
      await batchUpdateMemberStatus(ids, status);
      message.success($t('system.shared.batchStatusDone', [label]));
      clearSelection();
      onRefresh();
    },
  });
}
</script>

<template>
  <Page auto-content-height>
    <MemberForm ref="memberFormRef" @success="onRefresh" />
    <CrudView>
      <template #toolbar-actions>
        <div
          v-if="selectedRows.length > 0"
          class="vxe-grid-batch-toolbar"
        >
          <Button
            v-if="memberAccess.can(MEMBER_PERMISSIONS.BATCH_DELETE)"
            danger
            @click="onBatchDelete"
          >
            {{ $t('system.shared.batchDeleteWithCount', [selectedRows.length]) }}
          </Button>
          <Button
            v-if="memberAccess.can(MEMBER_PERMISSIONS.BATCH_STATUS)"
            class="member-batch-btn-enable ml-2"
            @click="onBatchUpdateStatus(1)"
          >
            {{ $t('system.shared.batchEnable') }}
          </Button>
          <Button
            v-if="memberAccess.can(MEMBER_PERMISSIONS.BATCH_STATUS)"
            class="member-batch-btn-disable ml-2"
            @click="onBatchUpdateStatus(0)"
          >
            {{ $t('system.shared.batchDisable') }}
          </Button>
          <Button class="ml-2" type="link" @click="clearSelection">
            {{ $t('system.shared.clearSelection') }}
          </Button>
        </div>
        <Button
          v-else-if="memberAccess.can(MEMBER_PERMISSIONS.CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          新增会员
        </Button>
      </template>
    </CrudView>
  </Page>
</template>

<style lang="scss">
.vxe-grid .member-batch-btn-enable.ant-btn,
.mobile-crud-list .member-batch-btn-enable.ant-btn {
  color: #fff;
  background-color: #52c41a;
  border-color: #52c41a;
}

.vxe-grid .member-batch-btn-disable.ant-btn,
.mobile-crud-list .member-batch-btn-disable.ant-btn {
  color: #fff;
  background-color: #fa8c16;
  border-color: #fa8c16;
}
</style>
