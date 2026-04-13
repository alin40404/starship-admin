<script lang="ts" setup>
import type { AdminRow, AdminSavePayload } from '#/api/system/admin';
import type { DeptNode } from '#/api/system/department';
import type { PostRow } from '#/api/system/post';
import type { RoleRow } from '#/api/system/role';

import { computed, onMounted, reactive, ref } from 'vue';

import { Page } from '@vben/common-ui';

import { ElMessage, ElMessageBox } from 'element-plus';

import {
  createAdmin,
  deleteAdmin,
  fetchAdminPage,
  updateAdmin,
} from '#/api/system/admin';
import { fetchDepartmentTree } from '#/api/system/department';
import { fetchPostPage } from '#/api/system/post';
import { fetchRolePage } from '#/api/system/role';

const loading = ref(false);
const tableData = ref<AdminRow[]>([]);
const total = ref(0);
const page = ref(1);
const perPage = ref(15);
const usernameQ = ref('');

const dialogVisible = ref(false);
const dialogTitle = ref('新增管理员');
const editingId = ref<number | null>(null);

const form = reactive<AdminSavePayload>({
  username: '',
  password: '',
  nickname: '',
  real_name: '',
  email: '',
  mobile: '',
  dept_id: undefined,
  status: 1,
  is_super: 0,
  remark: '',
  role_ids: [],
  post_ids: [],
});

const deptTree = ref<DeptNode[]>([]);
const deptTreeSelectData = computed(() => mapDeptForTreeSelect(deptTree.value));
const roleOptions = ref<RoleRow[]>([]);
const postOptions = ref<PostRow[]>([]);

function mapDeptForTreeSelect(nodes: DeptNode[]): { label: string; value: number; children?: any[] }[] {
  return nodes.map((n) => ({
    label: n.name,
    value: n.id,
    children: n.children?.length ? mapDeptForTreeSelect(n.children) : undefined,
  }));
}

async function loadTable() {
  loading.value = true;
  try {
    const { items, total: t } = await fetchAdminPage({
        page: page.value,
        per_page: perPage.value,
        username: usernameQ.value || undefined,
      });
    tableData.value = items;
    total.value = t;
  } finally {
    loading.value = false;
  }
}

async function loadMeta() {
  const [dept, roles, posts] = await Promise.all([
    fetchDepartmentTree(),
    fetchRolePage({ page: 1, per_page: 500 }),
    fetchPostPage({ page: 1, per_page: 500 }),
  ]);
  deptTree.value = dept;
  roleOptions.value = roles.items;
  postOptions.value = posts.items;
}

function openCreate() {
  editingId.value = null;
  dialogTitle.value = '新增管理员';
  Object.assign(form, {
    username: '',
    password: '',
    nickname: '',
    real_name: '',
    email: '',
    mobile: '',
    dept_id: undefined,
    status: 1,
    is_super: 0,
    remark: '',
    role_ids: [],
    post_ids: [],
  });
  dialogVisible.value = true;
}

function openEdit(row: AdminRow) {
  editingId.value = row.id;
  dialogTitle.value = '编辑管理员';
  Object.assign(form, {
    username: row.username,
    password: '',
    nickname: row.nickname ?? '',
    real_name: row.real_name ?? '',
    email: row.email ?? '',
    mobile: row.mobile ?? '',
    dept_id: row.dept_id ?? undefined,
    status: row.status,
    is_super: row.is_super,
    remark: row.remark ?? '',
    role_ids: row.roles?.map((r) => r.id) ?? [],
    post_ids: row.posts?.map((p) => p.id) ?? [],
  });
  dialogVisible.value = true;
}

async function submitForm() {
  if (!editingId.value) {
    if (!form.username || !form.password) {
      ElMessage.warning('请填写用户名与密码');
      return;
    }
    await createAdmin(form as AdminSavePayload);
    ElMessage.success('创建成功');
  } else {
    const payload = { ...form };
    if (!payload.password) {
      delete payload.password;
    }
    await updateAdmin(editingId.value, payload);
    ElMessage.success('已保存');
  }
  dialogVisible.value = false;
  await loadTable();
}

