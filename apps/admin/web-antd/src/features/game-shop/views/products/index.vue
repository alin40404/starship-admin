<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { GameProductRow } from '#/features/game-shop/api';

import { onMounted, ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import {
  deleteGameProduct,
  fetchGameOptions,
  fetchGameProductPage,
} from '#/features/game-shop/api';
import { GAME_SHOP_PERMISSIONS } from '#/features/game-shop/constants/game-shop-permissions';
import { $t } from '#/locales';

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

async function onDelete(row: GameProductRow) {
  Modal.confirm({
    title: $t('crud.confirmDelete'),
    content: row.name,
    async onOk() {
      await deleteGameProduct(row.id);
      message.success($t('crud.deleted'));
      crudApi.reload();
    },
  });
}

const columns = useColumns(onActionClick, access);

const [CrudView, crudApi] = useResponsiveCrud<GameProductRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: $t('common.query') },
  },
  gridOptions: {
    columns: columns as VxeTableGridOptions<GameProductRow>['columns'],
    height: 'auto',
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
  },
});

onMounted(() => {
  void loadGames();
});
</script>

<template>
  <Page auto-content-height>
    <CrudView table-title="游戏商品">
      <template #toolbar-tools>
        <Button
          v-if="access.can(GAME_SHOP_PERMISSIONS.PRODUCTS_CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          新增商品
        </Button>
      </template>
    </CrudView>
    <ProductForm
      ref="formRef"
      :game-options="gameOptions"
      @success="crudApi.reload()"
    />
  </Page>
</template>
