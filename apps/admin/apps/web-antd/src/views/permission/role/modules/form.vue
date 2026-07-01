<script lang="ts" setup>
import type { FlattenedItem } from '@vben/common-ui';
import type { Recordable } from '@vben/types';
import type { VbenFormSchema } from '#/adapter/form';
import type { DeptNode } from '#/api/system/department';
import type { MenuTreeNode } from '#/api/system/menu';
import type { RoleDetail, RoleRow, RoleSavePayload } from '#/api/system/role';

import { computed, nextTick, reactive, ref, watch } from 'vue';

import { useAccess } from '@vben/access';
import { Tree, useVbenDrawer } from '@vben/common-ui';
import { IconifyIcon } from '@vben/icons';

import { message, Spin } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import {
  createRole,
  fetchRole,
  syncRoleDepts,
  syncRoleMenus,
  updateRole,
} from '#/api/system/role';
import { fetchDepartmentTree } from '#/api/system/department';
import { fetchMenuTree } from '#/api/system/menu';
import { ROLE_PERMISSIONS } from '#/constants/role-permissions';

import { buildRoleFormSchema, DATA_SCOPE_CUSTOM } from '../data';

const emits = defineEmits<{ success: [] }>();

const { hasAccessByCodes } = useAccess();

type OpenMode = 'create' | 'edit' | 'view';

const MENU_TYPE_MENU = 2;
const MENU_TYPE_BUTTON = 3;

const editingId = ref<number | null>(null);
const openMode = ref<OpenMode>('create');
const menuTree = ref<MenuTreeNode[]>([]);
const menuReady = ref(false);
const deptTree = ref<DeptNode[]>([]);
const deptReady = ref(false);

const isViewOnly = computed(() => openMode.value === 'view');

const canWriteRole = computed(() => {
  if (isViewOnly.value) {
    return false;
  }
  if (openMode.value === 'create') {
    return hasAccessByCodes([ROLE_PERMISSIONS.CREATE]);
  }
  if (openMode.value === 'edit') {
    return hasAccessByCodes([ROLE_PERMISSIONS.UPDATE]);
  }
  return false;
});

function mapMenuToVbenTree(
  nodes: MenuTreeNode[],
): Record<string, unknown>[] {
  return nodes.map((n) => {
    const typeStr =
      n.type === MENU_TYPE_BUTTON
        ? 'button'
        : n.type === MENU_TYPE_MENU
          ? 'menu'
          : 'catalog';
    const meta: Record<string, unknown> = {
      title: (n.title && n.title.trim()) || n.name,
    };
    const icon = n.icon?.trim();
    if (icon) {
      meta.icon = icon;
    }
    return {
      children: n.children?.length
        ? mapMenuToVbenTree(n.children)
        : undefined,
      id: n.id,
      meta,
      type: typeStr,
    };
  });
}

const vbenTreeData = computed(() => mapMenuToVbenTree(menuTree.value));

function mapDeptToVbenTree(nodes: DeptNode[]): Record<string, unknown>[] {
  return nodes.map((n) => ({
    children: n.children?.length
      ? mapDeptToVbenTree(n.children)
      : undefined,
    id: n.id,
    meta: { title: n.name },
  }));
}

const vbenDeptTreeData = computed(() => mapDeptToVbenTree(deptTree.value));

function getAuthNodeClass(item: FlattenedItem<Recordable<any>>) {
  const type = item.value?.type as string | undefined;
  if (type === 'button') {
    return 'permission-tree-btn';
  }
  if (type === 'menu') {
    return 'permission-tree-menu';
  }
  if (type === 'catalog') {
    return 'permission-tree-catalog';
  }
  return '';
}

const [Form, formApi] = useVbenForm(
  reactive({
    commonConfig: {
      componentProps: {
        class: 'w-full',
      },
      labelClass: 'justify-start',
    },
    layout: 'vertical' as const,
    schema: [] as VbenFormSchema[],
    showDefaultActions: false,
    wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
  }) as any,
);

watch(
  [editingId, openMode, canWriteRole],
  () => {
    formApi.setState({
      schema: buildRoleFormSchema({
        isEdit: editingId.value !== null,
        lockBuiltin: false,
        readonly: !canWriteRole.value,
      }),
    });
  },
  { immediate: true },
);

