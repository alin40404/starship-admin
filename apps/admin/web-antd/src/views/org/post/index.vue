<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { PostRow } from '#/api/system/post';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import {
  batchDeletePosts,
  batchUpdatePostStatus,
  deletePost,
  fetchPostPage,
  updatePost,
} from '#/api/system/post';
import { POST_PERMISSIONS } from '#/constants/post-permissions';
import { $t } from '#/locales';

import {
  buildPostMobileCrudConfig,
  useColumns,
  useGridFormSchema,
} from './data';
import PostForm from './modules/form.vue';

const postFormRef = ref<InstanceType<typeof PostForm>>();

const { hasAccessByCodes } = useAccess();

const postAccess = {
  can: (code: string) => hasAccessByCodes([code]),
  canAny: (codes: string[]) => hasAccessByCodes(codes),
};

function onCreate() {
  postFormRef.value?.open({});
}

function onEdit(row: PostRow) {
  postFormRef.value?.open(row);
}

function onActionClick({ code, row }: OnActionClickParams<PostRow>) {
  if (code === 'edit') {
    onEdit(row);
  } else if (code === 'delete') {
    onDelete(row);
  }
}

async function onPostStatusToggle(nextStatus: number, row: PostRow) {
  return new Promise<boolean>((resolve) => {
    const label =
      nextStatus === 1
        ? $t('system.shared.status.enable')
        : $t('system.shared.status.disable');
    Modal.confirm({
      content: $t('system.shared.toggleStatusConfirm', [row.name, label]),
      okText: $t('common.confirm'),
      cancelText: $t('common.cancel'),
      title: $t('system.shared.toggleStatusTitle'),
      async onOk() {
        try {
          await updatePost(row.id, { status: nextStatus });
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

const columns = useColumns(onActionClick, onPostStatusToggle, postAccess);

const [CrudView, crudApi] = useResponsiveCrud<PostRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: $t('common.query') },
  },
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: columns as VxeTableGridOptions<PostRow>['columns'],
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
  mobile: buildPostMobileCrudConfig({
    columns,
    query: async ({ filters, keyword, page, pageSize, sortField, sortOrder }) => {
      const result = await fetchPostPage({
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

async function onDelete(row: PostRow) {
  await deletePost(row.id);
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
    content: $t('org.post.batchDeleteConfirm', [ids.length]),
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchDelete'),
    async onOk() {
      await batchDeletePosts(ids);
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
    content: $t('org.post.batchStatusConfirm', [ids.length, label]),
    okText: $t('common.confirm'),
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchSet', [label]),
    async onOk() {
      await batchUpdatePostStatus(ids, status);
      message.success($t('system.shared.batchStatusDone', [label]));
      clearSelection();
      onRefresh();
    },
  });
}
</script>

<template>
  <Page auto-content-height>
    <PostForm ref="postFormRef" @success="onRefresh" />
    <CrudView>
      <template #toolbar-actions>
        <div
          v-if="selectedRows.length > 0"
          class="vxe-grid-batch-toolbar"
        >
          <Button
            v-if="postAccess.can(POST_PERMISSIONS.BATCH_DELETE)"
            danger
            @click="onBatchDelete"
          >
            {{ $t('system.shared.batchDeleteWithCount', [selectedRows.length]) }}
          </Button>
          <Button
            v-if="postAccess.can(POST_PERMISSIONS.BATCH_STATUS)"
            class="org-batch-btn-enable ml-2"
            @click="onBatchUpdateStatus(1)"
          >
            {{ $t('system.shared.batchEnable') }}
          </Button>
          <Button
            v-if="postAccess.can(POST_PERMISSIONS.BATCH_STATUS)"
            class="org-batch-btn-disable ml-2"
            @click="onBatchUpdateStatus(0)"
          >
            {{ $t('system.shared.batchDisable') }}
          </Button>
          <Button class="ml-2" type="link" @click="clearSelection">
            {{ $t('system.shared.clearSelection') }}
          </Button>
        </div>
        <Button
          v-else-if="postAccess.can(POST_PERMISSIONS.CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          {{ $t('org.post.createPost') }}
        </Button>
      </template>
    </CrudView>
  </Page>
</template>

<style lang="scss">
.vxe-grid .org-batch-btn-enable.ant-btn,
.mobile-crud-list .org-batch-btn-enable.ant-btn {
  color: #fff;
  background-color: #52c41a;
  border-color: #52c41a;
}

.vxe-grid .org-batch-btn-disable.ant-btn,
.mobile-crud-list .org-batch-btn-disable.ant-btn {
  color: #fff;
  background-color: #fa8c16;
  border-color: #fa8c16;
}
</style>
