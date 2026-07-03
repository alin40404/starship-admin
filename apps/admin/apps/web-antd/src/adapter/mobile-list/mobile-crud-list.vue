<script lang="ts" setup generic="T extends Record<string, any>">
import type { MobileCrudListConfig } from './types';

import { computed, onMounted, reactive, watch } from 'vue';

import { EmptyIcon, IconifyIcon } from '@vben/icons';
import { preferences } from '@vben/preferences';
import { $t } from '@vben/locales';
import { get } from '@vben/utils';

import { useVbenForm } from '#/adapter/form';
import { TABLE_CELL_EMPTY } from '#/constants/table';

import {
  Avatar,
  Badge,
  Button,
  Drawer,
  Empty,
  Input,
  Pagination,
  Popconfirm,
  Space,
  Spin,
  Switch,
} from 'ant-design-vue';

import type { MobileListField } from './types';

import { useMobileCrudList } from './use-mobile-crud-list';

import './mobile-crud-list.css';

const props = defineProps<{
  config: MobileCrudListConfig<T>;
}>();

const configRef = computed(() => props.config);

const selectedRowsModel = defineModel<T[]>('selectedRows', { default: () => [] });

const filterDrawerOpen = defineModel<boolean>('filterDrawerOpen', {
  default: false,
});

const {
  activeFilterCount,
  clearSelection,
  currentPage,
  handleFilterSubmit,
  handleKeywordSearch,
  handleOperationClick,
  handlePageChange,
  isOperationEnabled,
  isRowCheckable,
  isRowSelected,
  keyword,
  list,
  loadData,
  loading,
  pageSize,
  reload,
  selectedRows,
  toggleRowSelection,
  toggleSelectAll,
  total,
} = useMobileCrudList(configRef);

const selectionEnabled = computed(
  () => props.config.selection?.enabled !== false,
);

const cardOperations = computed(() => props.config.operations ?? []);

function resolveOperationButtonType(
  operation: (typeof cardOperations.value)[number],
) {
  return operation.type ?? 'link';
}

function resolveFieldImageSrc(field: MobileListField<T>, row: T) {
  const value = field.value(row);
  if (!value || value === TABLE_CELL_EMPTY) {
    return preferences.app.defaultAvatar;
  }
  return value;
}

function resolveSwitchProps(field: MobileListField<T>, row: T) {
  const switchConfig = field.switch!;
  const loadingKey = `__loading_${switchConfig.field}`;
  const rowSwitchProps = switchConfig.getSwitchProps?.(row) ?? {};

  return {
    checkedChildren: $t('common.enabled'),
    checkedValue: 1,
    unCheckedChildren: $t('common.disabled'),
    unCheckedValue: 0,
    ...switchConfig.props,
    ...rowSwitchProps,
    loading: (row as Record<string, any>)[loadingKey] ?? false,
  };
}

function getSwitchChecked(field: MobileListField<T>, row: T) {
  return get(row, field.switch!.field);
}

async function handleSwitchChange(
  field: MobileListField<T>,
  row: T,
  newVal: boolean | number | string,
) {
  const switchConfig = field.switch;
  if (!switchConfig) {
    return;
  }
  const loadingKey = `__loading_${switchConfig.field}`;
  (row as Record<string, any>)[loadingKey] = true;
  try {
    const result = await switchConfig.beforeChange?.(newVal, row);
    if (result !== false) {
      (row as Record<string, any>)[switchConfig.field] = newVal;
    }
  } finally {
    (row as Record<string, any>)[loadingKey] = false;
  }
}

function handleSelectToggle(row: T) {
  if (!selectionEnabled.value || !isRowCheckable(row)) {
    return;
  }
  toggleRowSelection(row);
}

const searchPlaceholder = computed(
  () => props.config.search?.placeholder ?? $t('common.search'),
);

const showSearchBar = computed(
  () =>
    selectionEnabled.value ||
    !!props.config.search?.keywordFields?.length ||
    !!props.config.search?.filterSchema?.length,
);

const [FilterForm, filterFormApi] = useVbenForm(
  reactive({
    commonConfig: {
      componentProps: {
        class: 'w-full',
      },
    },
    fieldMappingTime: props.config.search?.fieldMappingTime,
    handleReset: async () => {
      await filterFormApi.resetForm();
      const values = await filterFormApi.getValues();
      handleFilterSubmit(values ?? {});
    },
    handleSubmit: async () => {
      const values = await filterFormApi.getValues();
      handleFilterSubmit(values ?? {});
      filterDrawerOpen.value = false;
    },
    schema: computed(() => props.config.search?.filterSchema ?? []),
    showCollapseButton: false,
    showDefaultActions: true,
    submitButtonOptions: {
      content: computed(() => $t('common.search')),
    },
    wrapperClass: 'grid-cols-1',
  }),
);

watch(
  selectedRows,
  (rows) => {
    selectedRowsModel.value = rows;
  },
  { deep: true },
);

watch(selectedRowsModel, (rows) => {
  if (rows === selectedRows.value) {
    return;
  }
  if (rows.length === 0) {
    clearSelection();
  }
});