const drawerTitle = computed(() => {
  if (openMode.value === 'view') {
    return '查看角色';
  }
  return editingId.value ? '编辑角色' : '新增角色';
});

function stripRolePayload(values: Record<string, unknown>): RoleSavePayload {
  const { menu_ids: _m, dept_ids: _d, ...rest } = values;
  return rest as unknown as RoleSavePayload;
}

const [Drawer, drawerApi] = useVbenDrawer({
  async onConfirm() {
    if (!canWriteRole.value) {
      drawerApi.close();
      return;
    }
    const { valid } = await formApi.validate();
    if (!valid) {
      return;
    }
    const raw = (await formApi.getValues()) as Record<string, unknown>;
    const menuIds = ((raw.menu_ids as (number | string)[] | undefined) ?? []).map(
      (k) => Number(k),
    );
    const dataScope = Number(raw.data_scope);
    const deptIds =
      dataScope === DATA_SCOPE_CUSTOM
        ? ((raw.dept_ids as (number | string)[] | undefined) ?? []).map((k) =>
            Number(k),
          )
        : [];
    const values = stripRolePayload(raw);
    drawerApi.lock();
    try {
      if (!editingId.value) {
        const created = await createRole(values);
        await syncRoleMenus(created.id, menuIds);
        if (dataScope === DATA_SCOPE_CUSTOM) {
          await syncRoleDepts(created.id, deptIds);
        }
        message.success('创建成功');
      } else {
        await updateRole(editingId.value, values);
        await syncRoleMenus(editingId.value, menuIds);
        if (dataScope === DATA_SCOPE_CUSTOM) {
          await syncRoleDepts(editingId.value, deptIds);
        }
        message.success('已保存');
      }
      drawerApi.close();
      emits('success');
    } finally {
      drawerApi.unlock();
    }
  },

  async onOpenChange(isOpen: boolean) {
    if (!isOpen) {
      drawerApi.setState({
        cancelText: undefined,
        showConfirmButton: true,
      });
      return;
    }

    await ensureMenuTree();
    await ensureDeptTree();

    const data = drawerApi.getData<
      (RoleRow & { _mode?: 'view' }) | Record<string, never>
    >();
    formApi.resetForm();

    const id =
      data && typeof (data as RoleRow).id === 'number'
        ? (data as RoleRow).id
        : null;
    const isView = (data as RoleRow & { _mode?: 'view' })?._mode === 'view';

    editingId.value = id;
    openMode.value = isView ? 'view' : id ? 'edit' : 'create';

    if (openMode.value === 'view' || !canWriteRole.value) {
      drawerApi.setState({
        cancelText: '关闭',
        showConfirmButton: false,
      });
    } else {
      drawerApi.setState({
        cancelText: undefined,
        showConfirmButton: true,
      });
    }

    await nextTick();

    if (id) {
      let detail: RoleDetail;
      try {
        detail = await fetchRole(id);
      } catch {
        return;
      }
      formApi.setValues({
        code: detail.code,
        data_scope: detail.data_scope,
        dept_ids: detail.dept_ids ?? [],
        menu_ids: detail.menu_ids ?? [],
        name: detail.name,
        remark: detail.remark ?? '',
        status: detail.status,
      });
    } else {
      formApi.setValues({
        code: '',
        data_scope: 1,
        dept_ids: [],
        menu_ids: [],
        name: '',
        remark: '',
        status: 1,
      });
    }
  },
});

async function loadMenuTree() {
  menuTree.value = await fetchMenuTree();
  menuReady.value = true;
}

async function ensureMenuTree() {
  if (!menuReady.value) {
    await loadMenuTree();
  }
}

async function loadDeptTree() {
  deptTree.value = await fetchDepartmentTree(true);
  deptReady.value = true;
}

async function ensureDeptTree() {
  if (!deptReady.value) {
    await loadDeptTree();
  }
}

/** 点击「全部」文案时同步触发工具行全选 checkbox */
function handlePermissionTreeCheckAllClick(event: MouseEvent) {
  if (!canWriteRole.value) return;

  const target = event.target as HTMLElement | null;
  if (!target) return;

  const toolbar = target.closest(
    '.permission-tree .container.list-none > div > .flex.size-5.flex-1.cursor-pointer',
  );
  if (!toolbar) return;

  if (target.closest('button[role="checkbox"]')) return;

  event.preventDefault();
  event.stopPropagation();

  toolbar.querySelector<HTMLButtonElement>('button[role="checkbox"]')?.click();
}
</script>

