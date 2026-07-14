<script lang="ts" setup>
import type { CodegenFileItem, GeneratorTableRow } from '#/api/generator';

import { computed, ref } from 'vue';

import { Drawer, Empty, Spin } from 'ant-design-vue';

import { previewCodegen } from '#/api/generator';
import { $t } from '#/locales';

const open = ref(false);
const files = ref<CodegenFileItem[]>([]);
const activePath = ref('');
const loading = ref(false);
const title = ref('');

const activeFile = computed(() =>
  files.value.find((file) => file.path === activePath.value),
);

async function show(row: GeneratorTableRow) {
  title.value = $t('dev.codegen.previewTitleWithName', [row.business_name]);
  open.value = true;
  loading.value = true;
  files.value = [];
  activePath.value = '';
  void previewCodegen(row.id)
    .then((result) => {
      files.value = result.files ?? [];
      activePath.value = files.value[0]?.path ?? '';
    })
    .finally(() => {
      loading.value = false;
    });
}

defineExpose({ open: show });
</script>

<template>
  <Drawer v-model:open="open" :title="title" width="min(90vw, 1200px)">
    <Spin :spinning="loading">
      <div v-if="files.length > 0" class="flex h-[calc(100vh-140px)] min-h-[480px] gap-4">
        <div class="w-72 shrink-0 overflow-auto rounded border p-2">
          <button
            v-for="file in files"
            :key="file.path"
            class="mb-1 block w-full truncate rounded px-2 py-1 text-left text-xs hover:bg-accent"
            :class="activePath === file.path ? 'bg-accent font-medium' : ''"
            type="button"
            @click="activePath = file.path"
          >
            {{ file.path }}
          </button>
        </div>
        <pre
          class="min-w-0 flex-1 overflow-auto rounded border bg-muted/30 p-3 text-xs leading-5"
        >{{ activeFile?.content }}</pre>
      </div>
      <Empty v-else-if="!loading" :description="$t('dev.codegen.previewEmpty')" />
    </Spin>
  </Drawer>
</template>
