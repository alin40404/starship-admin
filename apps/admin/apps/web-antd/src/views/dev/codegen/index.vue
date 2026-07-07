<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { GeneratorTableRow } from '#/api/generator';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { Button, Checkbox, message, Modal } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  deleteGeneratorTable,
  fetchGeneratorTablePage,
  generateCodegen,
} from '#/api/generator';
import { GENERATOR_PERMISSIONS } from '#/constants/generator-permissions';

import { useColumns } from './data';
import ImportModal from './modules/import-modal.vue';
import PreviewDrawer from './modules/preview-drawer.vue';

const importModalRef = ref<InstanceType<typeof ImportModal>>();
const previewDrawerRef = ref<InstanceType<typeof PreviewDrawer>>();

const generateOpen = ref(false);
const generateSubmitting = ref(false);
const generateTarget = ref<GeneratorTableRow | null>(null);
const generateForce = ref(false);
const generateSyncMenu = ref(true);

const { hasAccessByCodes } = useAccess();

const codegenAccess = {
  can: (code: string) => hasAccessByCodes([code]),
};

function onImport() {
  importModalRef.value?.open();
}

function onActionClick({ code, row }: OnActionClickParams<GeneratorTableRow>) {
  if (code === 'preview') {
    previewDrawerRef.value?.open(row);
  } else if (code === 'generate') {
    openGenerateModal(row);
  } else if (code === 'delete') {
    onDelete(row);
  }
}

async function onDelete(row: GeneratorTableRow) {
  await deleteGeneratorTable(row.id);
  message.success('已删除');
  await gridApi.reload();
}

function openGenerateModal(row: GeneratorTableRow) {
  generateTarget.value = row;
  generateForce.value = false;
  generateSyncMenu.value = true;
  generateOpen.value = true;
}

async function confirmGenerate() {
  if (!generateTarget.value) {
    return;
  }
  generateSubmitting.value = true;
  try {
    const result = await generateCodegen(generateTarget.value.id, {
      force: generateForce.value,
      sync_menu: generateSyncMenu.value,
    });
    const conflictCount = result.conflicts?.length ?? 0;
    const writtenCount = result.written?.length ?? 0;
    if (conflictCount > 0 && !generateForce.value) {
      message.warning(`有 ${conflictCount} 个文件已存在被跳过，可勾选强制覆盖后重试`);
    } else {
      message.success(`生成完成，写入 ${writtenCount} 个文件`);
      generateOpen.value = false;
    }
    await gridApi.reload();
  } finally {
    generateSubmitting.value = false;
  }
}

const columns = useColumns(onActionClick, codegenAccess);

const [Grid, gridApi] = useVbenVxeGrid<GeneratorTableRow>({
  gridOptions: {
    columns: columns as VxeTableGridOptions<GeneratorTableRow>['columns'],
    height: 'auto',
    proxyConfig: {
      ajax: {
        query: async ({ page }) =>
          await fetchGeneratorTablePage({
            page: page.currentPage,
            per_page: page.pageSize,
          }),
      },
    },
    rowConfig: { keyField: 'id' },
    toolbarConfig: { custom: true, refresh: true, zoom: true },
  } as VxeTableGridOptions<GeneratorTableRow>,
});
</script>

<template>
  <Page auto-content-height>
    <ImportModal ref="importModalRef" @success="gridApi.reload()" />
    <PreviewDrawer ref="previewDrawerRef" />

    <Modal
      v-model:open="generateOpen"
      :confirm-loading="generateSubmitting"
      ok-text="开始生成"
      :title="generateTarget ? `生成 · ${generateTarget.business_name}` : '执行生成'"
      @ok="confirmGenerate"
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

    <Grid>
      <template #toolbar-actions>
        <Button
          v-if="codegenAccess.can(GENERATOR_PERMISSIONS.IMPORT)"
          type="primary"
          @click="onImport"
        >
          <Plus class="size-4" />
          导入数据表
        </Button>
      </template>
    </Grid>
  </Page>
</template>
