<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { ConfigRow } from '#/features/system/api/config';

import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';

import { useAccess } from '@vben/access';
import { useVbenModal } from '@vben/common-ui';
import { Plus } from '@vben/icons';

import { useElementSize } from '@vueuse/core';
import { $t } from '@vben/locales';

import { Button, Input, InputNumber, message, Modal, Radio, Tag, Textarea } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  batchDeleteConfigs,
  batchUpsertConfigs,
  deleteConfig,
  fetchConfigs,
} from '#/features/system/api/config';
import { CONFIG_PERMISSIONS } from '#/features/system/constants/config-permissions';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import {
  applyAdminApiTimeout,
  clearAdminRuntimeParamsCache,
  initAdminRuntimeConfig,
} from '#/utils/admin-runtime-config';

import {
  buildParamSortColumn,
  buildParamValueColumn,
  configFieldLabel,
  configFieldRemark,
  getParamTypeTagOptions,
  normalizeParamValueForEdit,
  normalizeParamValueForSave,
  isParamBoolTrue,
  PARAM_VALUE_DEFAULT_SLOT,
  PARAM_VALUE_EDIT_SLOT,
  validateParamSort,
  validateParamValue,
} from '../data';
import ParamForm from './param-form.vue';

const props = defineProps<{
  canManage: boolean;
}>();

const { hasAccessByCodes } = useAccess();

const paramAccess = {
  can: (code: string) => hasAccessByCodes([code]),
};

const gridWrapRef = useTemplateRef<HTMLElement>('gridWrapRef');
const { height: gridWrapHeight } = useElementSize(gridWrapRef);
const selectedRows = ref<ConfigRow[]>([]);

const boolValueOptions = computed(() => [
  { label: $t('platform.config.param.boolTrue'), value: 'true' },
  { label: $t('platform.config.param.boolFalse'), value: 'false' },
]);

function finishParamValueEdit($table?: { clearEdit?: () => void }) {
  void $table?.clearEdit?.();
}

async function handleBoolValueChange($table?: { clearEdit?: () => void }) {
  await nextTick();
  finishParamValueEdit($table);
}

function getNumberEditValue(value: ConfigRow['value']) {
  const parsed = Number(value);

  return Number.isFinite(parsed) ? parsed : undefined;
}

function handleParamValueEnter(
  event: KeyboardEvent,
  $table?: { clearEdit?: () => void },
) {
  if (event.key !== 'Enter' || event.shiftKey) {
    return;
  }
  event.preventDefault();
  finishParamValueEdit($table);
}

const [ParamFormModal, paramFormModalApi] = useVbenModal({
  connectedComponent: ParamForm,
  destroyOnClose: true,
});

function syncSelection() {
  const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
  selectedRows.value = records as ConfigRow[];
}

function clearSelection() {
  gridApi?.grid?.clearCheckboxRow?.();
  selectedRows.value = [];
}

function onCreate() {
  paramFormModalApi.setData({}).open();
}

function onEdit(row: ConfigRow) {
  paramFormModalApi.setData(row).open();
}

function onRefresh() {
  gridApi.query();
}

function onActionClick({ code, row }: OnActionClickParams<ConfigRow>) {
  if (code === 'edit') onEdit(row);
  else if (code === 'delete') onDelete(row);
}

async function onDelete(row: ConfigRow) {
  await deleteConfig(row.id);
  message.success($t('crud.deleted'));
  clearSelection();
  onRefresh();
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) return;
  Modal.confirm({
    content: $t('platform.config.param.batchDeleteConfirm', [ids.length]),
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchDelete'),
    async onOk() {
      const result = await batchDeleteConfigs(ids);
      message.success(
        $t('platform.config.param.batchDeleted', [result.deleted]),
      );
      clearSelection();
      onRefresh();
    },
  });
}

async function refreshAdminRuntimeIfNeeded(key: string) {
  if (!key.startsWith('admin.')) {
    return;
  }

  clearAdminRuntimeParamsCache();
  await initAdminRuntimeConfig(true);
  const { requestClient, baseRequestClient } = await import('#/api/request');
  applyAdminApiTimeout(requestClient, baseRequestClient);
}

