<script lang="ts" setup>
import type { AttachmentRow } from '#/features/system/api/attachment';

import { computed, ref, useTemplateRef, watch } from 'vue';

import { useAccess } from '@vben/access';
import { IconifyIcon } from '@vben/icons';
import { $t } from '@vben/locales';

import { Button, Image, message } from 'ant-design-vue';

import { uploadAttachment } from '#/features/system/api/attachment';
import { ATTACHMENT_PERMISSIONS } from '#/features/system/constants/attachment-permissions';

import AttachmentPickerModal from './attachment-picker-modal.vue';
import type { AttachmentAcceptMode } from './types';
import {
  extractFileNameFromUrl,
  formatAttachmentDisplayName,
  isImageUrl,
  normalizeFieldValue,
} from './utils';
import { resolveUploadAcceptAttr, validateUploadFile } from '#/utils/upload-policy';

interface FileMeta {
  name: string;
  size?: number;
}

const props = withDefaults(
  defineProps<{
    accept?: AttachmentAcceptMode;
    disabled?: boolean;
    multiple?: boolean;
    placeholder?: string;
    value?: string | string[];
  }>(),
  {
    accept: 'all',
    disabled: false,
    multiple: false,
    placeholder: undefined,
    value: '',
  },
);

const emit = defineEmits<{
  change: [value: string | string[]];
  'update:value': [value: string | string[]];
}>();

defineOptions({ inheritAttrs: false });

const { hasAccessByCodes } = useAccess();
const canUpload = computed(() =>
  hasAccessByCodes([ATTACHMENT_PERMISSIONS.UPLOAD]),
);

const pickerOpen = ref(false);
const uploading = ref(false);
const isDragOver = ref(false);
const fileInputRef = useTemplateRef<HTMLInputElement>('fileInputRef');
const fileMetaMap = ref<Record<string, FileMeta>>({});
const brokenImageUrls = ref<Set<string>>(new Set());
const previewUrl = ref('');
const previewVisible = ref(false);

const currentUrls = computed(() => {
  const normalized = normalizeFieldValue(props.value, props.multiple);
  return Array.isArray(normalized) ? normalized : normalized ? [normalized] : [];
});

const isInteractive = computed(() => !props.disabled);

const rootClass = computed(() => ({
  'attachment-field': true,
  'attachment-field--disabled': props.disabled,
  'attachment-field--drag-over': isDragOver.value && isInteractive.value,
  'w-full': true,
  'min-w-0': true,
}));

function emitValue(urls: string[]) {
  const nextValue = props.multiple ? urls : (urls[0] ?? '');
  emit('update:value', nextValue);
  emit('change', nextValue);
}

function rememberMeta(url: string, meta: Partial<FileMeta>) {
  if (!url) {
    return;
  }
  fileMetaMap.value = {
    ...fileMetaMap.value,
    [url]: {
      name: meta.name ?? extractFileNameFromUrl(url),
      size: meta.size,
    },
  };
}

function rememberFromRow(row: AttachmentRow) {
  if (!row.file_url) {
    return;
  }
  rememberMeta(row.file_url, {
    name: row.file_name,
    size: row.file_size,
  });
}

watch(
  currentUrls,
  (urls) => {
    const nextMap = { ...fileMetaMap.value };
    for (const url of urls) {
      if (!nextMap[url]) {
        nextMap[url] = { name: extractFileNameFromUrl(url) };
      }
    }
    for (const key of Object.keys(nextMap)) {
      if (!urls.includes(key)) {
        delete nextMap[key];
      }
    }
    fileMetaMap.value = nextMap;
  },
  { immediate: true },
);

function getDisplayName(url: string) {
  const meta = fileMetaMap.value[url];
  const name = meta?.name ?? extractFileNameFromUrl(url);
  return formatAttachmentDisplayName(name, meta?.size);
}

function getFileIcon(url: string) {
  return isImageUrl(url) ? 'lucide:image' : 'lucide:paperclip';
}

function onUploadTrigger() {
  if (!isInteractive.value) {
    return;
  }
  if (!canUpload.value) {
    message.warning($t('resource.attachment.field.noUploadPermission'));
    return;
  }
  fileInputRef.value?.click();
}

function onSelectClick() {
  if (!isInteractive.value) {
    return;
  }
  pickerOpen.value = true;
}

async function uploadFiles(files: File[]) {
  if (files.length === 0 || !isInteractive.value) {
    return;
  }
  if (!canUpload.value) {
    message.warning($t('resource.attachment.field.noUploadPermission'));
    return;
  }

  if (!props.multiple && files.length > 1) {
    message.warning($t('resource.attachment.field.singleModeWarning'));
  }

  const uploadFiles = props.multiple ? files : files.slice(0, 1);
  uploading.value = true;
  try {
    const uploadedUrls: string[] = [];
    for (const file of uploadFiles) {
      const validationError = validateUploadFile(file, props.accept);
      if (validationError) {
        message.error(validationError);
        continue;
      }
      const row = await uploadAttachment(file);
      if (row.file_url) {
        rememberFromRow(row);
        uploadedUrls.push(row.file_url);
      }
    }
    if (uploadedUrls.length === 0) {
      message.error($t('resource.attachment.field.uploadNoUrl'));
      return;
    }
    if (props.multiple) {
      emitValue([...currentUrls.value, ...uploadedUrls]);
    } else {
      emitValue([uploadedUrls[0]!]);
    }
    message.success($t('resource.attachment.field.uploadSuccess'));
  } catch {
    message.error($t('resource.attachment.field.uploadFailed'));
  } finally {
    uploading.value = false;
  }
}

