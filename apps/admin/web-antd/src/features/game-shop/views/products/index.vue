<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { GameProductRow } from '#/features/game-shop/api';

import { computed, onMounted, ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import {
  deleteGameProduct,
  fetchGameOptions,
  fetchGameProductPage,
  updateGameProductStatus,
} from '#/features/game-shop/api';
import { GAME_SHOP_PERMISSIONS } from '#/features/game-shop/constants/game-shop-permissions';
import { $t } from '#/locales';
import { filterRowsForStatusChange } from '#/utils/batch-status';

function formatShelfLabel(action: '上架' | '下架', count: number) {
  return count > 0 ? `${action}（${count}）` : action;
}

import { useColumns, useGridFormSchema } from './data';
import ProductForm from './modules/form.vue';

type FormExpose = {
  open: (data?: GameProductRow | Record<string, never>) => void;
};

const formRef = ref<FormExpose>();
const gameOptions = ref<{ label: string; value: number }[]>([]);
const { hasAccessByCodes } = useAccess();
const access = { can: (code: string) => hasAccessByCodes([code]) };

async function loadGames() {
  gameOptions.value = await fetchGameOptions();
}

function onCreate() {
  formRef.value?.open({});
}

function onEdit(row: GameProductRow) {
  formRef.value?.open(row);
}

function onActionClick({ code, row }: OnActionClickParams<GameProductRow>) {
  if (code === 'edit') onEdit(row);
  else if (code === 'delete') onDelete(row);
}

const columns = useColumns(onActionClick, access);

const [CrudView, crudApi] = useResponsiveCrud<GameProductRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
  },
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: columns as VxeTableGridOptions<GameProductRow>['columns'],
    height: 'auto',
    keepSource: true,
    proxyConfig: {
      ajax: {
        query: async ({ page }, formValues) =>
          fetchGameProductPage({
            page: page.currentPage,
            per_page: page.pageSize,
            keyword: formValues?.keyword || undefined,
          }),
      },
    },
    rowConfig: { keyField: 'id' },
    toolbarConfig: { custom: true, refresh: true, search: true, zoom: true },
  } as VxeTableGridOptions<GameProductRow>,
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

async function onDelete(row: GameProductRow) {
  Modal.confirm({
    title: $t('crud.confirmDelete'),
    content: row.name,
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    async onOk() {
      await deleteGameProduct(row.id);
      message.success($t('crud.deleted'));
      clearSelection();
      onRefresh();
    },
  });
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) return;
  Modal.confirm({
    content: `确认删除选中的 ${ids.length} 个商品？`,
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchDelete'),
    async onOk() {
      await Promise.all(ids.map((id) => deleteGameProduct(id)));
      message.success($t('crud.batchDeleted'));
      clearSelection();
      onRefresh();
    },
  });
}

function onBatchUpdateStatus(status: 0 | 1) {
  const targets = filterRowsForStatusChange(selectedRows.value, status);
  const ids = targets.map((row) => row.id);
  if (ids.length === 0) return;
  const label = status === 1 ? '上架' : '下架';
  Modal.confirm({
    content: `确认将选中的 ${ids.length} 个商品设为「${label}」？`,
    okText: $t('common.confirm'),
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchSet', [label]),
    async onOk() {
      await Promise.all(ids.map((id) => updateGameProductStatus(id, status)));
      message.success($t('system.shared.batchSetDone', [label]));
      clearSelection();
      onRefresh();
    },
  });
}

onMounted(() => {
  void loadGames();
});
</script>

<template>
  <Page auto-content-height>
    <ProductForm
      ref="formRef"
      :game-options="gameOptions"
      @success="onRefresh"
    />
    <CrudView>
      <template #toolbar-actions>
        <div v-if="selectedRows.length > 0" class="vxe-grid-batch-toolbar">
          <Button
            v-if="access.can(GAME_SHOP_PERMISSIONS.PRODUCTS_DELETE)"
            danger
            @click="onBatchDelete"
          >
            {{ $t('system.shared.batchDeleteWithCount', [selectedRows.length]) }}
          </Button>
          <Button
            v-if="access.can(GAME_SHOP_PERMISSIONS.PRODUCTS_STATUS)"
            class="batch-btn-enable ml-2"
            :disabled="enableTargetRows.length === 0"
            @click="onBatchUpdateStatus(1)"
          >
            {{ formatShelfLabel('上架', enableTargetRows.length) }}
          </Button>
          <Button
            v-if="access.can(GAME_SHOP_PERMISSIONS.PRODUCTS_STATUS)"
            class="batch-btn-disable ml-2"
            :disabled="disableTargetRows.length === 0"
            @click="onBatchUpdateStatus(0)"
          >
            {{ formatShelfLabel('下架', disableTargetRows.length) }}
          </Button>
          <Button class="ml-2" type="link" @click="clearSelection">
            {{ $t('system.shared.clearSelection') }}
          </Button>
        </div>
        <Button
          v-else-if="access.can(GAME_SHOP_PERMISSIONS.PRODUCTS_CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          新增商品
        </Button>
      </template>
    </CrudView>
  </Page>
</template>
