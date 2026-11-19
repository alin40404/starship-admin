<script lang="ts" setup>
import type { UploadChangeParam, UploadFile } from 'ant-design-vue/es/upload';

import { computed, onBeforeUnmount, ref } from 'vue';

import { IconifyIcon } from '@vben/icons';
import { $t } from '@vben/locales';

import { Button, Upload } from 'ant-design-vue';

import AttachmentPickerModal from '#/components/attachment-field/attachment-picker-modal.vue';
import { resolveUploadAcceptAttr } from '#/utils/upload-policy';

import type { PictureUploadFieldProps } from './types';

defineOptions({ inheritAttrs: false, name: 'PictureUploadField' });

const props = withDefaults(defineProps<PictureUploadFieldProps>(), {
  accept: 'image',
  disabled: false,
  fileList: () => [],
});

const emit = defineEmits<{
  blur: [];
  change: [value?: unknown];
  input: [value?: unknown];
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

/** 单选已选中时仍可打开选择器替换；多选达上限则隐藏 */
const canSelectAttachment = computed(
  () => !props.disabled && (canAddMore.value || props.maxCount === 1),
);

/** 多图/相册：流式换行；单图：封面等横向紧凑布局 */
const isFlowLayout = computed(
  () => props.flowLayout === true || props.maxCount !== 1,
);

/** 有图片时启用 grid 流式；空态与封面图相同 flex 并排 */
const isFlowGrid = computed(
  () => isFlowLayout.value && innerFileList.value.length > 0,
);

const pickerMultiple = computed(() => props.maxCount !== 1);

const pickerInitialUrls = computed(() =>
  innerFileList.value
    .map((file) => file.url ?? file.response?.file_url)
    .filter((url): url is string => !!url)
    .map(String),
);

function resolveFilePreviewUrl(file: UploadFile): string | undefined {
  const response = file.response as { file_url?: string } | undefined;
  const url = file.url ?? file.thumbUrl ?? response?.file_url;
  return url ? String(url) : undefined;
}

function normalizeUploadFileList(files: UploadFile[]): UploadFile[] {
  return files
    .filter((file) => file.status !== 'removed')
    .map((file) => {
      const url = resolveFilePreviewUrl(file);
      if (!url || file.url === url) {
        return file;
      }
      return {
        ...file,
        thumbUrl: file.thumbUrl ?? url,
        url,
      };
    });
}

function onUploadChange(event: UploadChangeParam) {
  props.handleChange?.(event);
  innerFileList.value = normalizeUploadFileList(event.fileList);
  emit('change', event);
  emit('input', event);
}

function onSelectClick() {
  if (!canSelectAttachment.value) {
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
    emit('change', innerFileList.value);
    emit('input', innerFileList.value);
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
  emit('change', merged);
  emit('input', merged);
}
</script>

<template>
  <div
    class="picture-upload-field"
    :class="{
      'picture-upload-field--flow': isFlowLayout,
      'picture-upload-field--flow-grid': isFlowGrid,
    }"
  >
    <div class="picture-upload-field__body">
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
        v-if="canSelectAttachment"
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
    </div>

    <AttachmentPickerModal
      v-model:open="pickerOpen"
      :accept="accept"
      :initial-urls="pickerInitialUrls"
      :multiple="pickerMultiple"
      @confirm="onPickerConfirm"
    />
  </div>
</template>
