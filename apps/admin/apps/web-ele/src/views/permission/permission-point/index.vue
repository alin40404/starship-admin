<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { MenuNode } from '#/api/system/menu';

import { ElMessage } from 'element-plus';

import { Page } from '@vben/common-ui';

import { useVbenVxeGrid } from '#/adapter/vxe-table';
import { fetchMenuPage, fetchMenuTree } from '#/api/system/menu';

import { useColumns } from './data';

function buildIdNameMap(nodes: MenuNode[]): Map<number, string> {
  const map = new Map<number, string>();
  function walk(list: MenuNode[]) {
    for (const n of list) {
      map.set(n.id, n.name);
      if (n.children?.length) {
        walk(n.children);
      }
    }
  }
  walk(nodes);
  return map;
}

function onActionClick({ code }: OnActionClickParams<MenuNode>) {
  if (code === 'hint') {
    ElMessage.info('请在「菜单管理」中编辑类型为「按钮」的菜单项。');
  }
}

const [Grid] = useVbenVxeGrid({
  gridOptions: {
    columns: useColumns(onActionClick),
    height: 'auto',
    keepSource: true,
    proxyConfig: {
      ajax: {
        query: async ({ page }) => {
          const tree = await fetchMenuTree();
          const idName = buildIdNameMap(tree);
          const { items, total } = await fetchMenuPage({
            page: page.currentPage,
            per_page: page.pageSize,
            type: 3,
          });
          const rows = items.map((row) => ({
            ...row,
            _parentName:
              row.parent_id && idName.has(row.parent_id)
                ? idName.get(row.parent_id)
                : '—',
          }));
          return { items: rows, total };
        },
      },
    },
    rowConfig: {
      keyField: 'id',
    },
    toolbarConfig: {
      custom: true,
      refresh: true,
      zoom: true,
    },
  } as VxeTableGridOptions<MenuNode & { _parentName?: string }>,
});
</script>

<template>
  <Page auto-content-height>
    <ElAlert
      class="mb-4"
      title="权限点对应菜单类型为「按钮」的项，请在菜单管理中维护。"
      type="info"
      show-icon
      :closable="false"
    />
    <Grid />
  </Page>
</template>