async function onFileInputChange(event: Event) {
  const input = event.target as HTMLInputElement;
  const files = input.files ? [...input.files] : [];
  input.value = '';
  await uploadFiles(files);
}

function onDragOver(event: DragEvent) {
  if (!isInteractive.value) {
    return;
  }
  event.preventDefault();
  event.stopPropagation();
  isDragOver.value = true;
}

function onDragLeave(event: DragEvent) {
  if (!isInteractive.value) {
    return;
  }
  event.preventDefault();
  event.stopPropagation();
  const currentTarget = event.currentTarget as HTMLElement | null;
  if (currentTarget && !currentTarget.contains(event.relatedTarget as Node | null)) {
    isDragOver.value = false;
  }
}

async function onDrop(event: DragEvent) {
  if (!isInteractive.value) {
    return;
  }
  event.preventDefault();
  event.stopPropagation();
  isDragOver.value = false;
  const files = event.dataTransfer?.files
    ? [...event.dataTransfer.files]
    : [];
  await uploadFiles(files);
}

function onPickerConfirm(urls: string[], rows: AttachmentRow[] = []) {
  for (const row of rows) {
    rememberFromRow(row);
  }
  for (const url of urls) {
    if (!fileMetaMap.value[url]) {
      rememberMeta(url, { name: extractFileNameFromUrl(url) });
    }
  }
  if (props.multiple) {
    emitValue([...new Set([...currentUrls.value, ...urls])]);
  } else {
    emitValue(urls.slice(0, 1));
  }
}

function onRemoveUrl(url: string) {
  if (props.disabled) {
    return;
  }
  emitValue(currentUrls.value.filter((item) => item !== url));
  brokenImageUrls.value = new Set(
    [...brokenImageUrls.value].filter((item) => item !== url),
  );
}

function shouldShowImagePreview(url: string) {
  return isImageUrl(url) && !brokenImageUrls.value.has(url);
}

function onPreviewUrl(url: string) {
  if (!url || !shouldShowImagePreview(url)) {
    return;
  }
  previewUrl.value = url;
  previewVisible.value = true;
}

function onOpenUrl(url: string) {
  if (!url) {
    return;
  }
  window.open(url, '_blank', 'noopener,noreferrer');
}

function onItemClick(url: string) {
  if (shouldShowImagePreview(url)) {
    onPreviewUrl(url);
    return;
  }
  onOpenUrl(url);
}

function onPreviewVisibleChange(visible: boolean) {
  previewVisible.value = visible;
  if (!visible) {
    previewUrl.value = '';
  }
}
</script>

<template>
  <div
    v-bind="$attrs"
    :class="rootClass"
    @dragover.prevent="onDragOver"
    @dragleave.prevent="onDragLeave"
    @drop.prevent="onDrop"
  >
    <div class="attachment-field__header">
      <button
        class="attachment-field__trigger"
        :class="{ 'attachment-field__trigger--disabled': !isInteractive }"
        :disabled="!isInteractive || uploading"
        type="button"
        @click="onUploadTrigger"
      >
        <IconifyIcon
          v-if="isInteractive"
          class="attachment-field__trigger-icon"
          icon="lucide:paperclip"
        />
        <span class="attachment-field__trigger-text">
          <template v-if="isDragOver">
            {{ $t('resource.attachment.field.dragHint') }}
          </template>
          <template v-else>
            <span class="attachment-field__trigger-label">
              {{ $t('resource.attachment.field.entityLabel') }}
            </span>
            <span class="attachment-field__trigger-tip">
              {{ $t('resource.attachment.field.dragTip') }}
            </span>
          </template>
        </span>
      </button>

      <Button
        v-if="isInteractive"
        class="attachment-field__select-btn shrink-0"
        :loading="uploading"
        type="primary"
        @click.stop="onSelectClick"
      >
        <IconifyIcon class="size-3.5" icon="lucide:folder-open" />
        {{ $t('resource.attachment.field.select') }}
      </Button>
    </div>

    <div v-if="currentUrls.length > 0" class="attachment-field__body">
      <div
        v-for="url in currentUrls"
        :key="url"
        class="attachment-field__item"
      >
        <button
          class="attachment-field__item-main"
          :title="url"
          type="button"
          @click="onItemClick(url)"
        >
          <IconifyIcon
            class="attachment-field__item-icon"
            :icon="getFileIcon(url)"
          />
          <span class="attachment-field__item-name">
            {{ getDisplayName(url) }}
          </span>
        </button>
        <button
          v-if="!disabled"
          class="attachment-field__item-remove"
          :title="$t('resource.attachment.field.remove')"
          type="button"
          @click.stop="onRemoveUrl(url)"
        >
          <IconifyIcon class="size-4" icon="lucide:x" />
        </button>
      </div>
    </div>

    <input
      ref="fileInputRef"
      class="attachment-field__file-input"
      type="file"
      :accept="resolveUploadAcceptAttr(accept)"
      :disabled="disabled"
      :multiple="multiple"
      @change="onFileInputChange"
    />

    <AttachmentPickerModal
      v-model:open="pickerOpen"
      :accept="accept"
      :initial-urls="currentUrls"
      :multiple="multiple"
      @confirm="onPickerConfirm"
    />

    <Image
      v-if="previewUrl"
      :preview="{
        visible: previewVisible,
        onVisibleChange: onPreviewVisibleChange,
      }"
      :src="previewUrl"
      :style="{
        position: 'fixed',
        width: 0,
        height: 0,
        opacity: 0,
        pointerEvents: 'none',
      }"
    />
  </div>
</template>
