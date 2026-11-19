<script lang="ts" setup>
import type { ArticleRow, ArticleSavePayload } from '#/features/cms/api';

import type { UploadFile } from 'ant-design-vue/es/upload';

import { computed, reactive, ref } from 'vue';

import { useVbenModal } from '@vben/common-ui';

import { Button, message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { PictureUploadField } from '#/components/picture-upload-field';
import {
  createArticle,
  fetchArticle,
  fetchCmsCategoryOptions,
  updateArticle,
} from '#/features/cms/api';
import { uploadAttachment } from '#/features/system/api/attachment';
import { $t } from '#/locales';
import { validateUploadFile } from '#/utils/upload-policy';

import {
  buildArticleContentFormSchema,
  buildArticleOrgFormSchema,
  buildArticleStatusFormSchema,
  buildArticleSummaryFormSchema,
  buildArticleTitleFormSchema,
} from '../data';

/** 全屏弹窗内容区宽度，标题/按钮与之左右对齐 */
const FORM_CONTENT_MAX_CLASS = 'max-w-[1200px]';

const emits = defineEmits<{ success: [] }>();

const editingId = ref<number | null>(null);
const categoryOptions = ref<{ label: string; value: number }[]>([]);
const coverFileList = ref<UploadFile[]>([]);

function normalizeCoverToFileList(cover?: null | string): UploadFile[] {
  if (!cover) {
    return [];
  }
  return [
    {
      name: 'cover',
      status: 'done',
      uid: 'article-cover-existing',
      url: cover,
    },
  ];
}

function resolveCoverUrl(files: UploadFile[]): string {
  const file = files[0] as
    | { response?: { file_url?: string }; url?: string }
    | undefined;
  if (!file) {
    return '';
  }
  return file.response?.file_url ?? file.url ?? '';
}

async function onCoverUpload(options: any) {
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

const formCommon = {
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical' as const,
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 gap-y-1',
};

const [TitleForm, titleFormApi] = useVbenForm({
  ...formCommon,
  schema: buildArticleTitleFormSchema(),
});

const [SummaryForm, summaryFormApi] = useVbenForm({
  ...formCommon,
  schema: buildArticleSummaryFormSchema(),
});

const [ContentForm, contentFormApi] = useVbenForm({
  ...formCommon,
  schema: buildArticleContentFormSchema(),
});

const [StatusForm, statusFormApi] = useVbenForm({
  ...formCommon,
  schema: buildArticleStatusFormSchema(),
});

const [OrgForm, orgFormApi] = useVbenForm(
  reactive({
    ...formCommon,
    schema: buildArticleOrgFormSchema([]),
  }) as any,
);

function refreshOrgSchema() {
  orgFormApi.setState({
    schema: buildArticleOrgFormSchema(categoryOptions.value),
  });
}

const formTitle = computed(() => (editingId.value ? '编辑文章' : '新增文章'));

type PopupApi = {
  close: () => void;
  getData: <T = Record<string, never>>() => T;
  lock: () => void;
  open: () => void;
  setData: <T>(data: T) => void;
  unlock: () => void;
};

async function refreshOptions() {
  categoryOptions.value = await fetchCmsCategoryOptions();
  refreshOrgSchema();
}

function toPayload(
  title: Record<string, unknown>,
  summary: Record<string, unknown>,
  content: Record<string, unknown>,
  status: Record<string, unknown>,
  org: Record<string, unknown>,
): ArticleSavePayload {
  return {
    category_id: org.category_id ? Number(org.category_id) : null,
    title: String(title.title ?? ''),
    slug: String(title.slug ?? ''),
    summary: (summary.summary as string) || null,
    content: (content.content as string) || null,
    cover: resolveCoverUrl(coverFileList.value).trim() || null,
    status: Number(status.status ?? 0),
  };
}

async function handleConfirm(popupApi: PopupApi) {
  const { valid: titleValid } = await titleFormApi.validate();
  if (!titleValid) return;

  const [title, summary, content, status, org] = await Promise.all([
    titleFormApi.getValues(),
    summaryFormApi.getValues(),
    contentFormApi.getValues(),
    statusFormApi.getValues(),
    orgFormApi.getValues(),
  ]);

  popupApi.lock();
  try {
    const payload = toPayload(title, summary, content, status, org);
    if (!editingId.value) {
      await createArticle(payload);
      message.success($t('crud.created'));
    } else {
      await updateArticle(editingId.value, payload);
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
    coverFileList.value = [];
    return;
  }

  await refreshOptions();
  titleFormApi.resetForm();
  summaryFormApi.resetForm();
  contentFormApi.resetForm();
  statusFormApi.resetForm();
  orgFormApi.resetForm();
  coverFileList.value = [];

  const data = popupApi.getData<ArticleRow | Record<string, never>>();
  const id =
    data && typeof (data as ArticleRow).id === 'number'
      ? (data as ArticleRow).id
      : null;
  editingId.value = id;

  if (id) {
    const detail = await fetchArticle(id);
    coverFileList.value = normalizeCoverToFileList(detail.cover);
    await Promise.all([
      titleFormApi.setValues({
        title: detail.title,
        slug: detail.slug,
      }),
      summaryFormApi.setValues({
        summary: detail.summary ?? '',
      }),
      contentFormApi.setValues({
        content: detail.content ?? '',
      }),
      statusFormApi.setValues({
        status: detail.status,
      }),
      orgFormApi.setValues({
        category_id: detail.category_id ?? undefined,
      }),
    ]);
  } else {
    await Promise.all([
      titleFormApi.setValues({ title: '', slug: '' }),
      summaryFormApi.setValues({ summary: '' }),
      contentFormApi.setValues({ content: '' }),
      statusFormApi.setValues({ status: 0 }),
      orgFormApi.setValues({ category_id: undefined }),
    ]);
  }
}

const [Modal, modalApi] = useVbenModal({
  contentClass: 'bg-muted/40',
  destroyOnClose: true,
  footerClass: '!justify-center px-5',
  fullscreen: true,
  fullscreenButton: false,
  headerClass: 'px-5 *:mx-auto *:w-full *:max-w-[1200px]',
  async onConfirm() {
    await handleConfirm(modalApi as PopupApi);
  },
  async onOpenChange(isOpen: boolean) {
    await handleOpenChange(isOpen, modalApi as PopupApi);
  },
});

const submitting = modalApi.useStore((s) => s.submitting);
const confirmLoading = modalApi.useStore((s) => s.confirmLoading);

function open(data: ArticleRow | Record<string, never> = {}) {
  modalApi.setData(data);
  modalApi.open();
}

defineExpose({ open });
</script>

<template>
  <Modal :title="formTitle">
    <div class="mx-auto w-full" :class="FORM_CONTENT_MAX_CLASS">
      <div
        class="grid grid-cols-1 items-start gap-4 lg:grid-cols-[minmax(0,1fr)_280px]"
      >
        <div class="flex min-w-0 flex-col gap-4">
          <section
            class="rounded-lg border border-border bg-card p-4 shadow-sm"
          >
            <h3 class="mb-3 text-sm font-semibold">标题</h3>
            <TitleForm />
          </section>

          <section
            class="rounded-lg border border-border bg-card p-4 shadow-sm"
          >
            <h3 class="mb-3 text-sm font-semibold">封面</h3>
            <PictureUploadField
              accept="image"
              :custom-request="onCoverUpload"
              v-model:file-list="coverFileList"
              :max-count="1"
            />
          </section>

          <section
            class="rounded-lg border border-border bg-card p-4 shadow-sm"
          >
            <h3 class="mb-3 text-sm font-semibold">摘要</h3>
            <SummaryForm />
          </section>

          <section
            class="rounded-lg border border-border bg-card p-4 shadow-sm"
          >
            <h3 class="mb-3 text-sm font-semibold">正文</h3>
            <ContentForm />
          </section>
        </div>

        <div class="flex flex-col gap-4 lg:sticky lg:top-0">
          <section
            class="rounded-lg border border-border bg-card p-4 shadow-sm"
          >
            <h3 class="mb-3 text-sm font-semibold">状态</h3>
            <StatusForm />
          </section>

          <section
            class="rounded-lg border border-border bg-card p-4 shadow-sm"
          >
            <h3 class="mb-3 text-sm font-semibold">文章组织</h3>
            <OrgForm />
          </section>
        </div>
      </div>
    </div>

    <template #footer>
      <div
        class="flex w-full items-center justify-end gap-2"
        :class="FORM_CONTENT_MAX_CLASS"
      >
        <Button :disabled="submitting" @click="modalApi.onCancel()">
          {{ $t('common.cancel') }}
        </Button>
        <Button
          type="primary"
          :loading="confirmLoading || submitting"
          :disabled="submitting"
          @click="modalApi.onConfirm()"
        >
          {{ $t('common.confirm') }}
        </Button>
      </div>
    </template>
  </Modal>
</template>
