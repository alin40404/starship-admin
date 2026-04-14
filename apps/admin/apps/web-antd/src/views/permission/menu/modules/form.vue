<script lang="ts" setup>
import type { VbenFormSchema } from '#/adapter/form';
import type { MenuSavePayload, MenuTreeNode } from '#/api/system/menu';

import { computed, nextTick, reactive, ref, watch } from 'vue';

import { useVbenDrawer } from '@vben/common-ui';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { createMenu, fetchMenuTree, updateMenu } from '#/api/system/menu';

import { buildMenuFormSchema } from '../data';

const emits = defineEmits<{ success: [] }>();

const editingId = ref<number | null>(null);

function collectSubtreeIds(nodes: MenuTreeNode[], rootId: number): Set<number> {
  const ids = new Set<number>();
  function findBranch(list: MenuTreeNode[]): MenuTreeNode | null {
    for (const n of list) {
      if (n.id === rootId) {
        return n;
      }
      if (n.children?.length) {
        const c = findBranch(n.children);
        if (c) {
          return c;
        }
      }
    }
    return null;
  }
  function addAll(n: MenuTreeNode) {
    ids.add(n.id);
    n.children?.forEach(addAll);
  }
  const root = findBranch(nodes);
  if (root) {
    addAll(root);
  }
  return ids;
}

function filterTreeExcludeIds(
  nodes: MenuTreeNode[],
  exclude: Set<number>,
): MenuTreeNode[] {
  return nodes
    .filter((n) => !exclude.has(n.id))
    .map((n) => ({
      ...n,
      children: n.children?.length
        ? filterTreeExcludeIds(n.children, exclude)
        : [],
    }));
}

async function loadMenuTreeForParentSelect(): Promise<MenuTreeNode[]> {
  const tree = await fetchMenuTree();
  if (!tree.length) {
    return [];
  }
  if (editingId.value === null) {
    return tree;
  }
  const exclude = collectSubtreeIds(tree, editingId.value);
  return filterTreeExcludeIds(tree, exclude);
}

const [Form, formApi] = useVbenForm(
  reactive({
    commonConfig: {
      colon: true,
      componentProps: {
        class: 'w-full',
      },
      formItemClass: 'col-span-2 md:col-span-1',
    },
    layout: 'horizontal' as const,
    schema: [] as VbenFormSchema[],
    showDefaultActions: false,
    wrapperClass: 'grid-cols-2 gap-x-4',
  }) as any,
);

watch(
  editingId,
  () => {
    formApi.setState({
      schema: buildMenuFormSchema({
        parentTreeLoader: loadMenuTreeForParentSelect,
        parentTreeParams: {
          editingKey: editingId.value ?? 'new',
        },
      }),
    });
  },
  { immediate: true },
);

const drawerTitle = computed(() =>
  editingId.value ? '编辑菜单' : '新增菜单',
);

const [Drawer, drawerApi] = useVbenDrawer({
  async onConfirm() {
    const { valid } = await formApi.validate();
    if (!valid) {
      return;
    }
    const raw = (await formApi.getValues()) as Record<string, unknown>;
    const type = Number(raw.type);
    const needsPath = [1, 2, 4, 5].includes(type);
    if (needsPath && !String(raw.path ?? '').trim()) {
      message.warning('请填写路由地址');
      return;
    }
    if (type === 4 && !String(raw.iframe_src ?? '').trim()) {
      message.warning('请填写内嵌地址');
      return;
    }
    if (type === 5 && !String(raw.link_url ?? '').trim()) {
      message.warning('请填写外链地址');
      return;
    }
    const parentId =
      raw.parent_id === undefined || raw.parent_id === null
        ? 0
        : Number(raw.parent_id);
    const hideMenu = Boolean(raw.hide_menu);
    const to01 = (v: unknown) => (v === true || v === 1 ? 1 : 0);
    const payload: MenuSavePayload = {
      active_icon: (raw.active_icon as string) || null,
      badge: (raw.badge as string) || null,
      badge_type: (raw.badge_type as string) || null,
      badge_variants: (raw.badge_variants as string) || null,
      component: (raw.component as string) || null,
      hide_in_breadcrumb: to01(raw.hide_in_breadcrumb),
      hide_in_tab: to01(raw.hide_in_tab),
      hide_submenu: to01(raw.hide_submenu),
      icon: (raw.icon as string) || null,
      iframe_src: (raw.iframe_src as string) || null,
      keep_alive: Number(raw.keep_alive ?? 0),
      link_url: (raw.link_url as string) || null,
      name: String(raw.name ?? ''),
      parent_id: parentId,
      path: (raw.path as string) || null,
      permission: (raw.permission as string) || null,
      remark: (raw.remark as string) || null,
      sort: Number(raw.sort ?? 0),
      status: Number(raw.status ?? 1),
      title: String(raw.title ?? '').trim() || null,
      type,
      visible: hideMenu ? 0 : 1,
    };

    drawerApi.lock();
    try {
      if (editingId.value) {
        await updateMenu(editingId.value, payload);
        message.success('已保存');
      } else {
        await createMenu(payload);
        message.success('创建成功');
      }
      drawerApi.close();
      emits('success');
    } finally {
      drawerApi.unlock();
    }
  },

  async onOpenChange(isOpen: boolean) {
    if (!isOpen) {
      return;
    }
    const data = drawerApi.getData<
      MenuTreeNode | { parent_id?: number } | Record<string, never>
    >();
    const row = data as MenuTreeNode;
    const hasId = row && typeof row.id === 'number' && row.id > 0;
    editingId.value = hasId ? row.id : null;

    formApi.resetForm();

    if (hasId) {
      await nextTick();
      formApi.setValues({
        active_icon: row.active_icon ?? '',
        badge: row.badge ?? '',
        badge_type: row.badge_type ?? undefined,
        badge_variants: row.badge_variants ?? undefined,
        component: row.component ?? '',
        hide_in_breadcrumb: row.hide_in_breadcrumb === 1,
        hide_in_tab: row.hide_in_tab === 1,
        hide_menu: row.visible === 0,
        hide_submenu: row.hide_submenu === 1,
        icon: row.icon ?? '',
        iframe_src: row.iframe_src ?? '',
        keep_alive: row.keep_alive ?? 0,
        link_url: row.link_url ?? '',
        name: row.name,
        parent_id: row.parent_id === 0 ? undefined : row.parent_id,
        path: row.path ?? '',
        permission: row.permission ?? '',
        remark: row.remark ?? '',
        sort: row.sort ?? 0,
        status: row.status ?? 1,
        title: row.title ?? '',
        type: row.type,
      });
    } else {
      const pid = (data as { parent_id?: number }).parent_id;
      await nextTick();
      formApi.setValues({
        active_icon: '',
        badge: '',
        badge_type: undefined,
        badge_variants: undefined,
        component: '',
        hide_in_breadcrumb: false,
        hide_in_tab: false,
        hide_menu: false,
        hide_submenu: false,
        icon: '',
        iframe_src: '',
        keep_alive: 0,
        link_url: '',
        name: '',
        parent_id: pid && pid > 0 ? pid : undefined,
        path: '',
        permission: '',
        remark: '',
        sort: 0,
        status: 1,
        title: '',
        type: 2,
      });
    }
  },
});
</script>

<template>
  <Drawer :title="drawerTitle" class="w-full max-w-200">
    <Form class="menu-drawer-form mx-4" />
  </Drawer>
</template>

<style scoped>
.menu-drawer-form :deep(.ant-form-item-label) {
  flex: 0 0 100px;
  max-width: 100px;
}
</style>
