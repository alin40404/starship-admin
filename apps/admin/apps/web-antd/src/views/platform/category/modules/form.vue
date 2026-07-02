<script lang="ts" setup>
import type { CategoryRow, CategorySavePayload } from '#/api/system/category';

import { computed, ref } from 'vue';

import { useVbenModal } from '@vben/common-ui';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { createCategory, updateCategory } from '#/api/system/category';

import { buildFormSchema } from '../data';

const emits = defineEmits<{ success: [] }>();

const editingId = ref<number | null>(null);

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: buildFormSchema(),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
});

const modalTitle = computed(() =>
  editingId.value ? '编辑分类' : '新增分类',
);

const [Modal, modalApi] = useVbenModal({
  fullscreenButton: false,
  async onConfirm() {
    const { valid } = await formApi.validate();
    if (!valid) return;

    const values = (await formApi.getValues()) as CategorySavePayload;
    modalApi.lock();
    try {
      if (!editingId.value) {
        await createCategory(values);
        message.success('创建成功');
      } else {
        await updateCategory(editingId.value, values);
        message.success('已保存');
      }
      modalApi.close();
      emits('success');
    } finally {
      modalApi.unlock();
    }
  },
  onOpenChange(isOpen: boolean) {
    if (!isOpen) {
      editingId.value = null;
      return;
    }

    formApi.resetForm();
    const data = modalApi.getData<CategoryRow | Record<string, never>>();
    const id =
      data && typeof (data as CategoryRow).id === 'number'
        ? (data as CategoryRow).id
        : null;
    editingId.value = id;
    formApi.setState({ schema: buildFormSchema(!!id) });

    if (id) {
      const row = data as CategoryRow;
      formApi.setValues({
        label: row.label,
        remark: row.remark ?? '',
        sort: row.sort ?? 0,
        status: row.status,
        value: row.value,
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
  },
});
</script>

<template>
  <Modal :title="modalTitle" class="w-[560px] max-w-[92vw]">
    <Form />
  </Modal>
</template>
