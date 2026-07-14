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
    submitButtonOptions: { content: $t('common.query') },
  },
  gridOptions: {
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

const { reload } = crudApi;

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
        <Button
          v-if="memberAccess.can(MEMBER_PERMISSIONS.CREATE)"
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
