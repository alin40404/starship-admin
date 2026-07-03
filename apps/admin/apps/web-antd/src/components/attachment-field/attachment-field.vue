<script lang="ts" setup>
import { computed, ref, useTemplateRef } from 'vue';

import { useAccess } from '@vben/access';
import { IconifyIcon } from '@vben/icons';

import { Button, Input, message, Space } from 'ant-design-vue';

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
    category?: string;
    disabled?: boolean;
    multiple?: boolean;
    placeholder?: string;
    value?: string | string[];
  }>(),
  {
    accept: 'all',
    category: undefined,
    disabled: false,
    multiple: false,
    placeholder: '请选择或上传附件',
    value: '',
  },
);

const emit = defineEmits<{
  'update:value': [value: string | string[]];
}>();

const { hasAccessByCodes } = useAccess();
const canUpload = computed(() =>
  hasAccessByCodes([ATTACHMENT_PERMISSIONS.UPLOAD]),
);

const pickerOpen = ref(false);
const uploading = ref(false);
const fileInputRef = useTemplateRef<HTMLInputElement>('fileInputRef');
const brokenImageUrls = ref<Set<string>>(new Set());

const currentUrls = computed(() => {
  const normalized = normalizeFieldValue(props.value, props.multiple);
  return Array.isArray(normalized) ? normalized : normalized ? [normalized] : [];
});

const inputText = computed({
  get() {
    return valueToInputText(props.value, props.multiple);
  },
  set(text: string) {
    emit('update:value', parseInputText(text, props.multiple));
  },
});

function emitValue(urls: string[]) {
  if (props.multiple) {
    emit('update:value', urls);
    return;
  }
  emit('update:value', urls[0] ?? '');
}

function onUploadClick() {
  if (props.disabled) {
    return;
  }
  if (!canUpload.value) {
    message.warning('暂无附件上传权限');
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
    message.warning('当前为单选模式，仅上传第一个文件');
  }

  const uploadFiles = props.multiple ? files : files.slice(0, 1);
  uploading.value = true;
  try {
    const uploadedUrls: string[] = [];
    for (const file of uploadFiles) {
      const row = await uploadAttachment(file, props.category);
      if (row.file_url) {
        uploadedUrls.push(row.file_url);
      }
    }
    if (uploadedUrls.length === 0) {
      message.error('上传失败，未获取到文件链接');
      return;
    }
    if (props.multiple) {
      emitValue([...currentUrls.value, ...uploadedUrls]);
    } else {
      emitValue([uploadedUrls[0]!]);
    }
    message.success(uploadedUrls.length > 1 ? '上传成功' : '上传成功');
  } catch {
    message.error('上传失败');
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
</script>

<template>
  <div class="attachment-field w-full min-w-0">
    <Space.Compact block class="attachment-field__compact w-full">
      <Input
        v-model:value="inputText"
        class="attachment-field__input min-w-0 flex-1"
        :disabled="disabled"
        :placeholder="placeholder"
      />
      <Button
        class="attachment-field__btn attachment-field__btn--upload"
        :disabled="disabled || uploading"
        :loading="uploading"
        size="small"
        @click="onUploadClick"
      >
        <IconifyIcon class="size-3.5" icon="lucide:upload" />
        <span>上传</span>
      </Button>
      <Button
        class="attachment-field__btn attachment-field__btn--select attachment-field__btn--select-last"
        :disabled="disabled"
        size="small"
        @click="onSelectClick"
      >
        <IconifyIcon class="size-3.5" icon="lucide:folder-open" />
        <span>选择</span>
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
          title="删除"
          type="button"
          @click="onRemoveUrl(url)"
        >
          <IconifyIcon class="size-3" icon="lucide:x" />
        </button>
      </div>
    </div>

    <input
      ref="fileInputRef"
      class="hidden"
      type="file"
      :accept="resolveUploadAccept(accept)"
      :disabled="disabled"
      :multiple="multiple"
      @change="onFileInputChange"
    />
  </div>

  <AttachmentPickerModal
    v-model:open="pickerOpen"
    :accept="accept"
    :initial-urls="currentUrls"
    :multiple="multiple"
    @confirm="onPickerConfirm"
  />
</template>

<style scoped>
.attachment-field__compact :deep(.ant-input) {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
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
  border-top-right-radius: 6px;
  border-bottom-right-radius: 6px;
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
  border-radius: 6px;
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
