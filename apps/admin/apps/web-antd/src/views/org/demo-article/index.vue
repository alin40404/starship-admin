<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { DemoArticleRow } from '#/api/system/demo-article';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import {
  batchDeleteDemoArticles,

  batchUpdateDemoArticleStatus,

  deleteDemoArticle,
  fetchDemoArticlePage,
  updateDemoArticle,
} from '#/api/system/demo-article';
import { SYSTEM_DEMO_ARTICLES_PERMISSIONS } from '#/constants/demo-article-permissions';

import {
  buildDemoArticleMobileCrudConfig,
  useColumns,
  useGridFormSchema,
} from './data';
import DemoArticleForm from './modules/form.vue';

const demoArticleFormRef = ref<InstanceType<typeof DemoArticleForm>>();

const { hasAccessByCodes } = useAccess();

const demoArticleAccess = {
  can: (code: string) => hasAccessByCodes([code]),
  canAny: (codes: string[]) => hasAccessByCodes(codes),
};

function onCreate() {
  demoArticleFormRef.value?.open({});
}

function onEdit(row: DemoArticleRow) {
  demoArticleFormRef.value?.open(row);
}

function onActionClick({ code, row }: OnActionClickParams<DemoArticleRow>) {
  if (code === 'edit') {
    onEdit(row);
  } else if (code === 'delete') {
    onDelete(row);
  }
}

async function onDemoArticleStatusToggle(nextStatus: number, row: DemoArticleRow) {
  return new Promise<boolean>((resolve) => {
    const label = nextStatus === 1 ? '启用' : '禁用';
    Modal.confirm({
      content: `确定切换状态为【${label}】吗？`,
      okText: '确定',
      cancelText: '取消',
      title: '切换状态',
      async onOk() {
        try {
          await updateDemoArticle(row.id, { status: nextStatus });
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


const columns = useColumns(onActionClick, onDemoArticleStatusToggle, demoArticleAccess);

const [CrudView, crudApi] = useResponsiveCrud<DemoArticleRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: '查询' },
  },
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: columns as VxeTableGridOptions<DemoArticleRow>['columns'],
    height: 'auto',
    keepSource: true,
    proxyConfig: {
      ajax: {
        query: async ({ page, sort }, formValues) => {
          const order = sort?.order;
          return await fetchDemoArticlePage({
            page: page.currentPage,
            per_page: page.pageSize,
            title: formValues?.title || undefined,
            status: formValues?.status || undefined,
            sort_field: sort?.field,
            sort_order: order === 'desc' ? 'desc' : order === 'asc' ? 'asc' : undefined,
          });
        },
      },
    },
    rowConfig: { keyField: 'id' },
    toolbarConfig: { custom: true, refresh: true, zoom: true },
  },
  mobile: buildDemoArticleMobileCrudConfig({
    columns,
    query: async (params) => fetchDemoArticlePage(params),
  }),
});

function onDelete(row: DemoArticleRow) {
  Modal.confirm({
    content: '确定删除该记录吗？',
    okText: '删除',
    okType: 'danger',
    title: '删除确认',
    async onOk() {
      await deleteDemoArticle(row.id);
      message.success('已删除');
      await crudApi.reload();
    },
  });
}

function onFormSuccess() {
  crudApi.reload();
}
</script>

<template>
  <Page auto-content-height>
    <CrudView>
      <template #toolbar-actions>
        <Button
          v-if="demoArticleAccess.can(SYSTEM_DEMO_ARTICLES_PERMISSIONS.CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          新增演示文章
        </Button>
      </template>
    </CrudView>
    <DemoArticleForm ref="demoArticleFormRef" @success="onFormSuccess" />
  </Page>
</template>
