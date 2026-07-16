<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { ArticleRow } from '#/features/cms/api';

import { onMounted, ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import {
  deleteArticle,
  fetchArticlePage,
  fetchCmsCategoryOptions,
  updateArticleStatus,
} from '#/features/cms/api';
import { CMS_PERMISSIONS } from '#/features/cms/constants/cms-permissions';
import { $t } from '#/locales';

import { useColumns, useGridFormSchema } from './data';
import ArticleForm from './modules/form.vue';

type ArticleFormExpose = {
  open: (data?: ArticleRow | Record<string, never>) => void;
};

const articleFormRef = ref<ArticleFormExpose>();
const categoryOptions = ref<{ label: string; value: number }[]>([]);
const gridFormSchema = ref(useGridFormSchema([]));
const { hasAccessByCodes } = useAccess();
const access = { can: (code: string) => hasAccessByCodes([code]) };

function onCreate() {
  articleFormRef.value?.open({});
}

function onEdit(row: ArticleRow) {
  articleFormRef.value?.open(row);
}

function onActionClick({ code, row }: OnActionClickParams<ArticleRow>) {
  if (code === 'edit') onEdit(row);
  else if (code === 'delete') onDelete(row);
}

async function onStatusBeforeChange(newVal: number, row: ArticleRow) {
  try {
    await updateArticleStatus(row.id, newVal);
    message.success($t('crud.saved'));
    return true;
  } catch {
    return false;
  }
}

const columns = useColumns(onActionClick, access, onStatusBeforeChange);

const [CrudView, crudApi] = useResponsiveCrud<ArticleRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: gridFormSchema.value,
    submitOnChange: true,
    submitButtonOptions: { content: $t('common.query') },
  },
  gridOptions: {
    columns: columns as VxeTableGridOptions<ArticleRow>['columns'],
    height: 'auto',
    proxyConfig: {
      ajax: {
        query: async ({ page }, formValues) =>
          fetchArticlePage({
            page: page.currentPage,
            per_page: page.pageSize,
            keyword: formValues?.keyword || undefined,
            category_id: formValues?.category_id || undefined,
            status:
              formValues?.status === undefined || formValues?.status === null
                ? undefined
                : formValues.status,
          }),
      },
    },
    rowConfig: { keyField: 'id' },
    toolbarConfig: { custom: true, refresh: true, search: true, zoom: true },
  } as VxeTableGridOptions<ArticleRow>,
});

const { reload } = crudApi;

function onRefresh() {
  reload();
}

function onDelete(row: ArticleRow) {
  Modal.confirm({
    content: `确认删除文章「${row.title}」？`,
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('common.prompt'),
    async onOk() {
      await deleteArticle(row.id);
      message.success($t('crud.deleted'));
      onRefresh();
    },
  });
}

onMounted(async () => {
  categoryOptions.value = await fetchCmsCategoryOptions();
  gridFormSchema.value = useGridFormSchema(categoryOptions.value);
});
</script>

<template>
  <Page auto-content-height>
    <ArticleForm ref="articleFormRef" @success="onRefresh" />
    <CrudView>
      <template #toolbar-actions>
        <Button
          v-if="access.can(CMS_PERMISSIONS.ARTICLES_CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          新增文章
        </Button>
      </template>
    </CrudView>
  </Page>
</template>
