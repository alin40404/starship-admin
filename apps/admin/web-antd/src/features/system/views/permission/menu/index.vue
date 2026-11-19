<script lang="ts" setup>
import type {
  OnActionClickParams,
  VxeTableGridOptions,
} from '#/adapter/vxe-table';
import type { MenuTreeNode } from '#/features/system/api/menu';

import { computed, ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page, useVbenDrawer } from '@vben/common-ui';
import { IconifyIcon, Plus } from '@vben/icons';
import { MenuBadge } from '@vben-core/menu-ui';

import { $t } from '@vben/locales';

import { Button, message, Modal, Tag, Tooltip } from 'ant-design-vue';

import {
  batchDeleteMenus,
  batchUpdateMenuStatus,
  deleteMenu,
  fetchMenuTree,
  updateMenuSort,
  updateMenuStatus,
} from '#/features/system/api/menu';
import { useVbenVxeGrid } from '#/adapter/vxe-table';
import { MENU_PERMISSIONS } from '#/features/system/constants/menu-permissions';
import {
  isDevOnlyModuleUnavailable,
} from '#/features/system/utils/dev-only-module';
import { filterRowsForStatusChange } from '#/utils/batch-status';

import { menuDisplayLabel, useColumns } from './data';
import MenuForm from './modules/form.vue';

const { hasAccessByCodes } = useAccess();

const menuAccess = {
  can: (code: string) => hasAccessByCodes([code]),
  canAny: (codes: string[]) => hasAccessByCodes(codes),
};

function moduleTooltip(
  module: NonNullable<MenuTreeNode['module']>,
): string {
  if (isDevOnlyModuleUnavailable(module)) {
    return $t('permission.menu.moduleDevOnlyTooltip', [module.title]);
  }
  if (module.enabled) {
    return $t('permission.menu.moduleBelong', [
      module.title,
      module.name,
    ]);
  }
  return $t('permission.menu.moduleDisabled', [module.title]);
}

function moduleDevOnlySuffix(
  module: NonNullable<MenuTreeNode['module']>,
): string {
  if (!module.dev_only) {
    return '';
  }
  return isDevOnlyModuleUnavailable(module)
    ? $t('permission.menu.moduleDevOnlyUnavailableTag')
    : $t('permission.menu.moduleDevOnlyTag');
}

const [MenuFormDrawer, menuFormDrawerApi] = useVbenDrawer({
  connectedComponent: MenuForm,
  destroyOnClose: true,
});

const selectedRows = ref<MenuTreeNode[]>([]);

const enableTargetRows = computed(() =>
  filterRowsForStatusChange(selectedRows.value, 1),
);
const disableTargetRows = computed(() =>
  filterRowsForStatusChange(selectedRows.value, 0),
);

function syncSelection() {
  const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
  selectedRows.value = records as MenuTreeNode[];
}

function clearSelection() {
  gridApi?.grid?.clearCheckboxRow?.();
  selectedRows.value = [];
}

async function onMenuStatusToggle(nextStatus: number, row: MenuTreeNode) {
  return new Promise<boolean>((resolve) => {
    const label =
      nextStatus === 1
        ? $t('system.shared.status.enable')
        : $t('system.shared.status.disable');
    Modal.confirm({
      content: $t('system.shared.toggleStatusConfirm', [
        menuDisplayLabel(row),
        label,
      ]),
      okText: $t('common.confirm'),
      cancelText: $t('common.cancel'),
      title: $t('system.shared.toggleStatusTitle'),
      async onOk() {
        try {
          await updateMenuStatus(row.id, nextStatus as 0 | 1);
          message.success($t('crud.statusUpdated'));
          onRefresh();
          resolve(true);
        } catch {
          resolve(false);
        }
      },
      onCancel() {
        resolve(false);
      },
    });
  });
}

