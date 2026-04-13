<script lang="ts" setup>
import type {
  ExtendedVxeGridApi,
  OnActionClickParams,
  VxeTableGridOptions,
} from '#/adapter/vxe-table';
import type { AdminRow, AdminSavePayload } from '#/api/system/admin';
import type { DeptNode } from '#/api/system/department';
import type { PostRow } from '#/api/system/post';
import type { RoleRow } from '#/api/system/role';

import { computed, nextTick, onMounted, ref } from 'vue';

import { Page, useVbenDrawer } from '@vben/common-ui';

import { ElMessage, ElMessageBox } from 'element-plus';

import { useVbenForm } from '#/adapter/form';
import { useVbenVxeGrid } from '#/adapter/vxe-table';
import {
  batchDeleteAdmins,
  createAdmin,
  deleteAdmin,
  fetchAdminPage,
  updateAdmin,
} from '#/api/system/admin';
import { fetchDepartmentTree } from '#/api/system/department';
import { fetchPostPage } from '#/api/system/post';
import { fetchRolePage } from '#/api/system/role';

import { buildAdminFormSchema, useColumns, useGridFormSchema } from './data';

const editingId = ref<number | null>(null);
const metaReady = ref(false);

const deptTree = ref<DeptNode[]>([]);
const roleOptions = ref<RoleRow[]>([]);
const postOptions = ref<PostRow[]>([]);

const deptTreeSelectData = computed(() =>
  mapDeptForTreeSelect(deptTree.value),
);
const roleSelectOptions = computed(() =>
  roleOptions.value.map((r) => ({ label: r.name, value: r.id })),
);
const postSelectOptions = computed(() =>
  postOptions.value.map((p) => ({ label: p.name, value: p.id })),
);

function mapDeptForTreeSelect(
  nodes: DeptNode[],
): { label: string; value: number; children?: any[] }[] {
  return nodes.map((n) => ({
    label: n.name,
    value: n.id,
    children: n.children?.length ? mapDeptForTreeSelect(n.children) : undefined,
  }));
}

const adminFormSchema = computed(() =>
  buildAdminFormSchema({
    deptTreeData: deptTreeSelectData.value,
    isEdit: editingId.value !== null,
    postOptions: postSelectOptions.value,
    roleOptions: roleSelectOptions.value,
  }),
);

const [Form, formApi] = useVbenForm({
  // useVbenForm 内部会对 schema 做响应式处理，类型声明未包含 ComputedRef
  schema: adminFormSchema as any,
  showDefaultActions: false,
});

