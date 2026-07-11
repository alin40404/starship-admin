<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { DictItemRow, DictTypeRow } from '#/api/system/dict';

import { computed, ref, useTemplateRef, watch } from 'vue';

import { useAccess } from '@vben/access';
import { useVbenDrawer } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { useElementSize } from '@vueuse/core';
import { $t } from '@vben/locales';

import { Button, message } from 'ant-design-vue';

import { useResponsiveCrud } from '#/adapter/mobile-list';
import { deleteDictItem, fetchDictItemPage } from '#/api/system/dict';
import { DICT_PERMISSIONS } from '#/constants/dict-permissions';

import { dictTypeName } from '../i18n';
import { buildDictItemMobileCrudConfig, useDictItemColumns } from '../data';
import DictItemForm from './item-form.vue';

const emits = defineEmits<{ success: [] }>();

const { hasAccessByCodes } = useAccess();
const dictAccess = {
  can: (code: string) => hasAccessByCodes([code]),
};

const activeType = ref<DictTypeRow | null>(null);
const itemFormRef = ref<InstanceType<typeof DictItemForm>>();
const gridWrapRef = useTemplateRef<HTMLElement>('gridWrapRef');
const { height: gridWrapHeight } = useElementSize(gridWrapRef);

const drawerTitle = computed(() => {
  const name = activeType.value ? dictTypeName(activeType.value) : '';
  return name
    ? $t('platform.dict.itemsTitle', [name])
    : $t('platform.dict.itemEntity');
});

function onRefresh() {
  reload();
  emits('success');
}

function onCreateItem() {
  if (!activeType.value) return;
  itemFormRef.value?.open(activeType.value);
}

function onEditItem(row: DictItemRow) {
  if (!activeType.value) return;
  itemFormRef.value?.open(activeType.value, row);
}

async function onDeleteItem(row: DictItemRow) {
  await deleteDictItem(row.id);
  message.success($t('crud.deleted'));
  onRefresh();
}

function onItemActionClick({ code, row }: OnActionClickParams<DictItemRow>) {
  if (code === 'edit') onEditItem(row);
  else if (code === 'delete') onDeleteItem(row);
}

const columns = useDictItemColumns(
  onItemActionClick,
  dictAccess,
  () => activeType.value?.code ?? '',
);

const [CrudView, crudApi] = useResponsiveCrud<DictItemRow>({
  gridOptions: {
    class: 'h-full min-h-0',
    columns: columns as VxeTableGridOptions<DictItemRow>['columns'],
    gridClass: 'p-0',
    height: 360,
    proxyConfig: {
      ajax: {
        query: async ({ page }) => {
          if (!activeType.value) {
            return { items: [], total: 0 };
          }
          return await fetchDictItemPage({
            dict_type_id: activeType.value.id,
            page: page.currentPage,
            per_page: page.pageSize,
          });
        },
      },
    },
    rowConfig: { keyField: 'id' },
    toolbarConfig: { refresh: true },
  } as VxeTableGridOptions<DictItemRow>,
  mobile: buildDictItemMobileCrudConfig({
    columns,
    query: async ({ page, pageSize }) => {
      if (!activeType.value) {
        return { data: [], total: 0 };
      }
      const result = await fetchDictItemPage({
        dict_type_id: activeType.value.id,
        page,
        per_page: pageSize,
      });
      return {
        data: result.data,
        total: result.total,
      };
    },
  }),
});

const { reload } = crudApi;

watch(
  gridWrapHeight,
  (height) => {
    crudApi.gridApi.setGridOptions({
      height: Math.max(Math.floor(height), 200),
    });
  },
  { immediate: true },
);

const [Drawer, drawerApi] = useVbenDrawer({
  onConfirm() {
    drawerApi.close();
  },
  onOpenChange(isOpen: boolean) {
    if (!isOpen) {
      activeType.value = null;
      return;
    }
    activeType.value = drawerApi.getData<DictTypeRow>();
    reload();
  },
});
</script>

<template>
  <Drawer
    :title="drawerTitle"
    class="max-w-[860px] w-[94vw]"
    content-class="flex min-h-0 flex-1 flex-col overflow-hidden p-2"
  >
    <div ref="gridWrapRef" class="min-h-0 flex-1">
      <CrudView>
        <template #toolbar-actions>
          <Button
            v-if="dictAccess.can(DICT_PERMISSIONS.CREATE)"
            type="primary"
            @click="onCreateItem"
          >
            <Plus class="size-4" />
            {{ $t('platform.dict.createItem') }}
          </Button>
        </template>
      </CrudView>
    </div>
  </Drawer>
  <DictItemForm ref="itemFormRef" @success="onRefresh" />
</template>
