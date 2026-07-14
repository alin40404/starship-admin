<script lang="ts" setup>
import type { GeneratorTableRow } from '#/features/generator/api';

import { computed, ref } from 'vue';

import { useVbenModal } from '@vben/common-ui';

import { Checkbox, message } from 'ant-design-vue';

import { generateCodegen } from '#/features/generator/api';
import { $t } from '#/locales';
import { useAuthStore } from '#/store';

const emit = defineEmits<{
  success: [];
}>();

const authStore = useAuthStore();

const generateTarget = ref<GeneratorTableRow | null>(null);
const generateForce = ref(false);
const generateSyncMenu = ref(true);

const modalTitle = computed(() =>
  generateTarget.value
    ? $t('dev.codegen.generateTitleWithName', [
        generateTarget.value.business_name,
      ])
    : $t('dev.codegen.generateTitle'),
);

const [Modal, modalApi] = useVbenModal({
  confirmText: $t('dev.codegen.generateStart'),
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
        message.warning($t('dev.codegen.conflictSkipped', [conflictCount]));
      } else {
        message.success($t('dev.codegen.generateDone', [writtenCount]));
        if (generateSyncMenu.value) {
          try {
            await authStore.refreshAccess();
            message.success($t('dev.codegen.refreshAccessDone'));
          } catch {
            message.warning($t('dev.codegen.refreshAccessFailed'));
          }
        }
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
      {{ $t('dev.codegen.generateHint') }}
    </p>
    <div class="space-y-2">
      <Checkbox v-model:checked="generateSyncMenu">
        {{ $t('dev.codegen.syncMenu') }}
      </Checkbox>
      <br />
      <Checkbox v-model:checked="generateForce">
        {{ $t('dev.codegen.forceOverwrite') }}
      </Checkbox>
    </div>
  </Modal>
</template>
