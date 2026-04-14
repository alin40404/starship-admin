<script lang="ts" setup>
import type { VbenFormSchema } from '#/adapter/form';
import type { MenuTreeNode } from '#/api/system/menu';
import type { RoleDetail, RoleRow, RoleSavePayload } from '#/api/system/role';

import { computed, nextTick, reactive, ref, watch } from 'vue';

import { useVbenDrawer } from '@vben/common-ui';

import { message, Spin, Tree } from 'ant-design-vue';
import type { TreeProps } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import {
  createRole,
  fetchRole,
  syncRoleMenus,
  updateRole,
} from '#/api/system/role';
import { fetchMenuTree } from '#/api/system/menu';

import { buildRoleFormSchema } from '../data';

const emits = defineEmits<{ success: [] }>();

type OpenMode = 'create' | 'edit' | 'view';

const editingId = ref<number | null>(null);
const openMode = ref<OpenMode>('create');
const menuTree = ref<MenuTreeNode[]>([]);
const menuReady = ref(false);

const isViewOnly = computed(() => openMode.value === 'view');

const treeData = computed((): TreeProps['treeData'] =>
  mapMenuToTreeData(menuTree.value),
);

function mapMenuToTreeData(nodes: MenuTreeNode[]): NonNullable<TreeProps['treeData']> {
  return nodes.map((n) => ({
    children:
      n.children?.length ? mapMenuToTreeData(n.children) : undefined,
    key: n.id,
    title: n.name,
  }));
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
      <template #menu_ids="p">
        <Spin :spinning="!menuReady" class="w-full">
          <div
            class="auth-tree-box max-h-[min(420px,50vh)] w-full overflow-y-auto rounded-md border border-[var(--ant-color-border)] px-2 py-2"
          >
            <Tree
              v-if="treeData?.length"
              :checked-keys="(p.value as (number | string)[] | undefined) ?? []"
              :disabled="isViewOnly"
              :selectable="false"
              :tree-data="treeData"
              block-node
              checkable
              default-expand-all
              :show-icon="false"
              :show-line="false"
              @update:checked-keys="
                (keys) => (p as Record<string, any>)['onUpdate:value']?.(keys)
              "
            />
            <div v-else class="text-muted-foreground text-sm">暂无菜单数据</div>
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
</style>
