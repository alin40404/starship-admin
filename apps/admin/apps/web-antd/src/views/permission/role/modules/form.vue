<script lang="ts" setup>
import type { FlattenedItem } from '@vben/common-ui';
import type { Recordable } from '@vben/types';
import type { VbenFormSchema } from '#/adapter/form';
import type { MenuTreeNode } from '#/api/system/menu';
import type { RoleDetail, RoleRow, RoleSavePayload } from '#/api/system/role';

import { computed, nextTick, reactive, ref, watch } from 'vue';

import { Tree, useVbenDrawer } from '@vben/common-ui';
import { IconifyIcon } from '@vben/icons';

import { message, Spin } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import {
  createRole,
  fetchRole,
  syncRoleMenus,
  updateRole,
} from '#/api/system/role';
import { menuIconToIconify } from '#/api/core/menu';
import { fetchMenuTree } from '#/api/system/menu';

import { buildRoleFormSchema } from '../data';

const emits = defineEmits<{ success: [] }>();

type OpenMode = 'create' | 'edit' | 'view';

const MENU_TYPE_MENU = 2;
const MENU_TYPE_BUTTON = 3;

const editingId = ref<number | null>(null);
const openMode = ref<OpenMode>('create');
const menuTree = ref<MenuTreeNode[]>([]);
const menuReady = ref(false);

const isViewOnly = computed(() => openMode.value === 'view');

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
    const meta: Record<string, unknown> = { title: n.name };
    const iconify = menuIconToIconify(n.icon);
    if (iconify) {
      meta.icon = iconify;
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

function getAuthNodeClass(item: FlattenedItem<Recordable<any>>) {
  const classes: string[] = [];
  if (item.value?.type === 'button') {
    classes.push('inline-flex');
  }
  return classes.join(' ');
}

const [Form, formApi] = useVbenForm(
  reactive({
    commonConfig: {
      componentProps: {
        class: 'w-full',
      },
    },
    layout: 'horizontal' as const,
    schema: [] as VbenFormSchema[],
    showDefaultActions: false,
    wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
  }) as any,
);

watch(
  [editingId, openMode],
  () => {
    formApi.setState({
      schema: buildRoleFormSchema({
        isEdit: editingId.value !== null,
        lockBuiltin: false,
        readonly: openMode.value === 'view',
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

function stripMenuIds(values: Record<string, unknown>): RoleSavePayload {
  const { menu_ids: _m, ...rest } = values;
  return rest as unknown as RoleSavePayload;
}

const [Drawer, drawerApi] = useVbenDrawer({
  async onConfirm() {
    if (openMode.value === 'view') {
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
    const values = stripMenuIds(raw);
    drawerApi.lock();
    try {
      if (!editingId.value) {
        const created = await createRole(values);
        await syncRoleMenus(created.id, menuIds);
        message.success('创建成功');
      } else {
        await updateRole(editingId.value, values);
        await syncRoleMenus(editingId.value, menuIds);
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

    if (openMode.value === 'view') {
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
        menu_ids: detail.menu_ids ?? [],
        name: detail.name,
        remark: detail.remark ?? '',
        sort: detail.sort ?? 0,
        status: detail.status,
      });
    } else {
      formApi.setValues({
        code: '',
        data_scope: 1,
        menu_ids: [],
        name: '',
        remark: '',
        sort: 0,
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
</script>

<template>
  <Drawer :title="drawerTitle" class="max-w-[720px] w-[92vw]">
    <Form class="role-drawer-form">
      <template #menu_ids="slotProps">
        <Spin :spinning="!menuReady" wrapper-class-name="w-full">
          <div
            :class="[
              'w-full',
              { 'permission-tree--readonly': isViewOnly },
            ]"
          >
            <Tree
              :tree-data="vbenTreeData"
              multiple
              bordered
              :default-expanded-level="2"
              :disabled="isViewOnly"
              :get-node-class="getAuthNodeClass"
              v-bind="slotProps"
              value-field="id"
              label-field="meta.title"
              icon-field="meta.icon"
            >
              <template #node="{ value }">
                <IconifyIcon v-if="value.meta?.icon" :icon="value.meta.icon" />
                {{ value.meta?.title }}
              </template>
            </Tree>
          </div>
        </Spin>
      </template>
    </Form>
  </Drawer>
</template>

<style scoped>
.role-drawer-form :deep(.ant-form-item-label) {
  flex: 0 0 88px;
  max-width: 88px;
}

.role-drawer-form :deep(.permission-tree--readonly button[role='checkbox']) {
  pointer-events: none;
}
</style>