async function saveParamRow(row: ConfigRow) {
  gridApi.setLoading(true);
  try {
    await batchUpsertConfigs([
      {
        group_name: 'param',
        key: row.key,
        name: row.name,
        remark: row.remark || null,
        sort: Number(row.sort ?? 0),
        type: row.type,
        value: normalizeParamValueForSave(row.type, row.value),
      },
    ]);
    await refreshAdminRuntimeIfNeeded(row.key);
    message.success($t('crud.saved'));
    onRefresh();
  } catch {
    await gridApi.reload();
  } finally {
    gridApi.setLoading(false);
  }
}

const [Grid, gridApi] = useVbenVxeGrid<ConfigRow>({
  class: 'h-full min-h-0',
  gridClass: 'p-0',
  gridEvents: {
    checkboxAll: syncSelection,
    checkboxChange: syncSelection,
    editActivated({
      column,
      row,
    }: {
      column: { field: string };
      row: ConfigRow;
    }) {
      if (column.field !== 'value') {
        return;
      }

      row.value = normalizeParamValueForEdit(row.type, row.value);
    },
    async editClosed({
      column,
      row,
    }: {
      column: { field: string };
      row: ConfigRow;
    }) {
      if (!props.canManage) {
        return;
      }

      if (column.field !== 'value' && column.field !== 'sort') {
        return;
      }

      const grid = gridApi.grid;
      if (!grid?.isUpdateByRow?.(row)) {
        return;
      }

      if (column.field === 'value') {
        const nextValue = normalizeParamValueForSave(row.type, row.value);
        if (!validateParamValue(row.type, nextValue)) {
          message.warning($t('platform.config.param.valueInvalid'));
          await gridApi.reload();
          return;
        }
        row.value = nextValue;
      } else {
        const sort = Number(row.sort);
        if (!validateParamSort(sort)) {
          message.warning($t('org.shared.sortInvalid'));
          await gridApi.reload();
          return;
        }
        row.sort = sort;
      }

      await saveParamRow(row);
    },
  },
  gridOptions: {
    checkboxConfig: { highlight: true },
    columns: [
      {
        align: 'center',
        fixed: 'left',
        type: 'checkbox',
        width: 48,
      },
      {
        align: 'left',
        field: 'name',
        formatter: ({ row }) => configFieldLabel(row as ConfigRow),
        minWidth: 140,
        title: $t('platform.config.param.name'),
      },
      { align: 'left', field: 'key', minWidth: 160, title: $t('platform.config.param.key') },
      buildParamValueColumn(props.canManage),
      {
        align: 'center',
        cellRender: { name: 'CellTag', options: [...getParamTypeTagOptions()] },
        field: 'type',
        title: $t('platform.config.param.valueType'),
        width: 96,
      },
      buildParamSortColumn(props.canManage),
      {
        align: 'left',
        field: 'remark',
        formatter: ({ row }) =>
          configFieldRemark(row as ConfigRow) ?? TABLE_CELL_EMPTY,
        minWidth: 160,
        title: $t('platform.config.param.remark'),
      },
      {
        align: 'center',
        cellRender: {
          attrs: {
            nameField: 'name',
            nameTitle: $t('platform.config.param.entity'),
            onClick: onActionClick,
          },
          name: 'CellOperation',
          options: [
            {
              code: 'edit',
              show: () => props.canManage,
              text: $t('common.edit'),
              type: 'link',
            },
            {
              code: 'delete',
              danger: true,
              show: () => paramAccess.can(CONFIG_PERMISSIONS.DELETE),
              text: $t('common.delete'),
              type: 'link',
            },
          ],
        },
        fixed: 'right',
        title: $t('crud.columnActions'),
        width: 140,
      },
    ],
    editConfig: {
      beforeEditMethod({ column }) {
        return (
          (column.field === 'value' || column.field === 'sort') &&
          props.canManage
        );
      },
      mode: 'cell',
      trigger: 'dblclick',
    },
    height: 360,
    keepSource: true,
    pagerConfig: { enabled: false },
    proxyConfig: {
      ajax: {
        query: async () => {
          const result = await fetchConfigs('param');
          const list = result.list ?? [];
          return { data: list, total: list.length };
        },
      },
    },
    rowConfig: { keyField: 'id' },
    toolbarConfig: { refresh: true },
  } as VxeTableGridOptions<ConfigRow>,
});

