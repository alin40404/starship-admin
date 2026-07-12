<script lang="ts" setup>
import type { UploadChangeParam, UploadFile } from 'ant-design-vue/es/upload';

import { computed, onBeforeUnmount, ref } from 'vue';

import { IconifyIcon } from '@vben/icons';
import { $t } from '@vben/locales';

import { Upload } from 'ant-design-vue';

import AttachmentPickerModal from '#/components/attachment-field/attachment-picker-modal.vue';
import { resolveUploadAcceptAttr } from '#/utils/upload-policy';

import type { AvatarUploadFieldProps } from './types';

defineOptions({ name: 'AvatarUploadField' });

const props = withDefaults(defineProps<AvatarUploadFieldProps>(), {
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

const pickerInitialUrls = computed(() => {
  const file = innerFileList.value[0];
  const url = file?.url ?? file?.response?.file_url;
  return url ? [String(url)] : [];
});

function onUploadChange(event: UploadChangeParam) {
  props.handleChange?.(event);
  innerFileList.value = event.fileList.filter((file) => file.status !== 'removed');
}

function onSelectClick() {
  if (props.disabled) {
    return;
  }
  pickerOpen.value = true;
}

function onPickerConfirm(urls: string[]) {
  const url = urls[0]?.trim();
  if (!url) {
    return;
  }
  innerFileList.value = [
    {
      name: 'avatar',
      status: 'done',
      uid: `avatar-picked-${Date.now()}`,
      url,
    },
  ];
  pickerOpen.value = false;
}
</script>

<template>
  <div class="avatar-upload-field">
    <Upload
      :accept="resolveUploadAcceptAttr('image')"
      class="avatar-picture-card-upload avatar-upload-field__upload shrink-0"
      :custom-request="customRequest"
      :disabled="disabled"
      :file-list="innerFileList"
      list-type="picture-card"
      :max-count="1"
      @change="onUploadChange"
    >
      <div v-if="innerFileList.length < 1 && !disabled">
        {{ $t('ui.placeholder.upload') }}
      </div>
    </Upload>

    <button
      v-if="!disabled"
      type="button"
      class="avatar-upload-field__select-card"
      @click="onSelectClick"
    >
      <IconifyIcon
        class="avatar-upload-field__select-card-icon"
        icon="lucide:folder-open"
      />
      <span class="avatar-upload-field__select-card-text">
        {{ $t('resource.attachment.picker.title') }}
      </span>
    </button>

    <AttachmentPickerModal
      v-if="pickerOpen"
      accept="image"
      :initial-urls="pickerInitialUrls"
      :multiple="false"
      :open="pickerOpen"
      @confirm="onPickerConfirm"
      @update:open="pickerOpen = $event"
    />
  </div>
</template>
