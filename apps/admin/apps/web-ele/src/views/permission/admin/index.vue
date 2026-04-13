<script lang="ts" setup>
import type { OnActionClickParams, VxeTableGridOptions } from '#/adapter/vxe-table';
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
  appendToMain: true,
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
}

const [Grid, gridApi] = useVbenVxeGrid({
  formOptions: {
    schema: useGridFormSchema(),
    submitOnChange: false,
    submitButtonOptions: {
      content: '查询',
    },
  },
  gridOptions: {
    columns: useColumns(onActionClick),
    height: 'auto',
    keepSource: true,
    proxyConfig: {
      ajax: {
        query: async ({ page }, formValues) => {
          return await fetchAdminPage({
            page: page.currentPage,
            per_page: page.pageSize,
            username: formValues?.username || undefined,
          });
        },
      },
    },
    rowConfig: {
      keyField: 'id',
    },
    toolbarConfig: {
      custom: true,
      refresh: true,
      search: true,
      zoom: true,
    },
  } as VxeTableGridOptions<AdminRow>,
});

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
      <template #toolbar-tools>
        <ElButton type="primary" @click="openCreate">新增</ElButton>
      </template>
    </Grid>
  </Page>
</template>
