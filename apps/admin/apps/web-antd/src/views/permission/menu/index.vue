<script lang="ts" setup>
import type {
  OnActionClickParams,
  VxeTableGridOptions,
} from '#/adapter/vxe-table';
import type { MenuTreeNode } from '#/api/system/menu';

import { Page, useVbenDrawer } from '@vben/common-ui';
import { IconifyIcon, Plus } from '@vben/icons';
import { MenuBadge } from '@vben-core/menu-ui';

import { Button, message, Modal } from 'ant-design-vue';

import { deleteMenu, fetchMenuTree } from '#/api/system/menu';
import { useVbenVxeGrid } from '#/adapter/vxe-table';

import { menuDisplayLabel, useColumns } from './data';
import MenuForm from './modules/form.vue';

const [MenuFormDrawer, menuFormDrawerApi] = useVbenDrawer({
  connectedComponent: MenuForm,
  destroyOnClose: true,
});

const [Grid, gridApi] = useVbenVxeGrid({
  gridOptions: {
    columns: useColumns(onActionClick),
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

function onDelete(row: MenuTreeNode) {
  Modal.confirm({
    content: `确定删除「${menuDisplayLabel(row)}」吗？若存在子菜单可能删除失败。`,
    okText: '删除',
    okType: 'danger',
    title: '删除菜单',
    async onOk() {
      await deleteMenu(row.id);
      message.success('已删除');
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
        <Button type="primary" @click="onCreate">
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
