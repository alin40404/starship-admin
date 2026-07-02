<script lang="ts" setup>
import type {
  DepartmentSavePayload,
  DepartmentTreeNode,
} from '#/api/system/department';

import { computed, reactive, ref, watch } from 'vue';

import { useVbenModal } from '@vben/common-ui';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import {
  createDepartment,
  fetchDepartment,
  updateDepartment,
} from '#/api/system/department';

import { buildDepartmentFormSchema, loadDepartmentTreeForParent } from '../data';

const emits = defineEmits<{ success: [] }>();

const editingId = ref<number | null>(null);

const [Form, formApi] = useVbenForm(
  reactive({
    commonConfig: {
      componentProps: { class: 'w-full' },
      labelClass: 'justify-start',
    },
    layout: 'vertical' as const,
    schema: buildDepartmentFormSchema({
      parentTreeLoader: () => loadDepartmentTreeForParent(editingId.value ?? undefined),
    }),
    showDefaultActions: false,
    wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
  }) as any,
);

watch(editingId, () => {
  formApi.setState({
    schema: buildDepartmentFormSchema({
      parentTreeLoader: () =>
        loadDepartmentTreeForParent(editingId.value ?? undefined),
    }),
  });
});

const modalTitle = computed(() =>
  editingId.value ? '编辑部门' : '新增部门',
);

const [Modal, modalApi] = useVbenModal({
  fullscreenButton: false,
  async onConfirm() {
    const { valid } = await formApi.validate();
    if (!valid) return;

    const values = (await formApi.getValues()) as DepartmentSavePayload;
    if (!values.parent_id) values.parent_id = 0;

    modalApi.lock();
    try {
      if (!editingId.value) {
        await createDepartment(values);
        message.success('创建成功');
      } else {
        await updateDepartment(editingId.value, values);
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
      (DepartmentTreeNode & { parent_id?: number }) | Record<string, never>
    >();
    const id =
      data && typeof (data as DepartmentTreeNode).id === 'number'
        ? (data as DepartmentTreeNode).id
        : null;
    editingId.value = id;

    if (id) {
      const detail = await fetchDepartment(id);
      formApi.setValues({
        email: detail.email ?? '',
        leader: detail.leader ?? '',
        name: detail.name,
        parent_id: detail.parent_id ?? 0,
        phone: detail.phone ?? '',
        remark: detail.remark ?? '',
        sort: detail.sort ?? 0,
        status: detail.status,
      });
    } else {
      const parentId = (data as { parent_id?: number })?.parent_id ?? 0;
      formApi.setValues({
        email: '',
        leader: '',
        name: '',
        parent_id: parentId,
        phone: '',
        remark: '',
        sort: 0,
        status: 1,
      });
    }
  },
});
</script>

<template>
  <Modal :title="modalTitle" class="w-[640px] max-w-[92vw]">
    <Form />
  </Modal>
</template>
