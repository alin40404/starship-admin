<script lang="ts" setup>
import type { UploadChangeParam, UploadFile } from 'ant-design-vue/es/upload';

import { computed, onBeforeUnmount, ref } from 'vue';

import { IconifyIcon } from '@vben/icons';
import { $t } from '@vben/locales';

import { Button, Upload } from 'ant-design-vue';

import AttachmentPickerModal from '#/components/attachment-field/attachment-picker-modal.vue';
import { resolveUploadAcceptAttr } from '#/utils/upload-policy';

import type { PictureUploadFieldProps } from './types';

defineOptions({ name: 'PictureUploadField' });

const props = withDefaults(defineProps<PictureUploadFieldProps>(), {
  accept: 'image',
  disabled: false,
  fileList: () => [],
});

const emit = defineEmits<{
  'update:fileList': [fileList: UploadFile[]];
}>();

const pickerOpen = ref(false);

onBeforeUnmount(() => {
  pickerOpen.value = false;
});

const innerFileList = computed({
  get: () => props.fileList ?? [],
  set: (value) => emit('update:fileList', value),
});

const canAddMore = computed(
  () => !props.maxCount || innerFileList.value.length < props.maxCount,
);

const pickerMultiple = computed(() => props.maxCount !== 1);

const pickerInitialUrls = computed(() =>
  innerFileList.value
    .map((file) => file.url ?? file.response?.file_url)
    .filter((url): url is string => !!url)
    .map(String),
);

function onUploadChange(event: UploadChangeParam) {
  props.handleChange?.(event);
  innerFileList.value = event.fileList.filter((file) => file.status !== 'removed');
}

function onSelectClick() {
  if (props.disabled || !canAddMore.value) {
    return;
  }
  pickerOpen.value = true;
}

function buildPickedFile(url: string, index: number): UploadFile {
  return {
    name: `image-${index}`,
    status: 'done',
    uid: `image-picked-${Date.now()}-${index}`,
    url,
  };
}

function onPickerConfirm(urls: string[]) {
  const pickedUrls = urls.map((url) => url.trim()).filter(Boolean);
  if (pickedUrls.length === 0) {
    return;
  }

  if (props.maxCount === 1) {
    innerFileList.value = [buildPickedFile(pickedUrls[0]!, 0)];
    pickerOpen.value = false;
    return;
  }

  const existingUrls = new Set(
    innerFileList.value
      .map((file) => file.url ?? file.response?.file_url)
      .filter((url): url is string => !!url)
      .map(String),
  );
  const newFiles = pickedUrls
    .filter((url) => !existingUrls.has(url))
    .map((url, index) => buildPickedFile(url, index));

  let merged = [...innerFileList.value, ...newFiles];
  if (props.maxCount && merged.length > props.maxCount) {
    merged = merged.slice(0, props.maxCount);
  }
  innerFileList.value = merged;
  pickerOpen.value = false;
}
</script>

<template>
  <div class="picture-upload-field">
    <Upload
      :accept="resolveUploadAcceptAttr(accept)"
      class="picture-card-upload picture-upload-field__upload shrink-0"
      :custom-request="customRequest"
      :disabled="disabled"
      :file-list="innerFileList"
      list-type="picture-card"
      :max-count="maxCount"
      @change="onUploadChange"
    >
      <div v-if="canAddMore && !disabled">
        {{ $t('ui.placeholder.upload') }}
      </div>
    </Upload>

    <Button
      v-if="canAddMore && !disabled"
      type="primary"
      class="picture-upload-field__select-btn"
      @click="onSelectClick"
    >
      <IconifyIcon
        class="picture-upload-field__select-btn-icon"
        icon="lucide:folder-open"
      />
      <span class="picture-upload-field__select-btn-text">
        {{ $t('resource.attachment.picker.title') }}
      </span>
    </Button>

    <AttachmentPickerModal
      v-model:open="pickerOpen"
      :accept="accept"
      :initial-urls="pickerInitialUrls"
      :multiple="pickerMultiple"
      @confirm="onPickerConfirm"
    />
  </div>
</template>