watch(
  gridWrapHeight,
  (height) => {
    gridApi.setGridOptions({
      height: Math.max(Math.floor(height), 280),
    });
  },
  { immediate: true },
);
</script>

<template>
  <div class="flex h-full min-h-0 flex-col">
    <ParamFormModal @success="onRefresh" />
    <div ref="gridWrapRef" class="min-h-0 flex-1">
      <Grid>
        <template #[PARAM_VALUE_DEFAULT_SLOT]="{ row }">
          <Tag
            v-if="row.type === 'bool'"
            :color="isParamBoolTrue(row.value) ? 'success' : 'default'"
          >
            {{
              isParamBoolTrue(row.value)
                ? $t('platform.config.param.boolTrue')
                : $t('platform.config.param.boolFalse')
            }}
          </Tag>
          <span
            v-else-if="row.value === null || row.value === ''"
            class="text-muted-foreground"
          >
            {{ TABLE_CELL_EMPTY }}
          </span>
          <span v-else>{{ row.value }}</span>
        </template>
        <template #[PARAM_VALUE_EDIT_SLOT]="{ row, $table }">
          <Teleport v-if="row.type === 'json'" to="body">
            <div
              class="param-value-json-edit-overlay"
              @mousedown.self="finishParamValueEdit($table)"
            >
              <div class="param-value-json-edit-cell" @mousedown.stop>
                <Textarea
                  :value="row.value ?? ''"
                  :auto-size="{ minRows: 8, maxRows: 16 }"
                  class="w-full font-mono"
                  :placeholder="$t('platform.config.param.jsonPlaceholder')"
                  @update:value="(v) => (row.value = v)"
                />
              </div>
            </div>
          </Teleport>
          <div v-else class="param-value-edit-cell" @mousedown.stop>
            <InputNumber
              v-if="row.type === 'number'"
              :value="getNumberEditValue(row.value)"
              class="w-full"
              :placeholder="$t('platform.config.param.numberPlaceholder')"
              @keydown="handleParamValueEnter($event, $table)"
              @update:value="(v) => (row.value = v == null ? '' : String(v))"
            />
            <Radio.Group
              v-else-if="row.type === 'bool'"
              :value="row.value ?? 'false'"
              button-style="solid"
              class="param-bool-radio-group"
              option-type="button"
              :options="boolValueOptions"
              size="small"
              @update:value="(v) => { row.value = v; void handleBoolValueChange($table); }"
            />
            <Input
              v-else
              :value="row.value ?? ''"
              class="w-full"
              :placeholder="$t('platform.config.param.stringPlaceholder')"
              @keydown="handleParamValueEnter($event, $table)"
              @update:value="(v) => (row.value = v)"
            />
          </div>
        </template>
        <template #toolbar-actions>
          <div v-if="selectedRows.length > 0" class="vxe-grid-batch-toolbar">
            <Button
              v-if="paramAccess.can(CONFIG_PERMISSIONS.BATCH_DELETE)"
              danger
              @click="onBatchDelete"
            >
              {{ $t('system.shared.batchDeleteWithCount', [selectedRows.length]) }}
            </Button>
            <Button class="ml-2" type="link" @click="clearSelection">
              {{ $t('system.shared.clearSelection') }}
            </Button>
          </div>
          <Button
            v-else-if="props.canManage"
            type="primary"
            @click="onCreate"
          >
            <Plus class="size-4" />
            {{ $t('platform.config.param.createParam') }}
          </Button>
        </template>
      </Grid>
    </div>
  </div>
</template>

<style lang="scss">
.vxe-grid .param-value-editable-col,
.vxe-grid .param-sort-editable-col {
  cursor: pointer;
}

.vxe-grid .param-bool-radio-group {
  display: inline-flex;
  width: 156px;

  .ant-radio-button-wrapper {
    width: 78px;
    padding-inline: 0;
    text-align: center;
  }
}

.param-value-json-edit-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: rgb(0 0 0 / 45%);
}

.param-value-json-edit-cell {
  width: min(560px, 100%);
  max-height: 80vh;
  overflow: auto;
  padding: 16px;
  background: hsl(var(--card));
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  box-shadow: 0 12px 32px rgb(0 0 0 / 18%);
}
</style>
