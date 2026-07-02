<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type {
  DictItemRow,
  DictItemSavePayload,
  DictTypeRow,
} from '#/api/system/dict';

import { computed, ref } from 'vue';

import { useVbenDrawer } from '@vben/common-ui';

import { Button, message, Modal } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  createDictItem,
  deleteDictItem,
  fetchDictItemPage,
  updateDictItem,
} from '#/api/system/dict';

import { buildDictItemFormSchema, useDictItemColumns } from '../data';

const emits = defineEmits<{ success: [] }>();

const activeType = ref<DictTypeRow | null>(null);
const itemModalOpen = ref(false);
const editingItemId = ref<number | null>(null);

const drawerTitle = computed(() => {
  const name = activeType.value?.name ?? '';
  return name ? `字典项：${name}` : '字典项';
});

const [ItemForm, itemFormApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: buildDictItemFormSchema(),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
});

function onRefresh() {
  gridApi.query();
  emits('success');
}

function onCreateItem() {
  editingItemId.value = null;
  itemFormApi.resetForm();
  itemFormApi.setValues({
    label: '',
    remark: '',
    sort: 0,
    status: 1,
    value: '',
  });
  itemModalOpen.value = true;
}

function onEditItem(row: DictItemRow) {
  editingItemId.value = row.id;
  itemFormApi.setValues({
    label: row.label,
    remark: row.remark ?? '',
    sort: row.sort ?? 0,
    status: row.status,
    value: row.value,
  });
  itemModalOpen.value = true;
}

async function onDeleteItem(row: DictItemRow) {
  await deleteDictItem(row.id);
  message.success('已删除');
  onRefresh();
}

function onItemActionClick({ code, row }: OnActionClickParams<DictItemRow>) {
  if (code === 'edit') onEditItem(row);
  else if (code === 'delete') onDeleteItem(row);
}

async function submitItemForm() {
  if (!activeType.value) return;
  const { valid } = await itemFormApi.validate();
  if (!valid) return;

  const values = (await itemFormApi.getValues()) as Omit<
    DictItemSavePayload,
    'dict_type_id'
  >;
  const payload: DictItemSavePayload = {
    ...values,
    dict_type_id: activeType.value.id,
  };

  if (!editingItemId.value) {
    await createDictItem(payload);
    message.success('创建成功');
  } else {
    await updateDictItem(editingItemId.value, payload);
    message.success('已保存');
  }
  itemModalOpen.value = false;
  onRefresh();
}

const [Grid, gridApi] = useVbenVxeGrid<DictItemRow>({
  gridOptions: {
    columns: useDictItemColumns(onItemActionClick),
    height: 420,
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
});

const [Drawer, drawerApi] = useVbenDrawer({
  onConfirm() {
    drawerApi.close();
  },
  onOpenChange(isOpen: boolean) {
    if (!isOpen) {
      activeType.value = null;
      return;
    }
    const data = drawerApi.getData<DictTypeRow>();
    activeType.value = data;
    gridApi.query();
  },
});
</script>

<template>
  <Drawer :title="drawerTitle" class="max-w-[860px] w-[94vw]">
    <div class="mb-3 flex justify-end">
      <Button type="primary" @click="onCreateItem">新增字典项</Button>
    </div>
    <Grid />
  </Drawer>

  <Modal
    v-model:open="itemModalOpen"
    :title="editingItemId ? '编辑字典项' : '新增字典项'"
    ok-text="保存"
    cancel-text="取消"
    @ok="submitItemForm"
  >
    <ItemForm />
  </Modal>
</template>
