import type { MaybeRefOrGetter } from 'vue';

import type {
  MobileCrudListConfig,
  MobileListOperation,
} from './types';

import { computed, ref, toValue, watch } from 'vue';

import { isFunction } from '@vben/utils';

import { useDebounceFn } from '@vueuse/core';

function resolveRowKey<T extends Record<string, any>>(
  row: T,
  rowKey: MobileCrudListConfig<T>['rowKey'],
) {
  return isFunction(rowKey) ? rowKey(row) : row[rowKey];
}

function resolveOperationShow<T>(
  operation: MobileListOperation<T>,
  row: T,
) {
  if (operation.show === undefined) {
    return true;
  }
  return isFunction(operation.show) ? operation.show(row) : operation.show;
}

export function useMobileCrudList<T extends Record<string, any>>(
  config: MaybeRefOrGetter<MobileCrudListConfig<T>>,
) {
  const configRef = computed(() => toValue(config));

  const loading = ref(false);
  const list = ref<T[]>([]) as Ref<T[]>;
  const total = ref(0);
  const currentPage = ref(1);
  const pageSize = ref(configRef.value.defaultPageSize ?? 20);
  const keyword = ref('');
  const filterValues = ref<Record<string, any>>({});
  const selectedKeySet = ref(new Set<number | string>());
  const selectedRows = ref<T[]>([]) as Ref<T[]>;

  function syncSelectedRows() {
    const keys = selectedKeySet.value;
    selectedRows.value = list.value.filter((row) =>
      keys.has(resolveRowKey(row, configRef.value.rowKey)),
    );
  }

  function isRowCheckable(row: T) {
    const { selection } = configRef.value;
    if (!selection?.enabled) {
      return false;
    }
    if (selection.checkable) {
      return selection.checkable(row);
    }
    return true;
  }

  function isRowSelected(row: T) {
    return selectedKeySet.value.has(
      resolveRowKey(row, configRef.value.rowKey),
    );
  }

  function toggleRowSelection(row: T, checked?: boolean) {
    if (!isRowCheckable(row)) {
      return;
    }
    const key = resolveRowKey(row, configRef.value.rowKey);
    const nextChecked = checked ?? !selectedKeySet.value.has(key);
    const nextSet = new Set(selectedKeySet.value);
    if (nextChecked) {
      nextSet.add(key);
    } else {
      nextSet.delete(key);
    }
    selectedKeySet.value = nextSet;
    syncSelectedRows();
  }

  function clearSelection() {
    selectedKeySet.value = new Set();
    selectedRows.value = [];
  }

  function getCheckableRows() {
    return list.value.filter((row) => isRowCheckable(row));
  }

  function isAllCheckableSelected() {
    const checkableRows = getCheckableRows();
    if (checkableRows.length === 0) {
      return false;
    }
    return checkableRows.every((row) => isRowSelected(row));
  }

  function toggleSelectAll() {
    const checkableRows = getCheckableRows();
    if (checkableRows.length === 0) {
      return;
    }
    const nextSet = new Set(selectedKeySet.value);
    if (isAllCheckableSelected()) {
      for (const row of checkableRows) {
        nextSet.delete(resolveRowKey(row, configRef.value.rowKey));
      }
    } else {
      for (const row of checkableRows) {
        nextSet.add(resolveRowKey(row, configRef.value.rowKey));
      }
    }
    selectedKeySet.value = nextSet;
    syncSelectedRows();
  }

  function isOperationEnabled(row: T, operation: MobileListOperation<T>) {
    return resolveOperationShow(operation, row);
  }

  function getVisibleOperations(row: T) {
    return (configRef.value.operations ?? []).filter((operation) =>
      resolveOperationShow(operation, row),
    );
  }

  async function loadData() {
    loading.value = true;
    try {
      const { defaultSort, search } = configRef.value;
      const result = await configRef.value.query({
        filters: filterValues.value,
        keyword: keyword.value || undefined,
        page: currentPage.value,
        pageSize: pageSize.value,
        sortField: defaultSort?.field,
        sortOrder: defaultSort?.order,
      });
      list.value = result.data;
      total.value = result.total;
      syncSelectedRows();
    } finally {
      loading.value = false;
    }
  }

  async function reload() {
    currentPage.value = 1;
    await loadData();
  }

  function handleKeywordSearch() {
    currentPage.value = 1;
    loadData();
  }

  function handleFilterSubmit(values: Record<string, any>) {
    filterValues.value = values;
    currentPage.value = 1;
    loadData();
  }

  function handlePageChange(page: number, size: number) {
    currentPage.value = page;
    pageSize.value = size;
    loadData();
  }

  function handleOperationClick(code: string, row: T) {
    configRef.value.onOperationClick?.({ code, row });
  }

  const activeFilterCount = computed(() => {
    return Object.values(filterValues.value).filter(
      (value) => value !== undefined && value !== null && value !== '',
    ).length;
  });

  const debouncedKeywordSearch = useDebounceFn(() => {
    handleKeywordSearch();
  }, 300);

  watch(
    () => keyword.value,
    (value, oldValue) => {
      if (!searchUsesKeyword(configRef.value)) {
        return;
      }
      if (configRef.value.search?.submitOnChange) {
        if (value === '' && oldValue !== '') {
          handleKeywordSearch();
          return;
        }
        debouncedKeywordSearch();
        return;
      }
      if (value === '' && oldValue !== '') {
        handleKeywordSearch();
      }
    },
  );

  const selectAllButtonLabel = computed(() =>
    formatMobileSelectAllLabel(selectedRows.value.length),
  );

  return {
    activeFilterCount,
    clearSelection,
    currentPage,
    filterValues,
    getVisibleOperations,
    handleFilterSubmit,
    handleKeywordSearch,
    handleOperationClick,
    handlePageChange,
    isAllCheckableSelected,
    isOperationEnabled,
    isRowCheckable,
    isRowSelected,
    keyword,
    list,
    loadData,
    loading,
    pageSize,
    reload,
    selectAllButtonLabel,
    selectedRows,
    toggleRowSelection,
    toggleSelectAll,
    total,
  };
}

export function formatMobileSelectAllLabel(selectedCount: number) {
  return selectedCount > 0 ? `已选 ${selectedCount}` : '全选';
}

function searchUsesKeyword<T extends Record<string, any>>(
  config: MobileCrudListConfig<T>,
) {
  return !!config.search?.keywordFields?.length;
}

export type UseMobileCrudListReturn<T extends Record<string, any>> = ReturnType<
  typeof useMobileCrudList<T>
>;