<template>
  <Drawer :title="drawerTitle" class="max-w-[720px] w-[92vw]">
    <Form class="role-drawer-form">
      <template #dept_ids="slotProps">
        <Spin :spinning="!deptReady" wrapper-class-name="w-full">
          <div
            :class="[
              'dept-tree w-full',
              { 'dept-tree--readonly': !canWriteRole },
            ]"
          >
            <Tree
              :tree-data="vbenDeptTreeData"
              multiple
              bordered
              :default-expanded-level="10"
              :disabled="!canWriteRole"
              v-bind="slotProps"
              value-field="id"
              label-field="meta.title"
            />
          </div>
        </Spin>
      </template>
      <template #menu_ids="slotProps">
        <Spin :spinning="!menuReady" wrapper-class-name="w-full">
          <div
            :class="[
              'permission-tree w-full',
              { 'permission-tree--readonly': !canWriteRole },
            ]"
            @click.capture="handlePermissionTreeCheckAllClick"
          >
            <Tree
              :tree-data="vbenTreeData"
              multiple
              bordered
              :default-expanded-level="10"
              :disabled="!canWriteRole"
              :get-node-class="getAuthNodeClass"
              v-bind="slotProps"
              value-field="id"
              label-field="meta.title"
              icon-field="meta.icon"
            >
              <template #node="{ value }">
                <IconifyIcon
                  v-if="value.meta?.icon && value.type !== 'button'"
                  class="size-4 shrink-0 text-primary"
                  :icon="value.meta.icon"
                />
                <span
                  :class="{
                    'permission-tree-btn-label min-w-0 flex-1 truncate':
                      value.type === 'button',
                    'permission-tree-menu-label min-w-0 truncate':
                      value.type === 'menu',
                    'permission-tree-catalog-label min-w-0 truncate':
                      value.type === 'catalog',
                  }"
                  :title="String(value.meta?.title ?? '')"
                >
                  {{ value.meta?.title }}
                </span>
              </template>
            </Tree>
          </div>
        </Spin>
      </template>
    </Form>
  </Drawer>
</template>

<style scoped>
.role-drawer-form :deep(.grid > .col-span-full) {
  width: 100%;
}

/** 角色权限树：默认全展开，保留边框与顶部全选，三级层次排版 */
.role-drawer-form {
  --permission-tree-pl: 0.5rem;
}

.role-drawer-form :deep(.permission-tree .container.list-none) {
  border-radius: 0.375rem;
}

/* 工具行：保留全选/反选 checkbox，仅隐藏展开箭头并禁用展开收起点击 */
.role-drawer-form
  :deep(
    .permission-tree .container.list-none > div:has(> .flex.size-5.flex-1.cursor-pointer)
  ) {
  padding: 0.375rem 0.5rem 0.375rem var(--permission-tree-pl);
  cursor: default;
}

.role-drawer-form
  :deep(
    .permission-tree
      .container.list-none
      > div:has(> .flex.size-5.flex-1.cursor-pointer)
      > .flex.size-5.flex-1.cursor-pointer
  ) {
  display: inline-flex;
  flex: none;
  width: auto;
  align-items: center;
  gap: 0.375rem;
  cursor: pointer;
}

.role-drawer-form
  :deep(
    .permission-tree
      .container.list-none
      > div:has(> .flex.size-5.flex-1.cursor-pointer)
      > .flex.size-5.flex-1.cursor-pointer::after
  ) {
  content: '全部';
  font-size: 0.875rem;
  font-weight: 500;
  line-height: 1.25rem;
  color: hsl(var(--foreground));
}

/* 仅隐藏工具行展开箭头，不影响 checkbox 内勾选图标 */
.role-drawer-form
  :deep(
    .permission-tree
      .container.list-none
      > div:has(> .flex.size-5.flex-1.cursor-pointer)
      > .flex.size-5.flex-1.cursor-pointer
      > svg
  ) {
  display: none;
}

