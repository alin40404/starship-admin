<script lang="ts" setup>
import type {
  DictItemRow,
  DictItemSavePayload,
  DictTypeRow,
} from '#/api/system/dict';

import { computed, ref } from 'vue';

import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { usePreferences } from '@vben/preferences';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { createDictItem, updateDictItem } from '#/api/system/dict';

import { buildDictItemFormSchema } from '../data';

const emits = defineEmits<{ success: [] }>();

const { isMobile } = usePreferences();

const activeType = ref<DictTypeRow | null>(null);
const editingItemId = ref<number | null>(null);

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: buildDictItemFormSchema(),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
});

const formTitle = computed(() =>
  editingItemId.value ? '编辑字典项' : '新增字典项',
);

type PopupApi = {
  close: () => void;
  lock: () => void;
  unlock: () => void;
};

async function handleConfirm(popupApi: PopupApi) {
  if (!activeType.value) return;
  const { valid } = await formApi.validate();
  if (!valid) return;

  const values = (await formApi.getValues()) as Omit<
    DictItemSavePayload,
    'dict_type_id'
  >;
  const payload: DictItemSavePayload = {
    ...values,
    dict_type_id: activeType.value.id,
  };

  popupApi.lock();
  try {
    if (!editingItemId.value) {
      await createDictItem(payload);
      message.success('创建成功');
    } else {
      await updateDictItem(editingItemId.value, payload);
      message.success('已保存');
    }
    popupApi.close();
    emits('success');
  } finally {
    popupApi.unlock();
  }
}

function resetFormState(type: DictTypeRow, item?: DictItemRow) {
  activeType.value = type;
  formApi.resetForm();
  const id = item?.id ?? null;
  editingItemId.value = id;
  formApi.setState({ schema: buildDictItemFormSchema(!!id) });

  if (id && item) {
    formApi.setValues({
      label: item.label,
      remark: item.remark ?? '',
      sort: item.sort ?? 0,
      status: item.status,
      value: item.value,
    });
  } else {
    formApi.setValues({
      label: '',
      remark: '',
      sort: 0,
      status: 1,
      value: '',
    });
  }
}

const [Modal, modalApi] = useVbenModal({
  confirmText: '保存',
  destroyOnClose: true,
  fullscreenButton: false,
  async onConfirm() {
    await handleConfirm(modalApi);
  },
  onOpenChange(isOpen: boolean) {
    if (!isOpen) {
      editingItemId.value = null;
      activeType.value = null;
    }
  },
});

const [Drawer, drawerApi] = useVbenDrawer({
  confirmText: '保存',
  destroyOnClose: true,
  async onConfirm() {
    await handleConfirm(drawerApi);
  },
  onOpenChange(isOpen: boolean) {
    if (!isOpen) {
      editingItemId.value = null;
      activeType.value = null;
    }
  },
});

function getActivePopupApi() {
  return isMobile.value ? drawerApi : modalApi;
}

function open(type: DictTypeRow, item?: DictItemRow) {
  resetFormState(type, item);
  getActivePopupApi().open();
}

defineExpose({ open });
</script>

<template>
  <Modal
    v-if="!isMobile"
    :title="formTitle"
    class="w-full max-w-full md:w-[560px] md:max-w-[560px]"
  >
    <Form />
  </Modal>
  <Drawer v-else :title="formTitle" class="w-[560px]">
    <Form />
  </Drawer>
</template>
