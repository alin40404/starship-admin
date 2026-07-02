<script lang="ts" setup>
import type { AreaSavePayload, AreaTreeNode } from '#/api/system/region';

import { computed, reactive, ref, watch } from 'vue';

import { useVbenModal } from '@vben/common-ui';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { createArea, fetchArea, updateArea } from '#/api/system/region';

import { buildAreaFormSchema, loadAreaTreeForParent } from '../data';

const emits = defineEmits<{ success: [] }>();

const editingId = ref<number | null>(null);

const [Form, formApi] = useVbenForm(
  reactive({
    commonConfig: {
      componentProps: { class: 'w-full' },
      labelClass: 'justify-start',
    },
    layout: 'vertical' as const,
    schema: buildAreaFormSchema({
      parentTreeLoader: () => loadAreaTreeForParent(editingId.value ?? undefined),
    }),
    showDefaultActions: false,
    wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
  }) as any,
);

watch(editingId, () => {
  formApi.setState({
    schema: buildAreaFormSchema({
      parentTreeLoader: () =>
        loadAreaTreeForParent(editingId.value ?? undefined),
    }),
  });
});

const modalTitle = computed(() =>
  editingId.value ? '编辑区域' : '新增区域',
);

const [Modal, modalApi] = useVbenModal({
  fullscreenButton: false,
  async onConfirm() {
    const { valid } = await formApi.validate();
    if (!valid) return;

    const values = (await formApi.getValues()) as AreaSavePayload;
    if (!values.pid) values.pid = 0;

    modalApi.lock();
    try {
      if (!editingId.value) {
        await createArea(values);
        message.success('创建成功');
      } else {
        await updateArea(editingId.value, values);
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

    const data = modalApi.getData<
      (AreaTreeNode & { pid?: number }) | Record<string, never>
    >();
    const id =
      data && typeof (data as AreaTreeNode).id === 'number'
        ? (data as AreaTreeNode).id
        : null;
    editingId.value = id;

    if (id) {
      const detail = await fetchArea(id);
      formApi.setValues({
        code: detail.code ?? '',
        first: detail.first ?? '',
        lat: detail.lat ?? '',
        level: detail.level ?? undefined,
        lng: detail.lng ?? '',
        mergename: detail.mergename ?? '',
        name: detail.name,
        pid: detail.pid ?? 0,
        pinyin: detail.pinyin ?? '',
        shortname: detail.shortname ?? '',
        zip: detail.zip ?? '',
      });
    } else {
      const pid = (data as { pid?: number })?.pid ?? 0;
      formApi.setValues({
        code: '',
        first: '',
        lat: '',
        level: undefined,
        lng: '',
        mergename: '',
        name: '',
        pid,
        pinyin: '',
        shortname: '',
        zip: '',
      });
    }
  },
});
</script>

<template>
  <Modal :title="modalTitle" class="w-[720px] max-w-[92vw]">
    <Form />
  </Modal>
</template>
