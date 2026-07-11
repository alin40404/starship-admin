<script lang="ts" setup>
import type { UploadFile } from 'ant-design-vue';

import { ref } from 'vue';

import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { IconifyIcon } from '@vben/icons';
import { $t } from '@vben/locales';
import { usePreferences } from '@vben/preferences';

import { message, Progress, Upload } from 'ant-design-vue';

import { uploadAttachment } from '#/api/system/attachment';

const emits = defineEmits<{ success: [] }>();

const { isMobile } = usePreferences();

const uploadingCount = ref(0);
const hasUploaded = ref(false);
const fileList = ref<UploadFile[]>([]);
const focusingUid = ref<null | string>(null);

async function onUpload(options: any) {
  uploadingCount.value += 1;
  try {
    await uploadAttachment(options?.file);
    hasUploaded.value = true;
    message.success($t('resource.attachment.field.uploadSuccess'));
    options?.onSuccess?.({});
  } catch (error) {
    options?.onError?.(error);
  } finally {
    uploadingCount.value -= 1;
  }
}

function removeFile(file: UploadFile) {
  fileList.value = fileList.value.filter((item) => item.uid !== file.uid);
  if (focusingUid.value === file.uid) {
    focusingUid.value = null;
  }
}

function handleFileBlur(file: UploadFile) {
  if (focusingUid.value === file.uid) {
    focusingUid.value = null;
  }
}

function handleFileKeydown(event: KeyboardEvent, file: UploadFile) {
  if (event.key === 'Delete' || event.key === 'Backspace') {
    event.preventDefault();
    removeFile(file);
  }
}

type PopupApi = {
  close: () => void;
  open: () => void;
};

function finishAndClose(popupApi: PopupApi) {
  if (uploadingCount.value > 0) {
    message.warning($t('resource.attachment.upload.uploadingWait'));
    return;
  }
  popupApi.close();
}

function handleOpenChange(isOpen: boolean) {
  if (!isOpen) {
    if (hasUploaded.value) {
      emits('success');
    }
    hasUploaded.value = false;
    fileList.value = [];
    focusingUid.value = null;
  }
}

const [Modal, modalApi] = useVbenModal({
  animationType: 'scale',
  confirmText: $t('resource.attachment.upload.done'),
  destroyOnClose: true,
  fullscreenButton: false,
  onConfirm() {
    finishAndClose(modalApi as PopupApi);
  },
  onOpenChange: handleOpenChange,
});

const [Drawer, drawerApi] = useVbenDrawer({
  confirmText: $t('resource.attachment.upload.done'),
  destroyOnClose: true,
  onConfirm() {
    finishAndClose(drawerApi as PopupApi);
  },
  onOpenChange: handleOpenChange,
});

function getActivePopupApi() {
  return (isMobile.value ? drawerApi : modalApi) as PopupApi;
}

function open() {
  getActivePopupApi().open();
}

defineExpose({ open });
</script>

<template>
  <Modal
    v-if="!isMobile"
    class="w-full max-w-full md:w-[560px] md:max-w-[560px]"
    :title="$t('resource.attachment.upload.title')"
  >
    <div class="attachment-upload-panel">
      <Upload.Dragger
        v-model:file-list="fileList"
        class="attachment-upload-dragger"
        :custom-request="onUpload"
        :disabled="uploadingCount > 0"
        :multiple="true"
        :show-upload-list="false"
      >
        <div class="text-muted-foreground px-4 py-4 text-sm">
          {{ $t('resource.attachment.upload.draggerHint') }}
        </div>
      </Upload.Dragger>

      <ul v-if="fileList.length" class="attachment-upload-list">
        <li
          v-for="file in fileList"
          :key="file.uid"
          class="attachment-upload-list__item"
          :class="{
            'is-error': file.status === 'error',
            'is-focus': focusingUid === file.uid,
          }"
          tabindex="0"
          @blur="handleFileBlur(file)"
          @focus="focusingUid = file.uid"
          @keydown="handleFileKeydown($event, file)"
        >
          <IconifyIcon
            class="attachment-upload-list__file-icon"
            icon="lucide:paperclip"
          />

          <span
            class="attachment-upload-list__name"
            :class="{ 'attachment-upload-list__name--error': file.status === 'error' }"
            :title="file.name"
          >
            {{ file.name }}
          </span>

          <Progress
            v-if="file.status === 'uploading'"
            class="attachment-upload-list__progress"
            :percent="file.percent || 0"
            :show-info="false"
            size="small"
          />

          <span
            v-else-if="file.status === 'error'"
            class="attachment-upload-list__error-tip"
          >
            {{ $t('resource.attachment.upload.failed') }}
          </span>

          <span
            v-else-if="focusingUid === file.uid"
            class="attachment-upload-list__delete-tip"
          >
            {{ $t('resource.attachment.upload.deleteKeyHint') }}
          </span>

          <IconifyIcon
            v-else-if="file.status === 'done'"
            class="attachment-upload-list__success-icon"
            icon="lucide:circle-check"
          />

          <button
            v-if="file.status !== 'uploading'"
            class="attachment-upload-list__remove-btn"
            :title="$t('resource.attachment.field.remove')"
            type="button"
            @click="removeFile(file)"
          >
            <IconifyIcon class="size-3.5" icon="lucide:x" />
          </button>
        </li>
      </ul>
    </div>
  </Modal>

  <Drawer v-else class="w-full" :title="$t('resource.attachment.upload.title')">
    <div class="attachment-upload-panel">
      <Upload.Dragger
        v-model:file-list="fileList"
        class="attachment-upload-dragger"
        :custom-request="onUpload"
        :disabled="uploadingCount > 0"
        :multiple="true"
        :show-upload-list="false"
      >
        <div class="text-muted-foreground px-4 py-4 text-sm">
          {{ $t('resource.attachment.upload.draggerHint') }}
        </div>
      </Upload.Dragger>

      <ul v-if="fileList.length" class="attachment-upload-list">
        <li
          v-for="file in fileList"
          :key="file.uid"
          class="attachment-upload-list__item"
          :class="{
            'is-error': file.status === 'error',
            'is-focus': focusingUid === file.uid,
          }"
          tabindex="0"
          @blur="handleFileBlur(file)"
          @focus="focusingUid = file.uid"
          @keydown="handleFileKeydown($event, file)"
        >
          <IconifyIcon
            class="attachment-upload-list__file-icon"
            icon="lucide:paperclip"
          />

          <span
            class="attachment-upload-list__name"
            :class="{ 'attachment-upload-list__name--error': file.status === 'error' }"
            :title="file.name"
          >
            {{ file.name }}
          </span>

          <Progress
            v-if="file.status === 'uploading'"
            class="attachment-upload-list__progress"
            :percent="file.percent || 0"
            :show-info="false"
            size="small"
          />

          <span
            v-else-if="file.status === 'error'"
            class="attachment-upload-list__error-tip"
          >
            {{ $t('resource.attachment.upload.failed') }}
          </span>

          <span
            v-else-if="focusingUid === file.uid"
            class="attachment-upload-list__delete-tip"
          >
            {{ $t('resource.attachment.upload.deleteKeyHint') }}
          </span>

          <IconifyIcon
            v-else-if="file.status === 'done'"
            class="attachment-upload-list__success-icon"
            icon="lucide:circle-check"
          />

          <button
            v-if="file.status !== 'uploading'"
            class="attachment-upload-list__remove-btn"
            :title="$t('resource.attachment.field.remove')"
            type="button"
            @click="removeFile(file)"
          >
            <IconifyIcon class="size-3.5" icon="lucide:x" />
          </button>
        </li>
      </ul>
    </div>
  </Drawer>
