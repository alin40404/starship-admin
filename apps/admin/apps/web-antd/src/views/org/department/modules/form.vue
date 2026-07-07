<script lang="ts" setup>
import type {
  DepartmentSavePayload,
  DepartmentTreeNode,
} from '#/api/system/department';

import { computed, reactive, ref, watch } from 'vue';

import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { usePreferences } from '@vben/preferences';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import {
  createDepartment,
  fetchDepartment,
  updateDepartment,
} from '#/api/system/department';

import { buildDepartmentFormSchema, loadDepartmentTreeForParent } from '../data';

const emits = defineEmits<{ success: [] }>();

const { isMobile } = usePreferences();

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

const formTitle = computed(() =>
  editingId.value ? '编辑部门' : '新增部门',
);

type PopupApi = {
  close: () => void;
  getData: <T = Record<string, never>>() => T;
  lock: () => void;
  open: () => void;
  setData: <T>(data: T) => void;
  unlock: () => void;
};

async function handleConfirm(popupApi: PopupApi) {
  const { valid } = await formApi.validate();
  if (!valid) return;

  const values = (await formApi.getValues()) as DepartmentSavePayload;
  if (!values.parent_id) values.parent_id = 0;

  popupApi.lock();
  try {
    if (!editingId.value) {
      await createDepartment(values);
      message.success('创建成功');
    } else {
      await updateDepartment(editingId.value, values);
      message.success('已保存');
    }
    popupApi.close();
    emits('success');
  } finally {
    popupApi.unlock();
  }
}

async function handleOpenChange(isOpen: boolean, popupApi: PopupApi) {
  if (!isOpen) return;
  formApi.resetForm();

  const data = popupApi.getData<DepartmentTreeNode | { parent_id?: number }>();
  const id =
    data && typeof (data as DepartmentTreeNode).id === 'number'
      ? (data as DepartmentTreeNode).id
      : null;
  editingId.value = id;

  if (id) {
    const detail = await fetchDepartment(id);
    formApi.setValues({
      email: detail.email ?? '',
      leader_id: detail.leader_id ?? undefined,
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
      leader_id: undefined,
      name: '',
      parent_id: parentId,
      phone: '',
      remark: '',
      sort: 0,
      status: 1,
    });
  }
}

const [Modal, modalApi] = useVbenModal({
  destroyOnClose: true,
  fullscreenButton: false,
  async onConfirm() {
    await handleConfirm(modalApi as PopupApi);
  },
  async onOpenChange(isOpen: boolean) {
    await handleOpenChange(isOpen, modalApi as PopupApi);
  },
});

const [Drawer, drawerApi] = useVbenDrawer({
  destroyOnClose: true,
  async onConfirm() {
    await handleConfirm(drawerApi as PopupApi);
  },
  async onOpenChange(isOpen: boolean) {
    await handleOpenChange(isOpen, drawerApi as PopupApi);
  },
});

function getActivePopupApi() {
  return (isMobile.value ? drawerApi : modalApi) as PopupApi;
}

function open(data: DepartmentTreeNode | { parent_id?: number } = {}) {
  const api = getActivePopupApi();
  api.setData(data);
  api.open();
}

defineExpose({ open });
</script>

<template>
  <Modal
    v-if="!isMobile"
    :title="formTitle"
    class="w-full max-w-full md:w-[640px] md:max-w-[640px]"
  >
    <Form />
  </Modal>
  <Drawer v-else :title="formTitle" class="w-full">
    <Form />
  </Drawer>
</template>