defineExpose({
  clearSelection,
  reload,
});

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="mobile-crud-list flex h-full min-h-0 flex-col">
    <div v-if="$slots.toolbar" class="mobile-crud-list__toolbar mb-2 shrink-0">
      <slot name="toolbar"></slot>
    </div>

    <div v-if="showSearchBar" class="mobile-crud-list__search shrink-0 pb-3">
      <Space.Compact block class="mobile-crud-list__search-compact w-full">
        <Button
          v-if="selectionEnabled"
          class="mobile-crud-list__search-btn mobile-crud-list__search-btn--select-all"
          :class="{
            'mobile-crud-list__search-btn--last':
              !config.search?.keywordFields?.length &&
              !config.search?.filterSchema?.length,
          }"
          size="small"
          @click="toggleSelectAll"
        >
          全选
        </Button>
        <Input
          v-if="config.search?.keywordFields?.length"
          v-model:value="keyword"
          allow-clear
          class="mobile-crud-list__search-input min-w-0 flex-1"
          :placeholder="searchPlaceholder"
          @press-enter="handleKeywordSearch"
        >
          <template #prefix>
            <IconifyIcon
              class="mobile-crud-list__search-prefix-icon"
              icon="lucide:search"
            />
          </template>
        </Input>
        <Badge
          v-if="config.search?.filterSchema?.length"
          :count="activeFilterCount"
          :offset="[-4, 2]"
        >
          <Button
            class="mobile-crud-list__search-btn mobile-crud-list__search-btn--filter"
            :class="{
              'mobile-crud-list__search-btn--last': true,
            }"
            size="small"
            @click="filterDrawerOpen = true"
          >
            <IconifyIcon class="size-3.5" icon="lucide:sliders-horizontal" />
            <span>筛选</span>
          </Button>
        </Badge>
      </Space.Compact>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto">
      <Spin :spinning="loading">
        <div v-if="list.length > 0" class="mobile-crud-list__cards">
          <div
            v-for="row in list"
            :key="String(typeof config.rowKey === 'function' ? config.rowKey(row) : row[config.rowKey])"
            class="mobile-crud-list__item"
          >
            <div
              class="mobile-crud-list__card"
              :class="{
                'mobile-crud-list__card--selected':
                  selectionEnabled && isRowSelected(row),
                'mobile-crud-list__card--with-select': selectionEnabled,
              }"
            >
              <button
                v-if="selectionEnabled"
                type="button"
                class="mobile-crud-list__card-select-btn"
                :class="{
                  'mobile-crud-list__card-select-btn--disabled':
                    !isRowCheckable(row),
                  'mobile-crud-list__card-select-btn--selected':
                    isRowCheckable(row) && isRowSelected(row),
                }"
                :disabled="!isRowCheckable(row)"
                @click.stop="handleSelectToggle(row)"
              >
                <IconifyIcon
                  v-if="isRowCheckable(row) && isRowSelected(row)"
                  class="mobile-crud-list__card-select-btn-icon"
                  icon="lucide:check"
                />
              </button>
              <div
                v-if="config.item.fields.some((field) => (field.show ? field.show(row) : true))"
                class="mobile-crud-list__card-body"
              >
                <template
                  v-for="(field, fieldIndex) in config.item.fields"
                  :key="`${field.label}-${fieldIndex}`"
                >
                  <template v-if="field.show ? field.show(row) : true">
                    <span class="mobile-crud-list__field-label">
                      {{ field.label }}
                    </span>
                    <span
                      class="mobile-crud-list__field-value"
                      :class="{
                        'mobile-crud-list__field-value--switch':
                          field.render === 'switch',
                      }"
                    >
                      <Avatar
                        v-if="field.render === 'image'"
                        :size="32"
                        shape="square"
                        :src="resolveFieldImageSrc(field, row)"
                        class="mobile-crud-list__field-image"
                      />
                      <Switch
                        v-else-if="field.render === 'switch' && field.switch"
                        :checked="getSwitchChecked(field, row)"
                        size="small"
                        v-bind="resolveSwitchProps(field, row)"
                        @update:checked="handleSwitchChange(field, row, $event)"
                        @click.stop
                      />
                      <template v-else>{{ field.value(row) }}</template>
                    </span>
                  </template>
                </template>
              </div>

              <div
                v-if="cardOperations.length > 0"
                class="mobile-crud-list__card-actions table-operations"
                @click.stop
              >
                <template
                  v-for="operation in cardOperations"
                  :key="operation.code"
                >
                  <Popconfirm
                    v-if="operation.confirm && isOperationEnabled(row, operation)"
                    :title="operation.confirm.title ?? operation.text"
                    @confirm="handleOperationClick(operation.code, row)"
                  >
                    <template #description>
                      {{
                        operation.confirm.content?.(row) ??
                        operation.text
                      }}
                    </template>
                    <Button
                      class="mobile-crud-list__action-btn"
                      :danger="operation.danger"
                      size="small"
                      :type="resolveOperationButtonType(operation)"
                      @click.stop
                    >
                      {{ operation.text }}
                    </Button>
                  </Popconfirm>
                  <Button
                    v-else
                    class="mobile-crud-list__action-btn"
                    :danger="operation.danger"
                    :disabled="!isOperationEnabled(row, operation)"
                    size="small"
                    :type="resolveOperationButtonType(operation)"
                    @click.stop="handleOperationClick(operation.code, row)"
                  >
                    {{ operation.text }}
                  </Button>
                </template>
              </div>
            </div>
          </div>
        </div>
        <Empty v-else-if="!loading" class="py-12">
          <template #image>
            <EmptyIcon class="mx-auto size-24" />
          </template>
          <template #description>
            {{ $t('common.noData') }}
          </template>
        </Empty>
      </Spin>
    </div>

    <div v-if="total > 0" class="mobile-crud-list__pager">
      <span class="mobile-crud-list__pager-total">共 {{ total }} 条</span>
      <Pagination
        :current="currentPage"
        :page-size="pageSize"
        :show-size-changer="false"
        :total="total"
        size="small"
        @change="handlePageChange"
      />
    </div>

    <Drawer
      v-if="config.search?.filterSchema?.length"
      v-model:open="filterDrawerOpen"
      :height="420"
      placement="bottom"
      title="筛选"
    >
      <FilterForm />
    </Drawer>
  </div>
</template>
