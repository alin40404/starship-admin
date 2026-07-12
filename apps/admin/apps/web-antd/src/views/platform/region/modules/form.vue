<script lang="ts" setup>
import type { AreaCascaderOption, AreaSavePayload, AreaTreeNode } from '#/api/system/region';

import { computed, defineAsyncComponent, ref } from 'vue';

import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { $t } from '@vben/locales';
import { usePreferences } from '@vben/preferences';

import { useDebounceFn } from '@vueuse/core';
import { message } from 'ant-design-vue';

const Cascader = defineAsyncComponent(
  () => import('ant-design-vue/es/cascader'),
);

import { useVbenForm } from '#/adapter/form';
import {
  createArea,
  fetchArea,
  fetchAreaOptionsCascaderInit,
  fetchAreaOptionsCascaderOptions,
  fetchAreaPinyinPreview,
  updateArea,
} from '#/api/system/region';

import { buildAreaFormSchema } from '../data';

const emits = defineEmits<{ success: [] }>();

const { isMobile } = usePreferences();

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
    const result = await fetchAreaOptionsCascaderInit({
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
    target.children = await fetchAreaOptionsCascaderOptions({
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

const formTitle = computed(() =>
  editingId.value
    ? $t('platform.region.editRegion')
    : $t('platform.region.createRegionTitle'),
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

  const values = (await formApi.getValues()) as AreaSavePayload;
  if (!values.pid) values.pid = 0;

  popupApi.lock();
  try {
    if (!editingId.value) {
      await createArea(values);
      message.success($t('crud.created'));
    } else {
      await updateArea(editingId.value, values);
      message.success($t('crud.saved'));
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
  cascaderOptions.value = [];
  cascaderPath.value = [];

  const data = popupApi.getData<AreaTreeNode | { pid?: number }>();
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

function open(data: AreaTreeNode | { pid?: number } = {}) {
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
    class="w-full max-w-full md:w-[720px] md:max-w-[720px]"
  >
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
            :placeholder="$t('platform.region.selectParent')"
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
  <Drawer v-else :title="formTitle" class="w-full">
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
            :placeholder="$t('platform.region.selectParent')"
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
  </Drawer>
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
