<script lang="ts" setup>
import type {
  OnActionClickParams,
  VxeTableGridOptions,
} from '#/adapter/vxe-table';
import type { MenuTreeNode } from '#/api/system/menu';

import { ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page, useVbenDrawer } from '@vben/common-ui';
import { IconifyIcon, Plus } from '@vben/icons';
import { MenuBadge } from '@vben-core/menu-ui';

import { Button, message, Modal, Tag, Tooltip } from 'ant-design-vue';

import {
  batchDeleteMenus,
  batchUpdateMenuStatus,
  deleteMenu,
  fetchMenuTree,
  updateMenuSort,
  updateMenuStatus,
} from '#/api/system/menu';
import { useVbenVxeGrid } from '#/adapter/vxe-table';
import { MENU_PERMISSIONS } from '#/constants/menu-permissions';

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
  if (module.enabled) {
    return `归属模块：${module.title}（${module.name}）`;
  }
  return `模块「${module.title}」已禁用，侧栏与权限不会生效`;
}

const [MenuFormDrawer, menuFormDrawerApi] = useVbenDrawer({
  connectedComponent: MenuForm,
  destroyOnClose: true,
});

const selectedRows = ref<MenuTreeNode[]>([]);

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
    const label = nextStatus === 1 ? '启用' : '禁用';
    Modal.confirm({
      content: `确定将「${menuDisplayLabel(row)}」的状态切换为【${label}】吗？`,
      okText: '确定',
      cancelText: '取消',
      title: '切换状态',
      async onOk() {
        try {
          await updateMenuStatus(row.id, nextStatus as 0 | 1);
          message.success('状态已更新');
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
        message.warning('请输入有效整数');
        await gridApi.reload();
        return;
      }

      gridApi.setLoading(true);
      try {
        await updateMenuSort(row.id, sort);
        message.success('排序已更新');
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
  message.success('已删除');
  clearSelection();
  onRefresh();
}

function onBatchDelete() {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) {
    return;
  }
  Modal.confirm({
    content: `确定删除选中的 ${ids.length} 条菜单吗？若存在未选中的子菜单，删除可能失败。`,
    okText: '删除',
    okType: 'danger',
    cancelText: '取消',
    title: '批量删除',
    async onOk() {
      await batchDeleteMenus(ids);
      message.success('已批量删除');
      clearSelection();
      onRefresh();
    },
  });
}

function onBatchUpdateStatus(status: 0 | 1) {
  const ids = selectedRows.value.map((row) => row.id);
  if (ids.length === 0) {
    return;
  }
  const label = status === 1 ? '启用' : '禁用';
  Modal.confirm({
    content: `确定将选中的 ${ids.length} 条菜单批量【${label}】吗？`,
    okText: '确定',
    cancelText: '取消',
    title: `批量${label}`,
    async onOk() {
      await batchUpdateMenuStatus(ids, status);
      message.success(`已批量${label}`);
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
            批量删除（{{ selectedRows.length }}）
          </Button>
          <Button
            v-if="menuAccess.can(MENU_PERMISSIONS.BATCH_STATUS)"
            class="menu-batch-btn-enable ml-2"
            @click="onBatchUpdateStatus(1)"
          >
            批量启用
          </Button>
          <Button
            v-if="menuAccess.can(MENU_PERMISSIONS.BATCH_STATUS)"
            class="menu-batch-btn-disable ml-2"
            @click="onBatchUpdateStatus(0)"
          >
            批量禁用
          </Button>
          <Button class="ml-2" type="link" @click="clearSelection">取消选择</Button>
        </div>
        <Button
          v-else-if="menuAccess.can(MENU_PERMISSIONS.CREATE)"
          type="primary"
          @click="onCreate"
        >
          <Plus class="size-4" />
          新增菜单
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
            <span class="min-w-0 flex-auto truncate">{{
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
          <Tag :color="row.module.enabled ? 'processing' : 'warning'">
            {{ row.module.title }}
            <template v-if="!row.module.enabled"> · 已禁用</template>
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
</style>

<style lang="scss">
/** 排序列：提示可双击编辑 */
.vxe-grid .menu-sort-editable-col {
  cursor: pointer;
}

/** 批量启用 / 批量禁用（工具栏） */
.vxe-grid .menu-batch-btn-enable.ant-btn {
  color: #fff;
  background-color: #52c41a;
  border-color: #52c41a;
}

.vxe-grid .menu-batch-btn-enable.ant-btn:not(:disabled):hover {
  color: #fff;
  background-color: #73d13d;
  border-color: #73d13d;
}

.vxe-grid .menu-batch-btn-disable.ant-btn {
  color: #fff;
  background-color: #fa8c16;
  border-color: #fa8c16;
}

.vxe-grid .menu-batch-btn-disable.ant-btn:not(:disabled):hover {
  color: #fff;
  background-color: #ffa940;
  border-color: #ffa940;
}
</style>
