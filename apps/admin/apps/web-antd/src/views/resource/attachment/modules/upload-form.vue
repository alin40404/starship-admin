<script lang="ts" setup>
import { ref } from 'vue';

import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { usePreferences } from '@vben/preferences';

import { Form, message, Upload } from 'ant-design-vue';

import { uploadAttachment } from '#/api/system/attachment';

const emits = defineEmits<{ success: [] }>();

const { isMobile } = usePreferences();

const uploadingCount = ref(0);
const hasUploaded = ref(false);

async function onUpload(options: any) {
  uploadingCount.value += 1;
  try {
    await uploadAttachment(options?.file);
    hasUploaded.value = true;
    message.success('上传成功');
    options?.onSuccess?.({});
  } catch (error) {
    options?.onError?.(error);
  } finally {
    uploadingCount.value -= 1;
  }
}

type PopupApi = {
  close: () => void;
  open: () => void;
};

function finishAndClose(popupApi: PopupApi) {
  if (uploadingCount.value > 0) {
    message.warning('文件上传中，请稍候');
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
  }
}

const [Modal, modalApi] = useVbenModal({
  animationType: 'scale',
  confirmText: '完成',
  destroyOnClose: true,
  fullscreenButton: false,
  onConfirm() {
    finishAndClose(modalApi as PopupApi);
  },
  onOpenChange: handleOpenChange,
});

const [Drawer, drawerApi] = useVbenDrawer({
  confirmText: '完成',
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
    title="上传附件"
  >
    <Form layout="vertical">
      <Form.Item label="选择文件">
        <Upload.Dragger
          :custom-request="onUpload"
          :disabled="uploadingCount > 0"
          :multiple="true"
          :show-upload-list="true"
        >
          <p class="text-muted-foreground py-4 text-sm">
            点击或拖拽文件到此区域上传，支持多文件；分类将由系统按文件格式自动识别
          </p>
        </Upload.Dragger>
      </Form.Item>
    </Form>
  </Modal>
  <Drawer v-else class="w-full" title="上传附件">
    <Form layout="vertical">
      <Form.Item label="选择文件">
        <Upload.Dragger
          :custom-request="onUpload"
          :disabled="uploadingCount > 0"
          :multiple="true"
          :show-upload-list="true"
        >
          <p class="text-muted-foreground py-4 text-sm">
            点击或拖拽文件到此区域上传，支持多文件；分类将由系统按文件格式自动识别
          </p>
        </Upload.Dragger>
      </Form.Item>
    </Form>
  </Drawer>
</template>
