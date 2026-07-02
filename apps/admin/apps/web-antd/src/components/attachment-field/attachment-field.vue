<script lang="ts" setup>
import { computed, ref, useTemplateRef } from 'vue';

import { useAccess } from '@vben/access';

import { Button, Input, message } from 'ant-design-vue';

import { uploadAttachment } from '#/api/system/attachment';
import { ATTACHMENT_PERMISSIONS } from '#/constants/attachment-permissions';

import AttachmentPickerModal from './attachment-picker-modal.vue';
import type { AttachmentAcceptMode } from './types';
import {
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
</script>

<template>
  <div class="flex w-full min-w-0 items-center gap-2">
    <Input
      v-model:value="inputText"
      class="min-w-0 flex-1"
      :disabled="disabled"
      :placeholder="placeholder"
    />
    <Button
      :disabled="disabled || uploading"
      :loading="uploading"
      @click="onUploadClick"
    >
      上传
    </Button>
    <Button :disabled="disabled" @click="onSelectClick">选择</Button>
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
