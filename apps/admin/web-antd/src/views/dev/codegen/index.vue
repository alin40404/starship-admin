<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { GeneratorTableRow } from '#/api/generator';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { Plus, RotateCw } from '@vben/icons';

import { Button, message } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  deleteGeneratorTable,
  fetchGeneratorTablePage,
} from '#/api/generator';
import { GENERATOR_PERMISSIONS } from '#/constants/generator-permissions';
import { $t } from '#/locales';
import { useAuthStore } from '#/store';

import { useColumns } from './data';
import GenerateModal from './modules/generate-modal.vue';
import ImportModal from './modules/import-modal.vue';
import PreviewDrawer from './modules/preview-drawer.vue';

const importModalRef = ref<InstanceType<typeof ImportModal>>();
const generateModalRef = ref<InstanceType<typeof GenerateModal>>();
const previewDrawerRef = ref<InstanceType<typeof PreviewDrawer>>();
const refreshAccessLoading = ref(false);

const authStore = useAuthStore();
const { hasAccessByCodes } = useAccess();

const codegenAccess = {
  can: (code: string) =>
    hasAccessByCodes([GENERATOR_PERMISSIONS.MANAGE, code]),
};

function onImport() {
  importModalRef.value?.open();
}

async function onRefreshAccess() {
  refreshAccessLoading.value = true;
  try {
    await authStore.refreshAccess();
    message.success($t('dev.codegen.refreshAccessDone'));
  } finally {
    refreshAccessLoading.value = false;
  }
}

function onActionClick({ code, row }: OnActionClickParams<GeneratorTableRow>) {
  if (code === 'preview') {
    previewDrawerRef.value?.open(row);
  } else if (code === 'generate') {
    generateModalRef.value?.open(row);
  } else if (code === 'delete') {
    onDelete(row);
  }
}

async function onDelete(row: GeneratorTableRow) {
  await deleteGeneratorTable(row.id);
  message.success($t('crud.deleted'));
  await gridApi.reload();
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
    <GenerateModal ref="generateModalRef" @success="gridApi.reload()" />
    <PreviewDrawer ref="previewDrawerRef" />

    <Grid>
      <template #toolbar-actions>
        <Button
          v-if="codegenAccess.can(GENERATOR_PERMISSIONS.IMPORT)"
          type="primary"
          @click="onImport"
        >
          <Plus class="size-4" />
          {{ $t('dev.codegen.importTable') }}
        </Button>
        <Button
          :loading="refreshAccessLoading"
          class="ml-2"
          @click="onRefreshAccess"
        >
          <RotateCw class="size-4" />
          {{ $t('dev.codegen.refreshAccess') }}
        </Button>
      </template>
    </Grid>
  </Page>
</template>
