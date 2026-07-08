<script lang="ts" setup>
import type { GeneratorImportPayload } from '#/api/generator';
import type { MenuParentTreeOption } from '../menu-parent';

import { ref, watch } from 'vue';

import { useVbenModal } from '@vben/common-ui';

import { Form, Input, message, Select, TreeSelect } from 'ant-design-vue';

import {
  fetchGeneratorModules,
  fetchSchemaTables,
  importGeneratorTable,
} from '#/api/generator';
import { fetchMenuTree } from '#/api/system/menu';

import {
  defaultMenuParentPath,
  mapMenuTreeForParentPathSelect,
  menuParentPathExists,
} from '../menu-parent';

const emit = defineEmits<{
  success: [];
}>();

const isOpen = ref(false);
const modules = ref<string[]>(['System']);
const tableOptions = ref<Array<{ label: string; value: string }>>([]);
const menuParentOptions = ref<MenuParentTreeOption[]>([]);
const loadingTables = ref(false);
const loadingMenuParents = ref(false);

const form = ref<GeneratorImportPayload>({
  table_name: '',
  module: 'System',
  business: '',
  menu_parent_path: undefined,
});

function applyDefaultMenuParent(module: string) {
  const suggested = defaultMenuParentPath(module);
  if (
    suggested &&
    menuParentPathExists(menuParentOptions.value, suggested)
  ) {
    form.value.menu_parent_path = suggested;
  } else {
    form.value.menu_parent_path = undefined;
  }
}

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

async function loadMenuParents() {
  loadingMenuParents.value = true;
  try {
    const tree = await fetchMenuTree();
    menuParentOptions.value = mapMenuTreeForParentPathSelect(tree);
  } catch {
    menuParentOptions.value = [];
  } finally {
    loadingMenuParents.value = false;
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

async function initForm() {
  form.value = {
    table_name: '',
    module: modules.value[0] ?? 'System',
    business: '',
    menu_parent_path: undefined,
  };
  await loadModules();
  await Promise.all([searchTables(''), loadMenuParents()]);
  applyDefaultMenuParent(form.value.module ?? 'System');
}

const [Modal, modalApi] = useVbenModal({
  confirmText: '导入',
  fullscreenButton: false,
  async onConfirm() {
    if (!form.value.table_name?.trim() || !form.value.module?.trim()) {
      message.warning('请选择数据表和目标模块');
      return;
    }
    modalApi.lock();
    try {
      await importGeneratorTable({
        table_name: form.value.table_name,
        module: form.value.module,
        business: form.value.business || undefined,
        menu_parent_path: form.value.menu_parent_path || undefined,
      });
      message.success('导入成功');
      modalApi.close();
      emit('success');
    } finally {
      modalApi.unlock();
    }
  },
  async onOpenChange(open) {
    isOpen.value = open;
    if (open) {
      await initForm();
    }
  },
});

watch(
  () => form.value.module,
  (module) => {
    if (!isOpen.value || !module) {
      return;
    }
    applyDefaultMenuParent(module);
  },
);

defineExpose({ open: () => modalApi.open() });
</script>

<template>
  <Modal
    title="导入数据表"
    class="w-full max-w-full md:w-[560px] md:max-w-[560px]"
  >
    <Form layout="vertical">
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
      <Form.Item label="菜单父级">
        <TreeSelect
          v-model:value="form.menu_parent_path"
          allow-clear
          class="w-full"
          :field-names="{ label: 'label', value: 'value', children: 'children' }"
          :loading="loadingMenuParents"
          placeholder="留空则按 route_group 自动推断"
          show-search
          tree-default-expand-all
          :tree-data="menuParentOptions"
          tree-node-filter-prop="label"
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
        导入后将读取表结构并推断字段配置；菜单父级对应 CLI
        <code>--menu-parent</code>，与
        <code>generator:import</code> 效果一致。
      </p>
    </Form>
  </Modal>
</template>
