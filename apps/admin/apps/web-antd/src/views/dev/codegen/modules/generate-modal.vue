<script lang="ts" setup>
import type { GeneratorTableRow } from '#/api/generator';

import { computed, ref } from 'vue';

import { useVbenModal } from '@vben/common-ui';

import { Checkbox, message } from 'ant-design-vue';

import { generateCodegen } from '#/api/generator';

const emit = defineEmits<{
  success: [];
}>();

const generateTarget = ref<GeneratorTableRow | null>(null);
const generateForce = ref(false);
const generateSyncMenu = ref(true);

const modalTitle = computed(() =>
  generateTarget.value
    ? `生成 · ${generateTarget.value.business_name}`
    : '执行生成',
);

const [Modal, modalApi] = useVbenModal({
  confirmText: '开始生成',
  fullscreenButton: false,
  async onConfirm() {
    if (!generateTarget.value) {
      return;
    }
    modalApi.lock();
    try {
      const result = await generateCodegen(generateTarget.value.id, {
        force: generateForce.value,
        sync_menu: generateSyncMenu.value,
      });
      const conflictCount = result.conflicts?.length ?? 0;
      const writtenCount = result.written?.length ?? 0;
      if (conflictCount > 0 && !generateForce.value) {
        message.warning(
          `有 ${conflictCount} 个文件已存在被跳过，可勾选强制覆盖后重试`,
        );
      } else {
        message.success(`生成完成，写入 ${writtenCount} 个文件`);
        modalApi.close();
      }
      emit('success');
    } finally {
      modalApi.unlock();
    }
  },
  onOpenChange(isOpen) {
    if (isOpen) {
      generateForce.value = false;
      generateSyncMenu.value = true;
    }
  },
});

defineExpose({
  open(row: GeneratorTableRow) {
    generateTarget.value = row;
    modalApi.open();
  },
});
</script>

<template>
  <Modal
    :title="modalTitle"
    class="w-full max-w-full md:w-[520px] md:max-w-[520px]"
  >
    <p class="mb-3 text-sm">
      将写入后端/前端代码文件，效果与 CLI <code>generator:crud</code> 一致。
    </p>
    <div class="space-y-2">
      <Checkbox v-model:checked="generateSyncMenu">同步注册菜单</Checkbox>
      <br />
      <Checkbox v-model:checked="generateForce">强制覆盖已存在文件</Checkbox>
    </div>
  </Modal>
</template>