async function onDelete(row: AdminRow) {
  await ElMessageBox.confirm(`确定删除管理员「${row.username}」？`, '提示', {
    type: 'warning',
  });
  await deleteAdmin(row.id);
  ElMessage.success('已删除');
  await loadTable();
}

function statusLabel(v: number) {
  return v === 1 ? '启用' : '禁用';
}

function onSearch() {
  page.value = 1;
  loadTable();
}

function onPageSizeChange() {
  page.value = 1;
  loadTable();
}

onMounted(async () => {
  await loadMeta();
  await loadTable();
});
</script>

<template>
  <Page auto-content-height>
    <div class="flex flex-col gap-4 p-4">
      <el-form :inline="true" @submit.prevent>
        <el-form-item label="用户名">
          <el-input v-model="usernameQ" clearable placeholder="模糊搜索" style="width: 200px" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="onSearch">查询</el-button>
          <el-button @click="openCreate">新增</el-button>
        </el-form-item>
      </el-form>

      <el-table v-loading="loading" :data="tableData" border row-key="id" stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="username" label="用户名" min-width="120" />
        <el-table-column prop="nickname" label="昵称" min-width="100" />
        <el-table-column label="部门" min-width="120">
          <template #default="scope">
            {{ scope?.row?.department?.name ?? '—' }}
          </template>
        </el-table-column>
        <el-table-column label="角色" min-width="160">
          <template #default="scope">
            <template v-if="scope?.row">
              <el-tag
                v-for="r in scope.row.roles ?? []"
                :key="r.id"
                class="mr-1"
                size="small"
              >
                {{ r.name }}
              </el-tag>
              <span v-if="!(scope.row.roles?.length)">—</span>
            </template>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="90">
          <template #default="scope">
            <el-tag
              v-if="scope?.row"
              :type="scope.row.status === 1 ? 'success' : 'info'"
              size="small"
            >
              {{ statusLabel(scope.row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" fixed="right" width="160">
          <template #default="scope">
            <template v-if="scope?.row">
              <el-button link type="primary" @click="openEdit(scope.row)">编辑</el-button>
              <el-button link type="danger" @click="onDelete(scope.row)">删除</el-button>
            </template>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="page"
        v-model:page-size="perPage"
        :total="total"
        background
        layout="total, sizes, prev, pager, next"
        @current-change="loadTable"
        @size-change="onPageSizeChange"
      />
    </div>

    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="560px" destroy-on-close>
      <el-form label-width="96px">
        <el-form-item label="用户名" required>
          <el-input v-model="form.username" :disabled="!!editingId" />
        </el-form-item>
        <el-form-item :label="editingId ? '新密码' : '密码'" :required="!editingId">
          <el-input v-model="form.password" type="password" show-password placeholder="编辑时可留空不改" />
        </el-form-item>
        <el-form-item label="昵称">
          <el-input v-model="form.nickname" />
        </el-form-item>
        <el-form-item label="部门">
          <el-tree-select
            v-model="form.dept_id"
            :data="deptTreeSelectData"
            check-strictly
            clearable
            placeholder="选择部门"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="角色">
          <el-select v-model="form.role_ids" multiple clearable placeholder="选择角色" style="width: 100%">
            <el-option v-for="r in roleOptions" :key="r.id" :label="r.name" :value="r.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="岗位">
          <el-select v-model="form.post_ids" multiple clearable placeholder="可选" style="width: 100%">
            <el-option v-for="p in postOptions" :key="p.id" :label="p.name" :value="p.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="form.status">
            <el-radio :value="1">启用</el-radio>
            <el-radio :value="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="form.remark" type="textarea" rows="2" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm">确定</el-button>
      </template>
    </el-dialog>
  </Page>
</template>
