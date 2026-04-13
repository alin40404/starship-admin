<script lang="ts" setup>
import type { MenuNode } from '#/api/system/menu';
import type { RoleRow, RoleSavePayload } from '#/api/system/role';

import { nextTick, onMounted, reactive, ref } from 'vue';

import { Page } from '@vben/common-ui';

import { ElMessage, ElMessageBox } from 'element-plus';

import {
  createRole,
  deleteRole,
  fetchRoleDetail,
  fetchRolePage,
  syncRoleMenus,
  updateRole,
} from '#/api/system/role';
import { fetchMenuTree } from '#/api/system/menu';

const loading = ref(false);
const tableData = ref<RoleRow[]>([]);
const total = ref(0);
const page = ref(1);
const perPage = ref(15);

const dialogVisible = ref(false);
const dialogTitle = ref('新增角色');
const editingId = ref<number | null>(null);

const form = reactive<RoleSavePayload>({
  name: '',
  code: '',
  data_scope: 1,
  sort: 0,
  status: 1,
  remark: '',
});

const assignVisible = ref(false);
const assignRoleId = ref<number | null>(null);
const assignRoleName = ref('');
const menuTree = ref<MenuNode[]>([]);
const menuTreeRef = ref();
const treeRenderKey = ref(0);

const dataScopeOptions = [
  { value: 1, label: '全部数据' },
  { value: 2, label: '本部门' },
  { value: 3, label: '本部门及下级' },
  { value: 4, label: '仅本人' },
  { value: 5, label: '自定义' },
];

function toElTree(nodes: MenuNode[]): { id: number; label: string; children?: any[] }[] {
  return nodes.map((n) => ({
    id: n.id,
    label: `${n.name} (${typeLabel(n.type)})`,
    children: n.children?.length ? toElTree(n.children) : undefined,
  }));
}

function typeLabel(t: number) {
  if (t === 1) return '目录';
  if (t === 2) return '菜单';
  if (t === 3) return '按钮';
  return String(t);
}

async function loadTable() {
  loading.value = true;
  try {
    const { items, total: t } = await fetchRolePage({
      page: page.value,
      per_page: perPage.value,
    });
    tableData.value = items;
    total.value = t;
  } finally {
    loading.value = false;
  }
}

function openCreate() {
  editingId.value = null;
  dialogTitle.value = '新增角色';
  Object.assign(form, {
    name: '',
    code: '',
    data_scope: 1,
    sort: 0,
    status: 1,
    remark: '',
  });
  dialogVisible.value = true;
}

function openEdit(row: RoleRow) {
  editingId.value = row.id;
  dialogTitle.value = '编辑角色';
  Object.assign(form, {
    name: row.name,
    code: row.code,
    data_scope: row.data_scope,
    sort: row.sort,
    status: row.status,
    remark: row.remark ?? '',
  });
  dialogVisible.value = true;
}

async function submitForm() {
  if (!form.name || !form.code) {
    ElMessage.warning('请填写名称与编码');
    return;
  }
  if (!editingId.value) {
    await createRole(form);
    ElMessage.success('创建成功');
  } else {
    await updateRole(editingId.value, form);
    ElMessage.success('已保存');
  }
  dialogVisible.value = false;
  await loadTable();
}

async function onDelete(row: RoleRow) {
  await ElMessageBox.confirm(`确定删除角色「${row.name}」？`, '提示', {
    type: 'warning',
  });
  await deleteRole(row.id);
  ElMessage.success('已删除');
  await loadTable();
}

async function openAssignMenus(row: RoleRow) {
  assignRoleId.value = row.id;
  assignRoleName.value = row.name;
  menuTree.value = await fetchMenuTree();
  const detail = await fetchRoleDetail(row.id);
  treeRenderKey.value += 1;
  assignVisible.value = true;
  await nextTick();
  menuTreeRef.value?.setCheckedKeys(detail.menu_ids, false);
}

async function submitAssignMenus() {
  if (!assignRoleId.value || !menuTreeRef.value) {
    return;
  }
  const tree = menuTreeRef.value as {
    getCheckedKeys: (leafOnly?: boolean) => number[];
    getHalfCheckedKeys: () => number[];
  };
  const checked = tree.getCheckedKeys(false) as number[];
  const half = tree.getHalfCheckedKeys() as number[];
  const menuIds = [...new Set([...checked, ...half])];
  await syncRoleMenus(assignRoleId.value, menuIds);
  ElMessage.success('菜单权限已更新');
  assignVisible.value = false;
}

function dataScopeText(v: number) {
  return dataScopeOptions.find((o) => o.value === v)?.label ?? String(v);
}

function onSearch() {
  page.value = 1;
  loadTable();
}

function onPageSizeChange() {
  page.value = 1;
  loadTable();
}

onMounted(loadTable);
</script>

<template>
  <Page auto-content-height>
    <div class="flex flex-col gap-4 p-4">
      <el-form :inline="true">
        <el-form-item>
          <el-button type="primary" @click="onSearch">刷新</el-button>
          <el-button @click="openCreate">新增角色</el-button>
        </el-form-item>
      </el-form>

      <el-table v-loading="loading" :data="tableData" border row-key="id" stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="name" label="名称" min-width="140" />
        <el-table-column prop="code" label="编码" min-width="140" />
        <el-table-column label="数据范围" min-width="140">
          <template #default="scope">
            {{ scope?.row ? dataScopeText(scope.row.data_scope) : '' }}
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="80" />
        <el-table-column label="状态" width="90">
          <template #default="scope">
            <el-tag
              v-if="scope?.row"
              :type="scope.row.status === 1 ? 'success' : 'info'"
              size="small"
            >
              {{ scope.row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" fixed="right" width="220">
          <template #default="scope">
            <template v-if="scope?.row">
              <el-button link type="primary" @click="openEdit(scope.row)">编辑</el-button>
              <el-button link type="primary" @click="openAssignMenus(scope.row)">分配菜单</el-button>
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

    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="520px" destroy-on-close>
      <el-form label-width="96px">
        <el-form-item label="名称" required>
          <el-input v-model="form.name" />
        </el-form-item>
        <el-form-item label="编码" required>
          <el-input v-model="form.code" :disabled="!!editingId" />
        </el-form-item>
        <el-form-item label="数据范围">
          <el-select v-model="form.data_scope" style="width: 100%">
            <el-option
              v-for="o in dataScopeOptions"
              :key="o.value"
              :label="o.label"
              :value="o.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort" :min="0" />
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

    <el-dialog
      v-model="assignVisible"
      :title="`分配菜单 — ${assignRoleName}`"
      width="520px"
      destroy-on-close
    >
      <el-scrollbar max-height="420px">
        <el-tree
          :key="treeRenderKey"
          ref="menuTreeRef"
          :data="toElTree(menuTree)"
          show-checkbox
          node-key="id"
          default-expand-all
          highlight-current
        />
      </el-scrollbar>
      <template #footer>
        <el-button @click="assignVisible = false">取消</el-button>
        <el-button type="primary" @click="submitAssignMenus">保存</el-button>
      </template>
    </el-dialog>
  </Page>
</template>
