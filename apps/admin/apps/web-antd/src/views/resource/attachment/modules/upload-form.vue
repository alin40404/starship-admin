<script lang="ts" setup>
import { ref } from 'vue';

import { useVbenModal } from '@vben/common-ui';

import { Form, message, Select, Upload } from 'ant-design-vue';

import {
  fetchAttachmentCategories,
  uploadAttachment,
} from '#/api/system/attachment';

const emits = defineEmits<{ success: [] }>();

const category = ref<string | undefined>();
const categoryOptions = ref<{ label: string; value: string }[]>([]);
const uploadingCount = ref(0);
const hasUploaded = ref(false);

async function loadCategories() {
  const rows = await fetchAttachmentCategories();
  categoryOptions.value = rows.map((item) => ({
    label: item.label,
    value: item.value,
  }));
}

async function onUpload(options: any) {
  uploadingCount.value += 1;
  try {
    await uploadAttachment(options?.file, category.value);
    hasUploaded.value = true;
    message.success('上传成功');
    options?.onSuccess?.({});
  } catch (error) {
    options?.onError?.(error);
  } finally {
    uploadingCount.value -= 1;
  }
}

function finishAndClose() {
  if (uploadingCount.value > 0) {
    message.warning('文件上传中，请稍候');
    return;
  }
  modalApi.close();
}

const [Modal, modalApi] = useVbenModal({
  animationType: 'scale',
  confirmText: '完成',
  fullscreenButton: false,
  onConfirm: finishAndClose,
  async onOpenChange(isOpen: boolean) {
    if (!isOpen) {
      if (hasUploaded.value) {
        emits('success');
      }
      category.value = undefined;
      hasUploaded.value = false;
      return;
    }
    await loadCategories();
    const data = modalApi.getData<{ category?: string }>();
    category.value = data?.category;
  },
});
</script>

<template>
  <Modal class="w-[560px] max-w-[92vw]" title="上传附件">
    <Form layout="vertical">
      <Form.Item label="上传分类">
        <Select
          v-model:value="category"
          allow-clear
          class="w-full"
          :options="categoryOptions"
          placeholder="请选择分类（可选）"
        />
      </Form.Item>
      <Form.Item label="选择文件">
        <Upload.Dragger
          :custom-request="onUpload"
          :disabled="uploadingCount > 0"
          :multiple="true"
          :show-upload-list="true"
        >
          <p class="text-muted-foreground py-4 text-sm">
            点击或拖拽文件到此区域上传，支持多文件
          </p>
        </Upload.Dragger>
      </Form.Item>
    </Form>
  </Modal>
</template>
