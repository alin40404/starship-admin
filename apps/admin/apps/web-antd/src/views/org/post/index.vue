<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { PostRow } from '#/api/system/post';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page, useVbenDrawer } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  batchDeletePosts,
  batchUpdatePostStatus,
  deletePost,
  fetchPostPage,
  updatePost,
} from '#/api/system/post';
import { POST_PERMISSIONS } from '#/constants/post-permissions';

import { useColumns, useGridFormSchema } from './data';
import PostForm from './modules/form.vue';

const { hasAccessByCodes } = useAccess();

const postAccess = {
  can: (code: string) => hasAccessByCodes([code]),
  canAny: (codes: string[]) => hasAccessByCodes(codes),
};

const [PostFormDrawer, postFormDrawerApi] = useVbenDrawer({
  connectedComponent: PostForm,
  destroyOnClose: true,
});

const selectedRows = ref<PostRow[]>([]);

function syncSelection() {
  const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
  selectedRows.value = records as PostRow[];
}

function clearSelection() {
  gridApi?.grid?.clearCheckboxRow?.();
  selectedRows.value = [];
}

function onCreate() {
  postFormDrawerApi.setData({}).open();
}

function onEdit(row: PostRow) {
  postFormDrawerApi.setData(row).open();
}

function onRefresh() {
  gridApi.query();
}

function onActionClick({ code, row }: OnActionClickParams<PostRow>) {
  if (code === 'edit') onEdit(row);
  else if (code === 'delete') onDelete(row);
}

async function onPostStatusToggle(nextStatus: number, row: PostRow) {
  return new Promise<boolean>((resolve) => {
    const label = nextStatus === 1 ? '启用' : '禁用';
    Modal.confirm({
      content: `确定将「${row.name}」的状态切换为【${label}】吗？`,
      okText: '确定',
      cancelText: '取消',
      title: '切换状态',
      async onOk() {
        try {
          await updatePost(row.id, { status: nextStatus });
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

async function onDelete(row: PostRow) {
  await deletePost(row.id);
  message.success('已删除');
  clearSelection();
  gridApi.query();
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) return;
  Modal.confirm({
    content: `确定删除选中的 ${ids.length} 条岗位吗？`,
    okText: '删除',
    okType: 'danger',
    cancelText: '取消',
    title: '批量删除',
    async onOk() {
      await batchDeletePosts(ids);
      message.success('已批量删除');
      clearSelection();
      onRefresh();
    },
  });
}

function onBatchUpdateStatus(status: 0 | 1) {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) return;
  const label = status === 1 ? '启用' : '禁用';
  Modal.confirm({
    content: `确定将选中的 ${ids.length} 条岗位批量【${label}】吗？`,
    okText: '确定',
    cancelText: '取消',
    title: `批量${label}`,
    async onOk() {
      await batchUpdatePostStatus(ids, status);
      message.success(`已批量${label}`);
      clearSelection();
      onRefresh();
    },
  });
}

const [Grid, gridApi] = useVbenVxeGrid<PostRow>({
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
    columns: useColumns(onActionClick, onPostStatusToggle, postAccess),
    height: 'auto',
    keepSource: true,
    proxyConfig: {
      ajax: {
        query: async ({ page, sort }, formValues) => {
          const order = sort?.order;
          return await fetchPostPage({
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
  } as VxeTableGridOptions<PostRow>,
});
</script>

<template>
  <Page auto-content-height>
    <PostFormDrawer @success="onRefresh" />
    <Grid>
      <template #toolbar-actions>
        <template v-if="selectedRows.length > 0">
          <Button
            v-if="postAccess.can(POST_PERMISSIONS.BATCH_DELETE)"
            danger
            @click="onBatchDelete"
          >
            批量删除（{{ selectedRows.length }}）
          </Button>
          <Button
            v-if="postAccess.can(POST_PERMISSIONS.BATCH_ENABLE)"
            class="org-batch-btn-enable ml-2"
            @click="onBatchUpdateStatus(1)"
          >
            批量启用
          </Button>
          <Button
            v-if="postAccess.can(POST_PERMISSIONS.BATCH_DISABLE)"
            class="org-batch-btn-disable ml-2"
            @click="onBatchUpdateStatus(0)"
          >
            批量禁用
          </Button>
          <Button class="ml-2" type="link" @click="clearSelection">
            取消选择
          </Button>
        </template>
        <Button
          v-else-if="postAccess.can(POST_PERMISSIONS.CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          新增岗位
        </Button>
      </template>
    </Grid>
  </Page>
</template>

<style lang="scss">
.vxe-grid .org-batch-btn-enable.ant-btn {
  color: #fff;
  background-color: #52c41a;
  border-color: #52c41a;
}

.vxe-grid .org-batch-btn-disable.ant-btn {
  color: #fff;
  background-color: #fa8c16;
  border-color: #fa8c16;
}
</style>
