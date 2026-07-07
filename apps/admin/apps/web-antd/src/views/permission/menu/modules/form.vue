<script lang="ts" setup>
import type { VbenFormSchema } from '#/adapter/form';
import type {
  MenuOtherSettingsPayload,
  MenuSavePayload,
  MenuTreeNode,
} from '#/api/system/menu';

import { computed, nextTick, reactive, ref, watch } from 'vue';

import { useVbenDrawer } from '@vben/common-ui';
import { usePreferences } from '@vben/preferences';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import {
  createMenu,
  fetchMenuTree,
  isMenuPathDuplicate,
  updateMenu,
} from '#/api/system/menu';

import { buildMenuFormSchema } from '../data';

/** 按钮类型不使用其它设置，提交时固定为全 0 */
const ZERO_OTHER_SETTINGS: MenuOtherSettingsPayload = {
  affix_tab: 0,
  hide_in_breadcrumb: 0,
  hide_in_tab: 0,
  hide_menu: 0,
  hide_submenu: 0,
  keep_alive: 0,
};

const emits = defineEmits<{ success: [] }>();

const { isMobile } = usePreferences();

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
  isMobile,
  (mobile) => {
    formApi.setState({
      commonConfig: {
        colon: !mobile,
        componentProps: { class: 'w-full' },
        formItemClass: mobile ? 'col-span-full' : 'col-span-2 md:col-span-1',
        labelClass: mobile ? 'justify-start' : undefined,
      },
      layout: mobile ? 'vertical' : 'horizontal',
      wrapperClass: mobile ? 'grid-cols-1' : 'grid-cols-2 gap-x-4',
    });
  },
  { immediate: true },
);

watch(
  editingId,
  () => {
    formApi.setState({
      schema: buildMenuFormSchema({
        parentTreeLoader: loadMenuTreeForParentSelect,
        parentTreeRefreshKey: editingId.value ?? undefined,
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
    const needsPath = [1, 2, 4].includes(type);
    if (needsPath && !String(raw.path ?? '').trim()) {
      message.warning('请填写路由地址');
      return;
    }
    if (type === 3 && !String(raw.permission ?? '').trim()) {
      message.warning('请填写权限标识');
      return;
    }
    const pathTrim = String(raw.path ?? '').trim();
    if (![3, 5].includes(type) && pathTrim !== '') {
      const duplicate = await isMenuPathDuplicate(
        pathTrim,
        editingId.value ?? undefined,
      );
      if (duplicate) {
        message.warning('路由地址已存在');
        return;
      }
    }
    const parentId =
      raw.parent_id === undefined || raw.parent_id === null
        ? 0
        : Number(raw.parent_id);
    const hideMenu = Boolean(raw.hide_menu);
    const to01 = (v: unknown) => (v === true || v === 1 ? 1 : 0);
    /** 与表单 show 条件一致，避免切换类型后残留勾选被提交 */
    const otherSettings: MenuOtherSettingsPayload =
      type === 3
        ? { ...ZERO_OTHER_SETTINGS }
        : (() => {
            const base: MenuOtherSettingsPayload = {
              affix_tab: 0,
              hide_in_breadcrumb: 0,
              hide_in_tab: 0,
              hide_menu: hideMenu ? 1 : 0,
              hide_submenu: 0,
              keep_alive: 0,
            };
            if (type === 1) {
              return {
                ...base,
                hide_in_breadcrumb: to01(raw.hide_in_breadcrumb),
                hide_in_tab: to01(raw.hide_in_tab),
                hide_submenu: to01(raw.hide_submenu),
              };
            }
            if (type === 2) {
              return {
                ...base,
                affix_tab: to01(raw.affix_tab),
                hide_in_breadcrumb: to01(raw.hide_in_breadcrumb),
                hide_in_tab: to01(raw.hide_in_tab),
                hide_submenu: to01(raw.hide_submenu),
                keep_alive: to01(raw.keep_alive),
              };
            }
            if (type === 4) {
              return {
                ...base,
                affix_tab: to01(raw.affix_tab),
                hide_in_breadcrumb: to01(raw.hide_in_breadcrumb),
                hide_in_tab: to01(raw.hide_in_tab),
              };
            }
            // 外链：仅「隐藏菜单」与 playground 一致，其余项不展示且不落库
            return base;
          })();
    const payload: MenuSavePayload = {
      active_path: String(raw.active_path ?? '').trim() || null,
      active_icon: (raw.active_icon as string) || null,
      badge: (raw.badge as string) || null,
      badge_type: (raw.badge_type as string) || null,
      badge_variants: (raw.badge_variants as string) || null,
      component: (raw.component as string) || null,
      icon: (raw.icon as string) || null,
      iframe_src: (raw.iframe_src as string) || null,
      link_url: (raw.link_url as string) || null,
      name: String(raw.name ?? ''),
      other_settings: otherSettings,
      parent_id: parentId,
      path: type === 3 || type === 5 ? null : (raw.path as string) || null,
      permission: (raw.permission as string) || null,
      remark: (raw.remark as string) || null,
      sort: Number(raw.sort ?? 0),
      status: Number(raw.status ?? 1),
      title: String(raw.title ?? '').trim() || null,
      type,
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
        active_path: row.active_path ?? '',
        active_icon: row.active_icon ?? '',
        badge: row.badge ?? '',
        badge_type: row.badge_type ?? undefined,
        badge_variants: row.badge_variants ?? undefined,
        affix_tab: row.affix_tab === 1,
        component: row.component ?? '',
        hide_in_breadcrumb: row.hide_in_breadcrumb === 1,
        hide_in_tab: row.hide_in_tab === 1,
        hide_menu: row.visible === 0,
        hide_submenu: row.hide_submenu === 1,
        icon: row.icon ?? '',
        iframe_src: row.iframe_src ?? '',
        keep_alive: row.keep_alive === 1,
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
        active_path: '',
        active_icon: '',
        badge: '',
        badge_type: undefined,
        badge_variants: undefined,
        affix_tab: false,
        component: '',
        hide_in_breadcrumb: false,
        hide_in_tab: false,
        hide_menu: false,
        hide_submenu: false,
        icon: '',
        iframe_src: '',
        keep_alive: false,
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

/* 徽标样式：选中后在文案前显示与下拉一致的色块 */
.menu-drawer-form
  :deep([class*='menu-form-badge-variant--'] .ant-select-selector .ant-select-selection-item) {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.menu-drawer-form
  :deep([class*='menu-form-badge-variant--'] .ant-select-selector .ant-select-selection-item::before) {
  content: '';
  flex-shrink: 0;
  width: 0.625rem;
  height: 0.625rem;
  border-radius: 9999px;
  border: 1px solid rgb(0 0 0 / 10%);
}

.menu-drawer-form
  :deep(.menu-form-badge-variant--default .ant-select-selector .ant-select-selection-item::before) {
  background-color: #22c55e;
}

.menu-drawer-form
  :deep(.menu-form-badge-variant--destructive .ant-select-selector .ant-select-selection-item::before) {
  background-color: #ef4444;
}

.menu-drawer-form
  :deep(.menu-form-badge-variant--primary .ant-select-selector .ant-select-selection-item::before) {
  background-color: #3b82f6;
}

.menu-drawer-form
  :deep(.menu-form-badge-variant--success .ant-select-selector .ant-select-selection-item::before) {
  background-color: #22c55e;
}

.menu-drawer-form
  :deep(.menu-form-badge-variant--warning .ant-select-selector .ant-select-selection-item::before) {
  background-color: #eab308;
}
</style>