</template>

<style scoped>
.attachment-upload-panel {
  display: flex;
  flex-direction: column;
}

.attachment-upload-dragger {
  display: block;
  flex: 0 0 auto;
  height: auto;
}

.attachment-upload-dragger :deep(.ant-upload-drag) {
  display: flex;
  align-items: center;
  justify-content: center;
  box-sizing: border-box;
  height: 130px;
  min-height: 130px;
  max-height: 130px;
  padding: 0;
  border: 1px dashed hsl(var(--border));
  border-radius: var(--radius);
  background-color: hsl(var(--card));
  transition:
    border-color 0.2s ease,
    background-color 0.2s ease;
}

/* 拖入有效区域：加粗蓝色虚线边框 + 浅蓝背景 */
.attachment-upload-dragger
  :deep(.ant-upload-drag.ant-upload-drag-hover:not(.ant-upload-disabled)) {
  border: 2px dashed hsl(var(--primary)) !important;
  background-color: color-mix(in oklch, hsl(var(--primary)) 10%, hsl(var(--card))) !important;
}

.attachment-upload-dragger :deep(.ant-upload-drag .ant-upload-btn) {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  min-height: 130px;
  padding: 40px 16px;
}

.attachment-upload-dragger :deep(.ant-upload-drag .ant-upload-btn > :not(input)) {
  pointer-events: none;
}

.attachment-upload-dragger :deep(.ant-upload-drag .ant-upload-btn input[type='file']) {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  cursor: pointer;
}

.attachment-upload-list {
  margin: 8px 0 0;
  padding: 0;
  list-style: none;
}

.attachment-upload-list__item {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  min-height: 26px;
  padding: 4px;
  border-radius: 4px;
  outline: none;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.attachment-upload-list__item + .attachment-upload-list__item {
  margin-top: 4px;
}

.attachment-upload-list__item:hover,
.attachment-upload-list__item.is-focus {
  background: hsl(var(--accent));
}

.attachment-upload-list__file-icon {
  flex-shrink: 0;
  width: 14px;
  height: 14px;
  color: hsl(var(--muted-foreground));
}

.attachment-upload-list__name {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 14px;
  line-height: 1.5715;
  color: hsl(var(--foreground));
}

.attachment-upload-list__name--error {
  color: hsl(var(--destructive));
}

.attachment-upload-list__progress {
  flex: 0 1 120px;
  min-width: 72px;
  margin: 0;
}

.attachment-upload-list__progress :deep(.ant-progress-inner) {
  margin-bottom: 0;
}

.attachment-upload-list__error-tip,
.attachment-upload-list__delete-tip {
  flex-shrink: 0;
  font-size: 12px;
  line-height: 1;
  color: hsl(var(--muted-foreground));
}

.attachment-upload-list__error-tip {
  color: hsl(var(--destructive));
}

.attachment-upload-list__success-icon {
  flex-shrink: 0;
  width: 14px;
  height: 14px;
  color: #67c23a;
}

.attachment-upload-list__item:hover .attachment-upload-list__success-icon,
.attachment-upload-list__item.is-focus .attachment-upload-list__success-icon {
  display: none;
}

.attachment-upload-list__remove-btn {
  display: none;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
  padding: 0;
  border: 0;
  border-radius: 2px;
  color: hsl(var(--muted-foreground));
  background: transparent;
  cursor: pointer;
  transition: color 0.2s ease;
}

.attachment-upload-list__item:hover .attachment-upload-list__remove-btn,
.attachment-upload-list__item.is-focus .attachment-upload-list__remove-btn {
  display: inline-flex;
}

.attachment-upload-list__remove-btn:hover {
  color: hsl(var(--foreground));
}
</style>