const [Grid, gridApi] = useVbenVxeGrid({
  gridEvents: {
    checkboxAll: syncSelection,
    checkboxChange: syncSelection,
    async editClosed({
      column,
      row,
    }: {
      column: { field: string };
      row: MenuTreeNode;
    }) {
      if (column.field !== 'sort') {
        return;
      }
      const grid = gridApi.grid;
      if (!grid?.isUpdateByRow?.(row)) {
        return;
      }

      const sort = Number(row.sort);
      if (!Number.isFinite(sort) || sort < 0 || !Number.isInteger(sort)) {
        message.warning($t('permission.menu.sortInvalid'));
        await gridApi.reload();
        return;
      }

      gridApi.setLoading(true);
      try {
        await updateMenuSort(row.id, sort);
        message.success($t('permission.menu.sortUpdated'));
        onRefresh();
      } catch {
        await gridApi.reload();
      } finally {
        gridApi.setLoading(false);
      }
    },
  },
  gridOptions: {
    checkboxConfig: {
      highlight: true,
    },
    columns: useColumns(onActionClick, onMenuStatusToggle, menuAccess),
    editConfig: {
      beforeEditMethod({ column }) {
        return (
          column.field === 'sort' &&
          menuAccess.can(MENU_PERMISSIONS.SORT)
        );
      },
      mode: 'cell',
      trigger: 'dblclick',
    },
    height: 'auto',
    keepSource: true,
    pagerConfig: {
      enabled: false,
    },
    proxyConfig: {
      ajax: {
        query: async () => {
          return await fetchMenuTree();
        },
      },
    },
    rowConfig: {
      keyField: 'id',
    },
    toolbarConfig: {
      custom: true,
      export: false,
      refresh: true,
      zoom: true,
    },
    treeConfig: {
      childrenField: 'children',
      rowField: 'id',
      transform: false,
    },
  } as VxeTableGridOptions,
});

function onActionClick({
  code,
  row,
}: OnActionClickParams<MenuTreeNode>) {
  switch (code) {
    case 'append': {
      onAppend(row);
      break;
    }
    case 'delete': {
      onDelete(row);
      break;
    }
    case 'edit': {
      onEdit(row);
      break;
    }
    default: {
      break;
    }
  }
}

function onRefresh() {
  gridApi.query();
}

function onEdit(row: MenuTreeNode) {
  menuFormDrawerApi.setData(row).open();
}

function onCreate() {
  menuFormDrawerApi.setData({}).open();
}

function onAppend(row: MenuTreeNode) {
  menuFormDrawerApi.setData({ parent_id: row.id }).open();
}

async function onDelete(row: MenuTreeNode) {
  await deleteMenu(row.id);
  message.success($t('crud.deleted'));
  clearSelection();
  onRefresh();
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) {
    return;
  }
  Modal.confirm({
    content: $t('permission.menu.batchDeleteConfirm', [ids.length]),
    okText: $t('common.delete'),
    okType: 'danger',
    cancelText: $t('common.cancel'),
    title: $t('system.shared.batchDelete'),
    async onOk() {
      await batchDeleteMenus(ids);
      message.success($t('crud.batchDeleted'));
      clearSelection();
      onRefresh();
    },
  });
}

function onBatchUpdateStatus(status: 0 | 1) {
  const targets = filterRowsForStatusChange(selectedRows.value, status);
  const ids = targets.map((row) => row.id);
  if (ids.length === 0) {
    return;
  }
  const label =
    status === 1
      ? $t('system.shared.status.enable')
      : $t('system.shared.status.disable');
  Modal.confirm({
    content: $t('permission.menu.batchStatusConfirm', [
      ids.length,
      label,
    ]),
    okText: $t('common.confirm'),
    cancelText: $t('common.cancel'),
    title:
      status === 1
        ? $t('system.shared.batchEnable')
        : $t('system.shared.batchDisable'),
    async onOk() {
      await batchUpdateMenuStatus(ids, status);
      message.success($t('system.shared.batchStatusDone', [label]));
      clearSelection();
      onRefresh();
    },
  });
}
</script>

