<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
import type { MenuNode, MenuSavePayload } from '#/api/system/menu';

import { computed, nextTick, ref } from 'vue';

import { Page, useVbenDrawer } from '@vben/common-ui';

import { ElMessage, ElMessageBox } from 'element-plus';

import { useVbenForm } from '#/adapter/form';
import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  createMenu,
  deleteMenu,
  fetchMenuTree,
  updateMenu,
} from '#/api/system/menu';

import { buildMenuFormSchema, useColumns } from './data';

const editingId = ref<number | null>(null);
const treeData = ref<MenuNode[]>([]);

function countTree(nodes: MenuNode[]): number {
  let n = 0;
  function walk(list: MenuNode[]) {
    for (const x of list) {
      n++;
      if (x.children?.length) {
        walk(x.children);
      }
    }
  }
  walk(nodes);
  return n;
}

function flattenParentOptions(
  nodes: MenuNode[],
  depth = 0,
  skipId?: number,
): { label: string; value: number }[] {
  const out: { label: string; value: number }[] = [];
  const pad = `${'\u3000'.repeat(depth)}`;
  for (const x of nodes) {
    if (skipId !== undefined && x.id === skipId) {
      if (x.children?.length) {
        out.push(...flattenParentOptions(x.children, depth, skipId));
      }
      continue;
    }
    out.push({
      label: `${pad}${x.name}`,
      value: x.id,
    });
    if (x.children?.length) {
      out.push(...flattenParentOptions(x.children, depth + 1, skipId));
    }
  }
  return out;
}

const parentSelectOptions = computed(() =>
  flattenParentOptions(treeData.value, 0, editingId.value ?? undefined),
);

const menuFormSchema = computed(() =>
  buildMenuFormSchema(parentSelectOptions.value),
);

const [Form, formApi] = useVbenForm({
  schema: menuFormSchema as any,
  showDefaultActions: false,
});

const [Drawer, drawerApi] = useVbenDrawer({
  async onConfirm() {
    const { valid } = await formApi.validate();
    if (!valid) {
      return;
    }
    const values = (await formApi.getValues()) as MenuSavePayload;
    drawerApi.lock();
    try {
      if (!editingId.value) {
        await createMenu(values);
        ElMessage.success('创建成功');
      } else {
        await updateMenu(editingId.value, values);
        ElMessage.success('已保存');
      }
      drawerApi.close();
      gridApi.query();
    } catch {
      drawerApi.unlock();
    } finally {
      drawerApi.unlock();
    }
  },
});

function onActionClick({ code, row }: OnActionClickParams<MenuNode>) {
  if (code === 'append') {
    openCreate(row.id);
  } else if (code === 'edit') {
    openEdit(row);
  } else if (code === 'delete') {
    onDelete(row);
  }
}

function openCreate(parentId?: number) {
  editingId.value = null;
  formApi.resetForm();
  drawerApi
    .setState({ title: parentId ? '新增子项' : '新增菜单' })
    .open();
  nextTick(() => {
    formApi.setValues({
      component: '',
      icon: '',
      keep_alive: 0,
      name: '',
      parent_id: parentId ?? 0,
      path: '',
      permission: '',
      remark: '',
      sort: 0,
      status: 1,
      type: 2,
      visible: 1,
    });
  });
}

function openEdit(row: MenuNode) {
  editingId.value = row.id;
  formApi.resetForm();
  drawerApi.setState({ title: '编辑菜单' }).open();
  nextTick(() => {
    formApi.setValues({
      component: row.component ?? '',
      icon: row.icon ?? '',
      keep_alive: row.keep_alive,
      name: row.name,
      parent_id: row.parent_id,
      path: row.path ?? '',
      permission: row.permission ?? '',
      remark: row.remark ?? '',
      sort: row.sort,
      status: row.status,
      type: row.type,
      visible: row.visible,
    });
  });
}

async function onDelete(row: MenuNode) {
  if (row.children?.length) {
    ElMessage.warning('请先删除子节点');
    return;
  }
  await ElMessageBox.confirm(`确定删除「${row.name}」？`, '提示', {
    type: 'warning',
  });
  await deleteMenu(row.id);
  ElMessage.success('已删除');
  gridApi.query();
}

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
          const items = await fetchMenuTree();
          treeData.value = items;
          return { items, total: countTree(items) };
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
    treeConfig: {
      childrenField: 'children',
      rowField: 'id',
      transform: false,
    },
  } as VxeTableGridOptions<MenuNode>,
});
</script>

<template>
  <Page auto-content-height>
    <Drawer class="w-[560px]">
      <Form />
    </Drawer>
    <Grid>
      <template #toolbar-actions>
        <ElButton type="primary" @click="() => openCreate()">新增根菜单</ElButton>
      </template>
    </Grid>
  </Page>
</template>
