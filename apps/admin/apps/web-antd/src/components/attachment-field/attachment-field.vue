<script lang="ts" setup>
import { computed, ref, useTemplateRef } from 'vue';

import { useAccess } from '@vben/access';
import { IconifyIcon } from '@vben/icons';
import { $t } from '@vben/locales';

import { Button, Image, Input, message, Space } from 'ant-design-vue';

import { uploadAttachment } from '#/api/system/attachment';
import { ATTACHMENT_PERMISSIONS } from '#/constants/attachment-permissions';

import AttachmentPickerModal from './attachment-picker-modal.vue';
import type { AttachmentAcceptMode } from './types';
import {
  isImageUrl,
  normalizeFieldValue,
  parseInputText,
  resolveUploadAccept,
  valueToInputText,
} from './utils';

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
const fileInputRef = useTemplateRef<HTMLInputElement>('fileInputRef');
const brokenImageUrls = ref<Set<string>>(new Set());
const previewUrl = ref('');
const previewVisible = ref(false);

const currentUrls = computed(() => {
  const normalized = normalizeFieldValue(props.value, props.multiple);
  return Array.isArray(normalized) ? normalized : normalized ? [normalized] : [];
});

const inputText = computed({
  get() {
    return valueToInputText(props.value, props.multiple);
  },
  set(text: string) {
    const nextValue = parseInputText(text, props.multiple);
    emit('update:value', nextValue);
    emit('change', nextValue);
  },
});

const resolvedPlaceholder = computed(
  () => props.placeholder ?? $t('resource.attachment.field.placeholder'),
);

function emitValue(urls: string[]) {
  const nextValue = props.multiple ? urls : (urls[0] ?? '');
  emit('update:value', nextValue);
  emit('change', nextValue);
}

function onUploadClick() {
  if (props.disabled) {
    return;
  }
  if (!canUpload.value) {
    message.warning($t('resource.attachment.field.noUploadPermission'));
    return;
  }
  fileInputRef.value?.click();
}

function onSelectClick() {
  if (props.disabled) {
    return;
  }
  pickerOpen.value = true;
}