<template>
  <Page auto-content-height>
    <MenuFormDrawer @success="onRefresh" />
    <Grid>
      <template #toolbar-actions>
        <div v-if="selectedRows.length > 0" class="vxe-grid-batch-toolbar">
          <Button
            v-if="menuAccess.can(MENU_PERMISSIONS.BATCH_DELETE)"
            danger
            @click="onBatchDelete"
          >
            {{ $t('system.shared.batchDeleteWithCount', [selectedRows.length]) }}
          </Button>
          <Button
            v-if="menuAccess.can(MENU_PERMISSIONS.BATCH_STATUS)"
            class="batch-btn-enable ml-2"
            :disabled="enableTargetRows.length === 0"
            @click="onBatchUpdateStatus(1)"
          >
            {{ $t('system.shared.batchEnableWithCount', [enableTargetRows.length]) }}
          </Button>
          <Button
            v-if="menuAccess.can(MENU_PERMISSIONS.BATCH_STATUS)"
            class="batch-btn-disable ml-2"
            :disabled="disableTargetRows.length === 0"
            @click="onBatchUpdateStatus(0)"
          >
            {{ $t('system.shared.batchDisableWithCount', [disableTargetRows.length]) }}
          </Button>
          <Button class="ml-2" type="link" @click="clearSelection">
            {{ $t('system.shared.clearSelection') }}
          </Button>
        </div>
        <Button
          v-else-if="menuAccess.can(MENU_PERMISSIONS.CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          {{ $t('permission.menu.createMenu') }}
        </Button>
      </template>
      <template #title="{ row }">
        <div class="relative w-full">
          <div class="flex w-full items-center gap-1.5 pr-10">
            <span class="flex size-5 shrink-0 items-center justify-center">
              <IconifyIcon
                v-if="row.type === 3"
                class="size-4 text-foreground/70"
                icon="carbon:security"
              />
              <IconifyIcon
                v-else-if="row.icon?.trim()"
                class="size-4 text-foreground/80"
                :icon="row.icon!.trim()"
              />
            </span>
            <a
              v-if="menuAccess.can(MENU_PERMISSIONS.UPDATE)"
              class="menu-title-link min-w-0 flex-auto truncate"
              href="javascript:void(0)"
              @click.prevent="onEdit(row)"
            >
              {{ menuDisplayLabel(row) }}
            </a>
            <span v-else class="min-w-0 flex-auto truncate">{{
              menuDisplayLabel(row)
            }}</span>
          </div>
          <MenuBadge
            v-if="row.badge_type"
            class="menu-badge"
            :badge="row.badge ?? undefined"
            :badge-type="row.badge_type"
            :badge-variants="row.badge_variants ?? undefined"
          />
        </div>
      </template>
      <template #module="{ row }">
        <Tooltip v-if="row.module" :title="moduleTooltip(row.module)">
          <Tag
            :color="
              !row.module.enabled || isDevOnlyModuleUnavailable(row.module)
                ? 'warning'
                : row.module.dev_only
                  ? 'default'
                  : 'processing'
            "
          >
            {{ row.module.title }}
            <template v-if="!row.module.enabled">
              {{ $t('permission.menu.moduleDisabledTag') }}
            </template>
            <template v-else-if="row.module.dev_only">
              {{ moduleDevOnlySuffix(row.module) }}
            </template>
          </Tag>
        </Tooltip>
        <span v-else class="text-muted-foreground">—</span>
      </template>
    </Grid>
  </Page>
</template>

<style scoped lang="scss">
.menu-badge {
  top: 50%;
  right: 0;
  transform: translateY(-50%);

  & > :deep(div) {
    padding-top: 0;
    padding-bottom: 0;
  }
}

.menu-title-link {
  color: hsl(var(--primary));
  cursor: pointer;
  text-decoration: none;

  &:hover {
    color: hsl(var(--primary) / 80%);
    text-decoration: underline;
  }
}
</style>

<style lang="scss">
/** 排序列：提示可双击编辑 */
.vxe-grid .menu-sort-editable-col {
  cursor: pointer;
}

</style>