/* 去掉节点箭头与占位，层级缩进改由内容区表达，checkbox 第一列统一对齐 */
.role-drawer-form :deep(.permission-tree .tree-node) {
  margin-left: 0 !important;
  padding-right: 0.5rem;
  padding-left: var(--permission-tree-pl) !important;
  border-radius: 0.25rem;
}

.role-drawer-form :deep(.permission-tree .tree-node > :first-child),
.role-drawer-form :deep(.permission-tree .tree-node > :last-child) {
  display: none;
}

.role-drawer-form :deep(.permission-tree .tree-node > .flex.items-center.gap-1) {
  min-width: 0;
  flex: 1;
  align-items: center;
}

.role-drawer-form
  :deep(.permission-tree .tree-node button[role='checkbox']) {
  flex-shrink: 0;
}

/* 一级：目录模块 */
.role-drawer-form :deep(.permission-tree .tree-node.permission-tree-catalog) {
  position: relative;
  display: flex !important;
  width: 100% !important;
  margin-top: 0.75rem;
  padding: 0.5rem 0.75rem 0.5rem var(--permission-tree-pl);
  background: hsl(var(--muted) / 0.45);
  border-left: none;
  font-weight: 600;
}

.role-drawer-form :deep(.permission-tree .tree-node.permission-tree-catalog::before) {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  width: 3px;
  background: hsl(var(--primary));
  content: '';
}

.role-drawer-form
  :deep(.permission-tree .tree-node.permission-tree-catalog:first-child) {
  margin-top: 0;
}

.role-drawer-form :deep(.permission-tree-catalog-label) {
  font-size: 0.9375rem;
  font-weight: 600;
  line-height: 1.375rem;
  color: hsl(var(--foreground));
}

/* 二级：页面菜单 */
.role-drawer-form :deep(.permission-tree .tree-node.permission-tree-menu) {
  display: flex !important;
  width: 100% !important;
  margin-top: 0.375rem;
  padding: 0.375rem 0.75rem 0.25rem var(--permission-tree-pl);
}

.role-drawer-form :deep(.permission-tree-menu-label) {
  font-size: 0.8125rem;
  font-weight: 400;
  line-height: 1.125rem;
  color: hsl(var(--foreground) / 0.78);
}

/* 三级：按钮权限，每行 4 个 */
.role-drawer-form :deep(.permission-tree .tree-node.permission-tree-btn) {
  display: inline-flex !important;
  box-sizing: border-box;
  width: 25% !important;
  max-width: 25%;
  margin-top: 0;
  padding: 0.25rem 0.5rem 0.25rem var(--permission-tree-pl);
  vertical-align: top;
}

.role-drawer-form
  :deep(
    .permission-tree
      .tree-node.permission-tree-btn
      > .flex.items-center.gap-1
      > .flex.items-center.gap-1
  ) {
  min-width: 0;
  flex: 1;
  overflow: hidden;
}

.role-drawer-form :deep(.permission-tree .tree-node.permission-tree-btn > .flex.items-center.gap-1) {
  min-width: 0;
  flex: 1;
  overflow: hidden;
}

.role-drawer-form :deep(.permission-tree-btn-label) {
  display: block;
  font-size: 0.8125rem;
  font-weight: 400;
  line-height: 1.125rem;
  color: hsl(var(--foreground) / 0.78);
}

.role-drawer-form :deep(.permission-tree--readonly) {
  @apply text-muted-foreground;
}

.role-drawer-form :deep(.permission-tree--readonly .tree-node) {
  @apply cursor-default;
}

.role-drawer-form :deep(.permission-tree--readonly button[role='checkbox']) {
  pointer-events: none;
  @apply opacity-100;
}

.role-drawer-form
  :deep(
    .permission-tree--readonly button[role='checkbox'][data-state='checked']
  ) {
  @apply border-border bg-muted text-muted-foreground;
}

.role-drawer-form :deep(.permission-tree--readonly button[role='checkbox'][data-state='checked'][data-disabled]) {
  @apply opacity-90;
}

.role-drawer-form :deep(.dept-tree .container.list-none) {
  border-radius: 0.375rem;
}

.role-drawer-form :deep(.dept-tree--readonly) {
  @apply text-muted-foreground;
}

.role-drawer-form :deep(.dept-tree--readonly button[role='checkbox']) {
  pointer-events: none;
  @apply opacity-100;
}
</style>
