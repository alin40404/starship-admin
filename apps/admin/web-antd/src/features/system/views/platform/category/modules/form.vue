<script lang="ts" setup>
import type {
  CategorySavePayload,
  CategoryTreeNode,
  CategoryTypeOption,
} from '#/features/system/api/category';

import type { UploadFile } from 'ant-design-vue/es/upload';

import { computed, reactive, ref, watch } from 'vue';

import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { $t } from '@vben/locales';
import { usePreferences } from '@vben/preferences';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import {
  createCategory,
  fetchCategory,
  fetchCategoryOptionsTypes,
  updateCategory,
} from '#/features/system/api/category';
import { uploadAttachment } from '#/features/system/api/attachment';
import { validateUploadFile } from '#/utils/upload-policy';

import { buildFormSchema } from '../data';

const emits = defineEmits<{ success: [] }>();

const { isMobile } = usePreferences();

const editingId = ref<number | null>(null);
const typeOptions = ref<CategoryTypeOption[]>([]);

function normalizeImageToFileList(image?: null | string): UploadFile[] {
  if (!image) {
    return [];
  }
  return [
    {
      name: 'image',
      status: 'done',
      uid: 'category-image-existing',
      url: image,
    },
  ];
}

function resolveImageUrl(files: unknown): string {
  const imageFiles = Array.isArray(files) ? files : [];
  const imageFile = imageFiles[0] as
    | { response?: { file_url?: string }; url?: string }
    | undefined;
  if (!imageFile) {
    return '';
  }
  return imageFile.response?.file_url ?? imageFile.url ?? '';
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

function refreshFormSchema() {
  formApi.setState({
    schema: buildFormSchema({
      excludeId: editingId.value ?? undefined,
      onImageChange,
      onImageUpload,
      onTypeChange: () => {
        formApi.setFieldValue('parent_id', 0);
      },
      typeOptions: typeOptions.value,
    }),
  });
}

const [Form, formApi] = useVbenForm(
  reactive({
    commonConfig: {
      componentProps: { class: 'w-full' },
      labelClass: 'justify-start',
    },
    layout: 'vertical' as const,
    schema: buildFormSchema({
      onImageChange,
      onImageUpload,
      onTypeChange: () => {
        formApi.setFieldValue('parent_id', 0);
      },
      typeOptions: [],
    }),
    showDefaultActions: false,
    wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
  }) as any,
);

watch([editingId, typeOptions], () => {
  refreshFormSchema();
});

const formTitle = computed(() =>
  editingId.value
    ? $t('platform.category.editCategory')
    : $t('platform.category.createCategoryTitle'),
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

  const values = (await formApi.getValues()) as CategorySavePayload;
  if (!values.parent_id) values.parent_id = 0;
  values.image = resolveImageUrl((values as { image?: unknown }).image) || null;

  popupApi.lock();
  try {
    if (!editingId.value) {
      await createCategory(values);
      message.success($t('crud.created'));
    } else {
      await updateCategory(editingId.value, values);
      message.success($t('crud.saved'));
    }
    popupApi.close();
    emits('success');
  } finally {
    popupApi.unlock();
  }
}

async function handleOpenChange(isOpen: boolean, popupApi: PopupApi) {
  if (!isOpen) {
    editingId.value = null;
    return;
  }

  formApi.resetForm();
  if (typeOptions.value.length === 0) {
    typeOptions.value = await fetchCategoryOptionsTypes();
  }

  const data = popupApi.getData<
    CategoryTreeNode | { parent_id?: number; type?: string }
  >();
  const id =
    data && typeof (data as CategoryTreeNode).id === 'number'
      ? (data as CategoryTreeNode).id
      : null;
  editingId.value = id;
  refreshFormSchema();

  if (id) {
    const detail = await fetchCategory(id);
    formApi.setValues({
      description: detail.description ?? '',
      image: normalizeImageToFileList(detail.image),
      keywords: detail.keywords ?? '',
      name: detail.name,
      slug: detail.slug,
      parent_id: detail.parent_id ?? 0,
      sort: detail.sort ?? 0,
      status: detail.status,
      type: detail.type,
    });
  } else {
    const preset = data as { parent_id?: number; type?: string };
    formApi.setValues({
      description: '',
      image: [],
      keywords: '',
      name: '',
      slug: '',
      parent_id: preset.parent_id ?? 0,
      sort: 0,
      status: 'normal',
      type: preset.type ?? typeOptions.value[0]?.value ?? '',
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

function open(
  data: CategoryTreeNode | { parent_id?: number; type?: string } = {},
) {
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
    <div
      class="mb-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800"
    >
      {{ $t('platform.category.typeHint') }}
    </div>
    <Form />
  </Modal>
  <Drawer v-else :title="formTitle" class="w-full">
    <div
      class="mb-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800"
    >
      {{ $t('platform.category.typeHint') }}
    </div>
    <Form />
  </Drawer>
</template>
