<script lang="ts" setup>
import type { GeneratorImportPayload } from '#/api/generator';
import type { MenuParentTreeOption } from '../menu-parent';

import { computed, ref, watch } from 'vue';

import { useVbenModal } from '@vben/common-ui';

import { Form, Input, message, Select, TreeSelect } from 'ant-design-vue';

import {
  fetchGeneratorModules,
  fetchSchemaTables,
  importGeneratorTable,
} from '#/api/generator';
import { fetchMenuOptionsTree } from '#/api/system/menu';
import { $t } from '#/locales';

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

const importHint = computed(() => $t('dev.codegen.importHint'));

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
    const tree = await fetchMenuOptionsTree();
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
  confirmText: $t('dev.codegen.importConfirm'),
  fullscreenButton: false,
  async onConfirm() {
    if (!form.value.table_name?.trim() || !form.value.module?.trim()) {
      message.warning($t('dev.codegen.selectTableModule'));
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
      message.success($t('dev.shared.importSuccess'));
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
    :title="$t('dev.codegen.importTitle')"
    class="w-full max-w-full md:w-[560px] md:max-w-[560px]"
  >
    <Form layout="vertical">
      <Form.Item :label="$t('dev.codegen.physicalTable')" required>
        <Select
          v-model:value="form.table_name"
          allow-clear
          :loading="loadingTables"
          :options="tableOptions"
          :placeholder="$t('dev.codegen.tableSearchPlaceholder')"
          show-search
          @search="searchTables"
        />
      </Form.Item>
      <Form.Item :label="$t('dev.codegen.targetModule')" required>
        <Select
          v-model:value="form.module"
          :options="modules.map((item) => ({ label: item, value: item }))"
          :placeholder="$t('dev.codegen.selectModule')"
        />
      </Form.Item>
      <Form.Item :label="$t('dev.codegen.menuParent')">
        <TreeSelect
          v-model:value="form.menu_parent_path"
          allow-clear
          class="w-full"
          :field-names="{ label: 'label', value: 'value', children: 'children' }"
          :loading="loadingMenuParents"
          :placeholder="$t('dev.codegen.menuParentPlaceholder')"
          show-search
          tree-default-expand-all
          :tree-data="menuParentOptions"
          tree-node-filter-prop="label"
        />
      </Form.Item>
      <Form.Item :label="$t('dev.codegen.businessName')">
        <Input
          v-model:value="form.business"
          allow-clear
          :placeholder="$t('dev.codegen.businessPlaceholder')"
        />
      </Form.Item>
      <p class="text-muted-foreground text-xs">
        {{ importHint }}
      </p>
    </Form>
  </Modal>
</template>