const [Drawer, drawerApi] = useVbenDrawer({
  async onConfirm() {
    const { valid } = await formApi.validate();
    if (!valid) {
      return;
    }
    const values = (await formApi.getValues()) as AdminSavePayload;
    if (!editingId.value && !String(values.password ?? '').trim()) {
      ElMessage.warning('请填写密码');
      return;
    }
    drawerApi.lock();
    try {
      const payload = { ...values };
      if (editingId.value && !payload.password) {
        delete payload.password;
      }
      if (!editingId.value) {
        await createAdmin(payload);
        ElMessage.success('创建成功');
      } else {
        await updateAdmin(editingId.value, payload);
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

function onActionClick({ code, row }: OnActionClickParams<AdminRow>) {
  if (code === 'edit') {
    openEdit(row);
  } else if (code === 'delete') {
    onDelete(row);
  }
}

async function onDelete(row: AdminRow) {
  await ElMessageBox.confirm(`确定删除管理员「${row.username}」？`, '提示', {
    type: 'warning',
  });
  await deleteAdmin(row.id);
  ElMessage.success('已删除');
  gridApi.query();
  clearSelection();
}

const selectedRows = ref<AdminRow[]>([]);

let gridApi: ExtendedVxeGridApi<AdminRow>;

function syncSelection() {
  const records = gridApi?.grid?.getCheckboxRecords?.() ?? [];
  selectedRows.value = records as AdminRow[];
}

function clearSelection() {
  gridApi?.grid?.clearCheckboxRow?.();
  selectedRows.value = [];
}

async function onBatchDelete() {
  const ids = selectedRows.value.map((r) => r.id);
  if (ids.length === 0) {
    return;
  }
  await ElMessageBox.confirm(
    `确定删除选中的 ${ids.length} 条管理员？删除后不可恢复。`,
    '批量删除',
    { type: 'warning' },
  );
  await batchDeleteAdmins(ids);
  ElMessage.success('已批量删除');
  clearSelection();
  gridApi.query();
}

const [Grid, gridApiInstance] = useVbenVxeGrid({
  formOptions: {
    schema: useGridFormSchema(),
    submitOnChange: false,
    submitButtonOptions: {
      content: '查询',
    },
  },
  gridOptions: {
    checkboxConfig: {
      checkMethod: ({ row }) => (row as AdminRow).is_super !== 1,
      highlight: true,
    },
    columns: useColumns(onActionClick),
    gridEvents: {
      checkboxAll: syncSelection,
      checkboxChange: syncSelection,
    },
    height: 'auto',
    keepSource: true,
    proxyConfig: {
      ajax: {
        query: async ({ page, sort }, formValues) => {
          const order = sort?.order;
          return await fetchAdminPage({
            page: page.currentPage,
            per_page: page.pageSize,
            sort_field: sort?.field,
            sort_order:
              order === 'asc' || order === 'desc' ? order : undefined,
            username: formValues?.username || undefined,
          });
        },
      },
      sort: true,
    },
    rowConfig: {
      keyField: 'id',
    },
    sortConfig: {
      remote: true,
    },
    toolbarConfig: {
      custom: true,
      refresh: true,
      search: true,
      zoom: true,
    },
  } as VxeTableGridOptions<AdminRow>,
});

gridApi = gridApiInstance;

async function loadMeta() {
  const [dept, roles, posts] = await Promise.all([
    fetchDepartmentTree(),
    fetchRolePage({ page: 1, per_page: 500 }),
    fetchPostPage({ page: 1, per_page: 500 }),
  ]);
  deptTree.value = dept;
  roleOptions.value = roles.items;
  postOptions.value = posts.items;
  metaReady.value = true;
}

async function ensureMetaLoaded() {
  if (!metaReady.value) {
    await loadMeta();
  }
}

function openCreate() {
  void (async () => {
    await ensureMetaLoaded();
    editingId.value = null;
    formApi.resetForm();
    drawerApi.setState({ title: '新增管理员' }).open();
    nextTick(() => {
      formApi.setValues({
        nickname: '',
        password: '',
        post_ids: [],
        real_name: '',
        remark: '',
        role_ids: [],
        status: 1,
        username: '',
      });
    });
  })();
}

function openEdit(row: AdminRow) {
  void (async () => {
    await ensureMetaLoaded();
    editingId.value = row.id;
    formApi.resetForm();
    drawerApi.setState({ title: '编辑管理员' }).open();
    nextTick(() => {
      formApi.setValues({
        dept_id: row.dept_id ?? undefined,
        email: row.email ?? '',
        mobile: row.mobile ?? '',
        nickname: row.nickname ?? '',
        password: '',
        post_ids: row.posts?.map((p) => p.id) ?? [],
        real_name: row.real_name ?? '',
        remark: row.remark ?? '',
        role_ids: row.roles?.map((r) => r.id) ?? [],
        status: row.status,
        username: row.username,
      });
    });
  })();
}

onMounted(loadMeta);
</script>

<template>
  <Page auto-content-height>
    <Drawer class="w-[560px]">
      <Form />
    </Drawer>
    <Grid>
      <template #toolbar-actions>
        <template v-if="selectedRows.length > 0">
          <ElButton type="danger" @click="onBatchDelete">
            批量删除（{{ selectedRows.length }}）
          </ElButton>
          <ElButton @click="clearSelection">取消选择</ElButton>
        </template>
        <ElButton v-else type="primary" @click="openCreate">新增</ElButton>
      </template>
    </Grid>
  </Page>
</template>
