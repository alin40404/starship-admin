<script lang="ts" setup>
import type { AreaCascaderOption, AreaSavePayload, AreaTreeNode } from '#/api/system/region';

import { computed, defineAsyncComponent, ref } from 'vue';

import { useVbenModal } from '@vben/common-ui';

import { useDebounceFn } from '@vueuse/core';
import { message } from 'ant-design-vue';

const Cascader = defineAsyncComponent(
  () => import('ant-design-vue/es/cascader'),
);

import { useVbenForm } from '#/adapter/form';
import {
  createArea,
  fetchArea,
  fetchAreaCascaderInit,
  fetchAreaCascaderOptions,
  fetchAreaPinyinPreview,
  updateArea,
} from '#/api/system/region';

import { buildAreaFormSchema } from '../data';

const emits = defineEmits<{ success: [] }>();

const editingId = ref<number | null>(null);
const cascaderOptions = ref<AreaCascaderOption[]>([]);
const cascaderPath = ref<number[]>([]);
const cascaderReady = ref(false);

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: buildAreaFormSchema(),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
  handleValuesChange(_values, fieldsChanged) {
    if (fieldsChanged.includes('shortname')) {
      void syncPinyinPreview(String(_values.shortname ?? ''));
    }
  },
});

async function initCascader(pid = 0) {
  cascaderReady.value = false;
  try {
    const result = await fetchAreaCascaderInit({
      exclude_id: editingId.value ?? undefined,
      pid,
    });
    cascaderOptions.value = result.options;
    cascaderPath.value = pid > 0 ? result.path : [];
  } finally {
    cascaderReady.value = true;
  }
}

async function loadCascaderData(selectedOptions: AreaCascaderOption[]) {
  const target = selectedOptions[selectedOptions.length - 1];
  if (!target || target.children?.length) {
    return;
  }

  target.loading = true;
  try {
    target.children = await fetchAreaCascaderOptions({
      exclude_id: editingId.value ?? undefined,
      pid: target.value,
    });
  } finally {
    target.loading = false;
  }
}

function onCascaderChange(value: number[] | number[][]) {
  const path = (value ?? []) as number[];
  cascaderPath.value = path;
  const pid = path.length ? path[path.length - 1]! : 0;
  formApi.setFieldValue('pid', pid);
}

const syncPinyinPreview = useDebounceFn(async (shortname: string) => {
  const text = shortname.trim();
  if (!text) {
    formApi.setFieldValue('pinyin', '');
    formApi.setFieldValue('first', '');
    return;
  }

  const result = await fetchAreaPinyinPreview(text);
  formApi.setFieldValue('pinyin', result.pinyin ?? '');
  formApi.setFieldValue('first', result.first ?? '');
}, 300);

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
    cascaderOptions.value = [];
    cascaderPath.value = [];

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
      await initCascader(detail.pid ?? 0);
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
      await initCascader(pid);
    }
  },
});
</script>

<template>
  <Modal :title="modalTitle" class="w-[720px] max-w-[92vw]">
    <Form class="area-region-form">
      <template #pid>
        <div class="area-parent-cascader-field w-full min-w-0">
          <Cascader
            v-if="cascaderReady"
            v-model:value="cascaderPath"
            allow-clear
            change-on-select
            class="area-parent-cascader w-full"
            :load-data="loadCascaderData"
            :options="cascaderOptions"
            placeholder="请选择上级区域（留空为顶级）"
            popup-class-name="area-parent-cascader-popup"
            style="width: 100%"
            @change="onCascaderChange"
          />
          <div
            v-else
            class="border-input bg-background h-8 w-full animate-pulse rounded-md border"
          />
        </div>
      </template>
    </Form>
  </Modal>
</template>

<style scoped>
.area-region-form :deep(.grid > .col-span-full) {
  width: 100%;
}

.area-region-form :deep(.col-span-full > .w-full.flex-auto) {
  width: 100%;
  min-width: 0;
}

.area-region-form :deep(.area-parent-cascader-field) {
  display: block;
  width: 100%;
  min-width: 0;
}

.area-region-form :deep(.area-parent-cascader.ant-select),
.area-region-form :deep(.area-parent-cascader-field .ant-select) {
  display: block;
  width: 100% !important;
  max-width: 100%;
}

.area-region-form :deep(.area-parent-cascader-field .ant-select-selector) {
  width: 100% !important;
}
</style>

<style>
.area-parent-cascader-popup .ant-cascader-menu {
  min-height: 280px;
  max-height: 400px;
}
</style>
