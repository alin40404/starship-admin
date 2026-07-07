<script lang="ts" setup>
import type { GeneratorImportPayload } from '#/api/generator';

import { ref } from 'vue';

import { Form, Input, message, Modal, Select } from 'ant-design-vue';

import {
  fetchGeneratorModules,
  fetchSchemaTables,
  importGeneratorTable,
} from '#/api/generator';

const emit = defineEmits<{
  success: [];
}>();

const open = ref(false);
const submitting = ref(false);
const modules = ref<string[]>(['System']);
const tableOptions = ref<Array<{ label: string; value: string }>>([]);
const loadingTables = ref(false);

const form = ref<GeneratorImportPayload>({
  table_name: '',
  module: 'System',
  business: '',
});

async function loadModules() {
  try {
    modules.value = await fetchGeneratorModules();
  } catch {
    modules.value = ['System'];
  }
  if (!form.value.module) {
    form.value.module = modules.value[0] ?? 'System';
  }
}

async function searchTables(keyword: string) {
  loadingTables.value = true;
  try {
    const items = await fetchSchemaTables(keyword || undefined);
    tableOptions.value = items.map((item) => ({
      label: item.comment ? `${item.name}（${item.comment}）` : item.name,
      value: item.name,
    }));
  } finally {
    loadingTables.value = false;
  }
}

async function show() {
  form.value = {
    table_name: '',
    module: modules.value[0] ?? 'System',
    business: '',
  };
  open.value = true;
  await loadModules();
  await searchTables('');
}

async function handleOk() {
  if (!form.value.table_name?.trim() || !form.value.module?.trim()) {
    message.warning('请选择数据表和目标模块');
    return;
  }
  submitting.value = true;
  try {
    await importGeneratorTable({
      table_name: form.value.table_name,
      module: form.value.module,
      business: form.value.business || undefined,
    });
    message.success('导入成功');
    open.value = false;
    emit('success');
  } finally {
    submitting.value = false;
  }
}

defineExpose({ open: show });
</script>

<template>
  <Modal
    v-model:open="open"
    :confirm-loading="submitting"
    ok-text="导入"
    title="导入数据表"
    width="560px"
    @ok="handleOk"
  >
    <Form layout="vertical" class="mt-2">
      <Form.Item label="物理表" required>
        <Select
          v-model:value="form.table_name"
          allow-clear
          :loading="loadingTables"
          :options="tableOptions"
          placeholder="搜索并选择数据表"
          show-search
          @search="searchTables"
        />
      </Form.Item>
      <Form.Item label="目标模块" required>
        <Select
          v-model:value="form.module"
          :options="modules.map((item) => ({ label: item, value: item }))"
          placeholder="选择模块"
        />
      </Form.Item>
      <Form.Item label="业务名称">
        <Input
          v-model:value="form.business"
          allow-clear
          placeholder="留空则使用表注释或类名"
        />
      </Form.Item>
      <p class="text-muted-foreground text-xs">
        导入后将读取表结构并推断字段配置，与 CLI
        <code>generator:import</code> 效果一致。
      </p>
    </Form>
  </Modal>
</template>
