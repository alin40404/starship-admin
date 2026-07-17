<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { GameRow } from '#/features/game-shop/api';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, message, Modal } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import { deleteGame, fetchGamePage } from '#/features/game-shop/api';
import { GAME_SHOP_PERMISSIONS } from '#/features/game-shop/constants/game-shop-permissions';
import { $t } from '#/locales';

import { useColumns, useGridFormSchema } from './data';
import GameForm from './modules/form.vue';

type FormExpose = { open: (data?: GameRow | Record<string, never>) => void };

const formRef = ref<FormExpose>();
const { hasAccessByCodes } = useAccess();
const access = { can: (code: string) => hasAccessByCodes([code]) };

function onCreate() {
  formRef.value?.open({});
}

function onEdit(row: GameRow) {
  formRef.value?.open(row);
}

function onActionClick({ code, row }: OnActionClickParams<GameRow>) {
  if (code === 'edit') onEdit(row);
  else if (code === 'delete') onDelete(row);
}

async function onDelete(row: GameRow) {
  Modal.confirm({
    title: $t('crud.confirmDelete'),
    content: row.name,
    async onOk() {
      await deleteGame(row.id);
      message.success($t('crud.deleted'));
      crudApi.reload();
    },
  });
}

const columns = useColumns(onActionClick, access);

const [CrudView, crudApi] = useResponsiveCrud<GameRow>({
  formOptions: {
    commonConfig: { componentProps: { class: 'w-full' } },
    schema: useGridFormSchema(),
    submitOnChange: true,
    submitButtonOptions: { content: $t('common.query') },
  },
  gridOptions: {
    columns: columns as VxeTableGridOptions<GameRow>['columns'],
    height: 'auto',
    proxyConfig: {
      ajax: {
        query: async ({ page }, formValues) =>
          fetchGamePage({
            page: page.currentPage,
            per_page: page.pageSize,
            keyword: formValues?.keyword || undefined,
          }),
      },
    },
  },
});
</script>

<template>
  <Page auto-content-height>
    <CrudView table-title="游戏管理">
      <template #toolbar-tools>
        <Button
          v-if="access.can(GAME_SHOP_PERMISSIONS.GAMES_CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          新增游戏
        </Button>
      </template>
    </CrudView>
    <GameForm ref="formRef" @success="crudApi.reload()" />
  </Page>
</template>
