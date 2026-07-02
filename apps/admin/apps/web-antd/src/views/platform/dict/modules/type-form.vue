<script lang="ts" setup>
import type { DictTypeRow, DictTypeSavePayload } from '#/api/system/dict';

import { computed, ref } from 'vue';

import { useVbenModal } from '@vben/common-ui';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { createDictType, updateDictType } from '#/api/system/dict';

import { buildDictTypeFormSchema } from '../data';

const emits = defineEmits<{ success: [] }>();

const editingId = ref<number | null>(null);

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
  },
  schema: buildDictTypeFormSchema(),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1',
});

const modalTitle = computed(() =>
  editingId.value ? '编辑字典' : '新增字典',
);

const [Modal, modalApi] = useVbenModal({
  fullscreenButton: false,
  async onConfirm() {
    const { valid } = await formApi.validate();
    if (!valid) return;

    const values = (await formApi.getValues()) as DictTypeSavePayload;
    modalApi.lock();
    try {
      if (!editingId.value) {
        await createDictType(values);
        message.success('创建成功');
      } else {
        await updateDictType(editingId.value, values);
        message.success('已保存');
      }
      modalApi.close();
      emits('success');
    } finally {
      modalApi.unlock();
    }
  },
  async onOpenChange(isOpen: boolean) {
    if (!isOpen) return;
    formApi.resetForm();
    const data = modalApi.getData<DictTypeRow | Record<string, never>>();
    const id =
      data && typeof (data as DictTypeRow).id === 'number'
        ? (data as DictTypeRow).id
        : null;
    editingId.value = id;

    if (id) {
      formApi.setValues({
        code: (data as DictTypeRow).code,
        name: (data as DictTypeRow).name,
        remark: (data as DictTypeRow).remark ?? '',
        status: (data as DictTypeRow).status,
      });
    } else {
      formApi.setValues({
        code: '',
        name: '',
        remark: '',
        status: 1,
      });
    }
  },
});
</script>

<template>
  <Modal :title="modalTitle" class="w-[560px] max-w-[92vw]">
    <div :style="{ paddingTop: 'calc(var(--spacing) * 4)' }">
      <Form />
    </div>
  </Modal>
</template>