async function onFileInputChange(event: Event) {
  const input = event.target as HTMLInputElement;
  const files = input.files ? [...input.files] : [];
  input.value = '';
  if (files.length === 0) {
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
      const row = await uploadAttachment(file);
      if (row.file_url) {
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

function onPickerConfirm(urls: string[]) {
  if (props.multiple) {
    emitValue([...new Set([...currentUrls.value, ...urls])]);
  } else {
    emitValue(urls.slice(0, 1));
  }
}

function shouldShowImagePreview(url: string) {
  return isImageUrl(url) && !brokenImageUrls.value.has(url);
}

function onPreviewImageError(url: string) {
  brokenImageUrls.value = new Set([...brokenImageUrls.value, url]);
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

function onPreviewVisibleChange(visible: boolean) {
  previewVisible.value = visible;
  if (!visible) {
    previewUrl.value = '';
  }
}
</script>

<template>
  <div class="attachment-field w-full min-w-0">
    <Space.Compact block class="attachment-field__compact w-full">
      <Input
        v-bind="$attrs"
        v-model:value="inputText"
        class="attachment-field__input min-w-0 flex-1"
        :disabled="disabled"
        :placeholder="resolvedPlaceholder"
      />
      <Button
        class="attachment-field__btn attachment-field__btn--upload"
        :disabled="disabled || uploading"
        :loading="uploading"
        size="small"
        @click="onUploadClick"
      >
        <IconifyIcon class="size-3.5" icon="lucide:upload" />
        <span>{{ $t('resource.attachment.field.upload') }}</span>
      </Button>
      <Button
        class="attachment-field__btn attachment-field__btn--select attachment-field__btn--select-last"
        :disabled="disabled"
        size="small"
        @click="onSelectClick"
      >
        <IconifyIcon class="size-3.5" icon="lucide:folder-open" />
        <span>{{ $t('resource.attachment.field.select') }}</span>
      </Button>
    </Space.Compact>

    <div
      v-if="currentUrls.length > 0"
      class="attachment-field__preview-list mt-2 flex flex-wrap gap-2"
    >
      <div
        v-for="url in currentUrls"
        :key="url"
        class="attachment-field__preview-item group"
        :title="url"
      >
        <img
          v-if="shouldShowImagePreview(url)"
          :alt="url"
          class="attachment-field__preview-image"
          :src="url"
          @error="onPreviewImageError(url)"
        />
        <div v-else class="attachment-field__preview-file">
          <IconifyIcon class="size-7 text-muted-foreground" icon="lucide:paperclip" />
        </div>
        <button
          v-if="!disabled"
          class="attachment-field__preview-remove"
          :title="$t('resource.attachment.field.remove')"
          type="button"
          @click="onRemoveUrl(url)"
        >
          <IconifyIcon class="size-3" icon="lucide:x" />
        </button>
        <button
          class="attachment-field__preview-open"
          :title="
            shouldShowImagePreview(url)
              ? $t('resource.attachment.field.preview')
              : $t('resource.attachment.field.open')
          "
          type="button"
          @click="
            shouldShowImagePreview(url)
              ? onPreviewUrl(url)
              : onOpenUrl(url)
          "
        >
          <IconifyIcon
            class="size-3"
            :icon="
              shouldShowImagePreview(url)
                ? 'lucide:eye'
                : 'lucide:external-link'
            "
          />
        </button>
      </div>
    </div>

    <input
      ref="fileInputRef"
      class="attachment-field__file-input"
      type="file"
      :accept="resolveUploadAccept(accept)"
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

<style scoped>
.attachment-field__file-input {
  display: none !important;
}

.attachment-field__compact :deep(.ant-input) {
  border-start-start-radius: var(--radius);
  border-end-start-radius: var(--radius);
  border-start-end-radius: 0;
  border-end-end-radius: 0;
}

.attachment-field__btn {
  display: inline-flex;
  align-items: center;
  gap: 0;
  height: 32px;
  padding-inline: 10px;
  font-size: 12px;
  line-height: 1;
  border-radius: 0;
}

.attachment-field__btn--upload {
  color: #fff;
  background: #1677ff;
  border-color: #1677ff;
}

.attachment-field__btn--upload:hover:not(:disabled) {
  color: #fff;
  background: #4096ff;
  border-color: #4096ff;
}

.attachment-field__btn--select {
  color: #fff;
  background: #52c41a;
  border-color: #52c41a;
}

.attachment-field__btn--select-last {
  border-start-end-radius: var(--radius);
  border-end-end-radius: var(--radius);
}

.attachment-field__btn--select:hover:not(:disabled) {
  color: #fff;
  background: #73d13d;
  border-color: #73d13d;
}

.attachment-field__btn:disabled {
  opacity: 0.55;
}

.attachment-field__preview-item {
  position: relative;
  overflow: hidden;
  width: 64px;
  height: 64px;
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius-md);
  background: hsl(var(--background));
}

.attachment-field__preview-remove {
  position: absolute;
  top: 2px;
  right: 2px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  padding: 0;
  color: #fff;
  cursor: pointer;
  background: rgb(0 0 0 / 55%);
  border: none;
  border-radius: 50%;
  opacity: 0;
  transition: opacity 0.15s ease;
}

.attachment-field__preview-item:hover .attachment-field__preview-remove,
.attachment-field__preview-remove:focus-visible {
  opacity: 1;
}

.attachment-field__preview-open {
  position: absolute;
  right: 2px;
  bottom: 2px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  padding: 0;
  color: #fff;
  cursor: pointer;
  background: rgb(0 0 0 / 55%);
  border: none;
  border-radius: 50%;
  opacity: 0;
  transition: opacity 0.15s ease;
}

.attachment-field__preview-item:hover .attachment-field__preview-open,
.attachment-field__preview-open:focus-visible {
  opacity: 1;
}

.attachment-field__preview-open:hover {
  background: rgb(0 0 0 / 72%);
}

.attachment-field__preview-image {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.attachment-field__preview-file {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  background: hsl(var(--muted));
}
</style>
