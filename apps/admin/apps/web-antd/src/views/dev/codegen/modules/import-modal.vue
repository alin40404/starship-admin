<script lang="ts" setup>
import type { GeneratorImportPayload } from '#/api/generator';
import type { MenuParentTreeOption } from '../menu-parent';

import { ref } from 'vue';

import { useVbenModal } from '@vben/common-ui';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import {
  fetchGeneratorModules,
  fetchSchemaTables,
  importGeneratorTable,
} from '#/api/generator';
import { fetchMenuOptionsTree } from '#/api/system/menu';
import { $t } from '#/locales';

import {
  buildImportFormSchema,
  formatModuleSlug,
  toModuleSelectOption,
  type BuildImportFormSchemaParams,
} from '../data';
import {
  defaultMenuParentPath,
  mapMenuTreeForParentPathSelect,
  menuParentPathExists,
} from '../menu-parent';

const emit = defineEmits<{
  success: [];
}>();

const modules = ref<string[]>(['System']);
const tableOptions = ref<Array<{ label: string; value: string }>>([]);
const menuParentOptions = ref<MenuParentTreeOption[]>([]);
const loadingTables = ref(false);
const loadingMenuParents = ref(false);

function createSchemaParams(): BuildImportFormSchemaParams {
  return {
    tableOptions: tableOptions.value,
    moduleOptions: modules.value.map(toModuleSelectOption),
    loadingTables: loadingTables.value,
    loadingMenuParents: loadingMenuParents.value,
    getMenuParentTree: () => menuParentOptions.value,
    onTableSearch: searchTables,
  };
}

function syncSchema() {
  formApi.setState({ schema: buildImportFormSchema(createSchemaParams()) });
}

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: buildImportFormSchema({
    tableOptions: [],
    moduleOptions: [{ label: 'System', value: 'System' }],
    loadingTables: false,
    loadingMenuParents: false,
    getMenuParentTree: () => [],
    onTableSearch: () => {},
  }),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 gap-y-1',
});

async function loadModules() {
  try {
    modules.value = await fetchGeneratorModules();
  } catch {
    modules.value = ['System'];
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
  syncSchema();
  try {
    const items = await fetchSchemaTables(keyword || undefined);
    tableOptions.value = items.map((item) => ({
      label: item.comment ? `${item.name}（${item.comment}）` : item.name,
      value: item.name,
    }));
  } finally {
    loadingTables.value = false;
    syncSchema();
  }
}

function resolveDefaultMenuParent(module: string): string | undefined {
  const suggested = defaultMenuParentPath(module);
  if (
    suggested &&
    menuParentPathExists(menuParentOptions.value, suggested)
  ) {
    return suggested;
  }
  return undefined;
}

async function initForm() {
  formApi.resetForm();
  await loadModules();
  await Promise.all([searchTables(''), loadMenuParents()]);
  syncSchema();

  const module = formatModuleSlug(modules.value[0] ?? 'System');
  formApi.setValues({
    table_name: undefined,
    module,
    business: '',
    menu_parent_path: resolveDefaultMenuParent(module),
  });
}

const [Modal, modalApi] = useVbenModal({
  confirmText: $t('dev.codegen.importConfirm'),
  destroyOnClose: true,
  fullscreenButton: false,
  async onConfirm() {
    const { valid } = await formApi.validate();
    if (!valid) {
      return;
    }

    const values = (await formApi.getValues()) as GeneratorImportPayload;
    if (!values.table_name?.trim() || !values.module?.trim()) {
      message.warning($t('dev.codegen.selectTableModule'));
      return;
    }

    modalApi.lock();
    try {
      await importGeneratorTable({
        table_name: values.table_name,
        module: values.module,
        business: values.business || undefined,
        menu_parent_path: values.menu_parent_path || undefined,
      });
      message.success($t('dev.shared.importSuccess'));
      modalApi.close();
      emit('success');
    } finally {
      modalApi.unlock();
    }
  },
  async onOpenChange(open) {
    if (open) {
      await initForm();
    }
  },
});

defineExpose({ open: () => modalApi.open() });
</script>

<template>
  <Modal
    :title="$t('dev.codegen.importTitle')"
    class="w-full max-w-full md:w-[560px] md:max-w-[560px]"
  >
    <Form />
    <p class="text-muted-foreground mt-2 text-sm">
      {{ $t('dev.codegen.importHint') }}
    </p>
  </Modal>
</template>
