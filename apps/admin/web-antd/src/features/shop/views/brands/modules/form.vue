<script lang="ts" setup>
import type { BrandRow, BrandSavePayload } from '#/features/shop/api';

import type { UploadFile } from 'ant-design-vue/es/upload';

import { computed, reactive, ref } from 'vue';

import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { usePreferences } from '@vben/preferences';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { createBrand, fetchBrand, updateBrand } from '#/features/shop/api';
import { uploadAttachment } from '#/features/system/api/attachment';
import { $t } from '#/locales';
import { validateUploadFile } from '#/utils/upload-policy';

import { buildBrandFormSchema } from '../data';

const emits = defineEmits<{ success: [] }>();
const { isMobile } = usePreferences();
const editingId = ref<number | null>(null);

function normalizeLogoToFileList(logo?: null | string): UploadFile[] {
  if (!logo) {
    return [];
  }
  return [
    {
      name: 'logo',
      status: 'done',
      uid: 'brand-logo-existing',
      url: logo,
    },
  ];
}

function resolveLogoUrl(files: unknown): string | null {
  const imageFiles = Array.isArray(files) ? files : [];
  const imageFile = imageFiles[0] as
    | { response?: { file_url?: string }; url?: string }
    | undefined;
  if (!imageFile) {
    return null;
  }
  return imageFile.response?.file_url ?? imageFile.url ?? null;
}

function onImageChange(_event: unknown) {}

async function onImageUpload(options: any) {
  const file = options?.file as File | undefined;
  if (!file) {
    options?.onError?.(new Error('missing file'));
    return;
  }

  const validationError = validateUploadFile(file, 'image');
  if (validationError) {
    message.error(validationError);
    options?.onError?.(new Error(validationError));
    return;
  }

  try {
    const row = await uploadAttachment(file);
    options?.onSuccess?.(row);
  } catch (error) {
    options?.onError?.(error);
    throw error;
  }
}

const [Form, formApi] = useVbenForm(
  reactive({
    commonConfig: {
      componentProps: { class: 'w-full' },
      labelClass: 'justify-start',
    },
    layout: 'vertical' as const,
    schema: buildBrandFormSchema({
      onImageChange,
      onImageUpload,
    }),
    showDefaultActions: false,
    wrapperClass: 'grid-cols-1 gap-y-1',
  }) as any,
);

const formTitle = computed(() => (editingId.value ? '编辑品牌' : '新增品牌'));

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

  const values = (await formApi.getValues()) as BrandSavePayload;
  values.logo = resolveLogoUrl((values as { logo?: unknown }).logo);

  popupApi.lock();
  try {
    if (!editingId.value) {
      await createBrand(values);
      message.success($t('crud.created'));
    } else {
      await updateBrand(editingId.value, values);
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
  const data = popupApi.getData<BrandRow | Record<string, never>>();
  const id =
    data && typeof (data as BrandRow).id === 'number'
      ? (data as BrandRow).id
      : null;
  editingId.value = id;

  if (id) {
    const detail = await fetchBrand(id);
    await formApi.setValues({
      name: detail.name,
      logo: normalizeLogoToFileList(detail.logo),
      sort: detail.sort,
      status: detail.status,
    });
  } else {
    await formApi.setValues({
      name: '',
      logo: [],
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

function open(data: BrandRow | Record<string, never> = {}) {
  const api = (isMobile.value ? drawerApi : modalApi) as PopupApi;
  api.setData(data);
  api.open();
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
  <Drawer v-else :title="formTitle" class="w-full">
    <Form />
  </Drawer>
</template>
