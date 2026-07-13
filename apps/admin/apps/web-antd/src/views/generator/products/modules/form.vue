<script lang="ts" setup>
import type { ProductsRow, ProductsSavePayload } from '#/api/generator/products';

import { computed, ref } from 'vue';

import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { $t } from '@vben/locales';
import { usePreferences } from '@vben/preferences';

import { message } from 'ant-design-vue';
import type { UploadFile } from 'ant-design-vue/es/upload';

import { uploadAttachment } from '#/api/system/attachment';
import { validateUploadFile } from '#/utils/upload-policy';


import { useVbenForm } from '#/adapter/form';
import { createProducts, updateProducts } from '#/api/generator/products';

import { buildProductsFormSchema } from '../data';

const emits = defineEmits<{ success: [] }>();

const { isMobile } = usePreferences();

const editingId = ref<number | null>(null);
const readonly = ref(false);

const pictureUploadFieldNames = ['cover', 'images'] as const;

const multiValueFieldNames = ['flags'] as const;


function normalizeUploadToFileList(url?: null | string): UploadFile[] {
  if (!url) {
    return [];
  }
  return [
    {
      name: 'file',
      status: 'done',
      uid: 'upload-existing',
      url,
    },
  ];
}

function resolveUploadUrl(files: unknown): string {
  const uploadFiles = Array.isArray(files) ? files : [];
  const file = uploadFiles[0] as
    | { response?: { file_url?: string }; url?: string }
    | undefined;
  if (!file) {
    return '';
  }
  return file.response?.file_url ?? file.url ?? '';
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

function buildSchema() {
  return buildProductsFormSchema({
    readonly: readonly.value,
    onImageChange,
    onImageUpload,
  });
}


const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: buildSchema(),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
});

const formTitle = computed(() => {
  if (readonly.value) {
    return $t('generator.products.form.viewTitle');
  }

  return editingId.value
    ? $t('generator.products.form.editTitle')
    : $t('generator.products.form.createTitle');
});

type PopupApi = {
  close: () => void;
  getData: <T = Record<string, never>>() => T;
  lock: () => void;
  open: () => void;
  setData: <T>(data: T) => void;
  unlock: () => void;
};

async function handleConfirm(popupApi: PopupApi) {
  if (readonly.value) {
    popupApi.close();
    return;
  }
  const { valid } = await formApi.validate();
  if (!valid) return;

  const values = (await formApi.getValues()) as Record<string, unknown>;
  for (const fieldName of pictureUploadFieldNames) {
    if (fieldName in values) {
      values[fieldName] = resolveUploadUrl(values[fieldName]);
    }
  }

  for (const fieldName of multiValueFieldNames) {
    const current = values[fieldName];
    if (Array.isArray(current)) {
      values[fieldName] = current.join(',');
    }
  }

  const payload = values as ProductsSavePayload;
  popupApi.lock();
  try {
    if (!editingId.value) {
      await createProducts(payload);
      message.success($t('crud.created'));
    } else {
      await updateProducts(editingId.value, payload);
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
  const data = popupApi.getData<ProductsRow | Record<string, never>>();
  const id = data && typeof (data as ProductsRow).id === 'number' ? (data as ProductsRow).id : null;
  editingId.value = id;
  readonly.value = false;
  formApi.setState({ schema: buildSchema() });
  if (id) {
    const row = { ...(data as ProductsRow) } as Record<string, unknown>;

    for (const fieldName of pictureUploadFieldNames) {
      const value = row[fieldName];
      if (typeof value === 'string' && value) {
        row[fieldName] = normalizeUploadToFileList(value);
      }
    }

    for (const fieldName of multiValueFieldNames) {
      const value = row[fieldName];
      if (typeof value === 'string' && value) {
        row[fieldName] = value.split(',').filter(Boolean);
      }
    }

    formApi.setValues(row as ProductsRow);

  }
}

const [ModalView, modalApi] = useVbenModal({
  onConfirm: () => handleConfirm(modalApi),
  onOpenChange: (isOpen) => handleOpenChange(isOpen, modalApi),
});

const [DrawerView, drawerApi] = useVbenDrawer({
  onConfirm: () => handleConfirm(drawerApi),
  onOpenChange: (isOpen) => handleOpenChange(isOpen, drawerApi),
});

function open(data: ProductsRow | Record<string, never> = {}) {
  const api = isMobile.value ? drawerApi : modalApi;
  api.setData(data);
  api.open();
}

defineExpose({ open });
</script>

<template>
  <ModalView v-if="!isMobile" :title="formTitle" class="w-[860px]">

    <Form />
  </ModalView>
  <DrawerView v-else :title="formTitle" class="w-full">
    <Form />
  </DrawerView>
</template>
