<script lang="ts" setup>
import type { ArticleRow, ArticleSavePayload } from '#/features/cms/api';

import { computed, onMounted, ref } from 'vue';

import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { usePreferences } from '@vben/preferences';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import {
  createArticle,
  fetchArticle,
  fetchCategoryOptions,
  updateArticle,
} from '#/features/cms/api';
import { $t } from '#/locales';

import { buildArticleFormSchema } from './data';

const emits = defineEmits<{ success: [] }>();
const { isMobile } = usePreferences();
const editingId = ref<number | null>(null);
const categoryOptions = ref<{ label: string; value: number }[]>([]);

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: buildArticleFormSchema([]),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
});

const formTitle = computed(() => (editingId.value ? '编辑文章' : '新增文章'));

type PopupApi = {
  close: () => void;
  getData: <T = Record<string, never>>() => T;
  lock: () => void;
  open: () => void;
  setData: <T>(data: T) => void;
  unlock: () => void;
};

async function loadCategories() {
  const items = await fetchCategoryOptions();
  categoryOptions.value = items.map((item) => ({
    label: item.name,
    value: item.id,
  }));
  formApi.setState({ schema: buildArticleFormSchema(categoryOptions.value) });
}

async function handleConfirm(popupApi: PopupApi) {
  const { valid } = await formApi.validate();
  if (!valid) return;

  const values = (await formApi.getValues()) as ArticleSavePayload;
  popupApi.lock();
  try {
    if (!editingId.value) {
      await createArticle(values);
      message.success($t('crud.created'));
    } else {
      await updateArticle(editingId.value, values);
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
  await loadCategories();
  formApi.resetForm();
  const data = popupApi.getData<ArticleRow | Record<string, never>>();
  const id =
    data && typeof (data as ArticleRow).id === 'number'
      ? (data as ArticleRow).id
      : null;
  editingId.value = id;

  if (id) {
    const detail = await fetchArticle(id);
    await formApi.setValues({
      category_id: detail.category_id ?? undefined,
      title: detail.title,
      slug: detail.slug,
      summary: detail.summary ?? '',
      content: detail.content ?? '',
      cover: detail.cover ?? '',
      status: detail.status,
    });
  } else {
    await formApi.setValues({
      category_id: undefined,
      title: '',
      slug: '',
      summary: '',
      content: '',
      cover: '',
      status: 0,
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

function open(data: ArticleRow | Record<string, never> = {}) {
  const api = (isMobile.value ? drawerApi : modalApi) as PopupApi;
  api.setData(data);
  api.open();
}

onMounted(() => {
  void loadCategories();
});

defineExpose({ open });
</script>

<template>
  <Modal
    v-if="!isMobile"
    :title="formTitle"
    class="w-full max-w-full md:w-[720px] md:max-w-[720px]"
  >
    <Form />
  </Modal>
  <Drawer v-else :title="formTitle" class="w-full">
    <Form />
  </Drawer>
</template>
