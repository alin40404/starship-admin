import type { Ref } from 'vue';

import type { VxeGridProps } from '#/adapter/vxe-table';

import type { ExtendedVxeGridApi } from '#/adapter/vxe-table';

import type { MobileCrudListConfig } from './types';

import { defineComponent, h, ref, watch } from 'vue';

import { usePreferences } from '@vben/preferences';

import { useVbenVxeGrid } from '#/adapter/vxe-table';

import MobileCrudList from './mobile-crud-list.vue';

export interface ResponsiveCrudOptions<
  T extends Record<string, any>,
> extends VxeGridProps<T> {
  mobile: MobileCrudListConfig<T>;
}

export interface ResponsiveCrudApi<T extends Record<string, any>> {
  clearSelection: () => void;
  gridApi: ExtendedVxeGridApi<T>;
  mobileListRef: Ref<{
    clearSelection: () => void;
    reload: () => Promise<void>;
  } | null>;
  query: () => Promise<void>;
  reload: () => Promise<void>;
  selectedRows: Ref<T[]>;
}

export function useResponsiveCrud<T extends Record<string, any>>(
  options: ResponsiveCrudOptions<T>,
) {
  const { isMobile } = usePreferences();
  const selectedRows = ref<T[]>([]) as Ref<T[]>;
  const mobileListRef = ref<{ clearSelection: () => void; reload: () => Promise<void> } | null>(
    null,
  );

  const { mobile, gridEvents, ...gridOptions } = options;

  function syncGridSelection() {
    if (isMobile.value) {
      return;
    }
    const records = gridApi.grid?.getCheckboxRecords?.() ?? [];
    selectedRows.value = records as T[];
  }

  const [Grid, gridApi] = useVbenVxeGrid<T>({
    ...gridOptions,
    gridEvents: {
      ...gridEvents,
      checkboxAll: (...args) => {
        syncGridSelection();
        gridEvents?.checkboxAll?.(...args);
      },
      checkboxChange: (...args) => {
        syncGridSelection();
        gridEvents?.checkboxChange?.(...args);
      },
    },
  });

  function clearSelection() {
    selectedRows.value = [];
    gridApi.grid?.clearCheckboxRow?.();
    mobileListRef.value?.clearSelection?.();
  }

  async function reload() {
    if (isMobile.value) {
      await mobileListRef.value?.reload?.();
      return;
    }
    await gridApi.reload();
  }

  async function query() {
    await reload();
  }

  watch(isMobile, () => {
    clearSelection();
  });

  const CrudView = defineComponent({
    name: 'ResponsiveCrudView',
    inheritAttrs: false,
    setup(_, { attrs, slots }) {
      return () => {
        if (isMobile.value) {
          return h(
            'div',
            {
              class: 'flex h-full min-h-0 flex-col rounded-md bg-card p-2',
            },
            [
              h(
                MobileCrudList,
                {
                  ...attrs,
                  ref: mobileListRef,
                  config: mobile,
                  selectedRows: selectedRows.value,
                  'onUpdate:selectedRows': (rows: T[]) => {
                    selectedRows.value = rows;
                  },
                },
                {
                  toolbar: () => slots['toolbar-actions']?.(),
                },
              ),
            ],
          );
        }

        return h(
          Grid,
          {
            ...attrs,
          },
          slots,
        );
      };
    },
  });

  const api: ResponsiveCrudApi<T> = {
    clearSelection,
    gridApi,
    mobileListRef,
    query,
    reload,
    selectedRows,
  };

  return [CrudView, api] as const;
}

export type UseResponsiveCrud = typeof useResponsiveCrud;
