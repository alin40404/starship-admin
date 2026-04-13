<script lang="ts" setup>
import type { MenuNode, MenuSavePayload } from '#/api/system/menu';

import { computed, onMounted, reactive, ref } from 'vue';

import { Page } from '@vben/common-ui';

import { ElMessage, ElMessageBox } from 'element-plus';

import {
  createMenu,
  deleteMenu,
  fetchMenuTree,
  updateMenu,
} from '#/api/system/menu';

const loading = ref(false);
const treeData = ref<MenuNode[]>([]);

const dialogVisible = ref(false);
const dialogTitle = ref('新增菜单');
const editingId = ref<number | null>(null);

const form = reactive<MenuSavePayload>({
  parent_id: 0,
  type: 2,
  name: '',
  path: '',
  component: '',
  permission: '',
  icon: '',
  sort: 0,
  visible: 1,
  keep_alive: 0,
  status: 1,
  remark: '',
});

const parentOptions = computed(() => flattenMenuOptions(treeData.value));

function flattenMenuOptions(
  nodes: MenuNode[],
  depth = 0,
): { id: number; label: string }[] {
  const out: { id: number; label: string }[] = [];
  const pad = '　'.repeat(depth);
  for (const n of nodes) {
    out.push({ id: n.id, label: `${pad}${n.name}` });
    if (n.children?.length) {
      out.push(...flattenMenuOptions(n.children, depth + 1));
    }
  }
  return out;
}

function typeLabel(t: number) {
  if (t === 1) return '目录';
  if (t === 2) return '菜单';
  if (t === 3) return '按钮';
  return String(t);
}

async function loadTree() {
  loading.value = true;
  try {
    treeData.value = await fetchMenuTree();
  } finally {
    loading.value = false;
  }
}

function openCreate(parentId?: number) {
  editingId.value = null;
  dialogTitle.value = parentId ? '新增子项' : '新增菜单';
  Object.assign(form, {
    parent_id: parentId ?? 0,
    type: 2,
    name: '',
    path: '',
    component: '',
    permission: '',
    icon: '',
    sort: 0,
    visible: 1,
    keep_alive: 0,
    status: 1,
    remark: '',
  });
  dialogVisible.value = true;
}

function openEdit(row: MenuNode) {
  editingId.value = row.id;
  dialogTitle.value = '编辑菜单';
  Object.assign(form, {
    parent_id: row.parent_id,
    type: row.type,
    name: row.name,
    path: row.path ?? '',
    component: row.component ?? '',
    permission: row.permission ?? '',
    icon: row.icon ?? '',
    sort: row.sort,
    visible: row.visible,
    keep_alive: row.keep_alive,
    status: row.status,
    remark: row.remark ?? '',
  });
  dialogVisible.value = true;
}

async function submitForm() {
  if (!form.name) {
    ElMessage.warning('请填写名称');
    return;
  }
  const payload = { ...form };
  if (!editingId.value) {
    await createMenu(payload as MenuSavePayload);
    ElMessage.success('创建成功');
  } else {
    await updateMenu(editingId.value, payload);
    ElMessage.success('已保存');
  }
  dialogVisible.value = false;
  await loadTree();
}

async function onDelete(row: MenuNode) {
  if (row.children?.length) {
    ElMessage.warning('请先删除子节点');
    return;
  }
  await ElMessageBox.confirm(`确定删除「${row.name}」？`, '提示', { type: 'warning' });
  await deleteMenu(row.id);
  ElMessage.success('已删除');
  await loadTree();
}

onMounted(loadTree);
</script>

<template>
  <Page auto-content-height>
    <div class="flex flex-col gap-4 p-4">
      <el-button type="primary" @click="openCreate()">新增根菜单</el-button>

      <el-table
        v-loading="loading"
        :data="treeData"
        border
        row-key="id"
        default-expand-all
        :tree-props="{ children: 'children' }"
      >
        <el-table-column prop="name" label="名称" min-width="200" />
        <el-table-column label="类型" width="90">
          <template #default="scope">
            {{ scope?.row ? typeLabel(scope.row.type) : '' }}
          </template>
        </el-table-column>
        <el-table-column prop="path" label="路由路径" min-width="160" />
        <el-table-column prop="permission" label="权限标识" min-width="160" />
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
        <el-table-column label="操作" fixed="right" width="260">
          <template #default="scope">
            <template v-if="scope?.row">
              <el-button link type="primary" @click="openCreate(scope.row.id)">新增子项</el-button>
              <el-button link type="primary" @click="openEdit(scope.row)">编辑</el-button>
              <el-button link type="danger" @click="onDelete(scope.row)">删除</el-button>
            </template>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="560px" destroy-on-close>
      <el-form label-width="100px">
        <el-form-item label="上级">
          <el-select v-model="form.parent_id" clearable filterable style="width: 100%" placeholder="根节点为 0">
            <el-option :value="0" label="（根）" />
            <el-option
              v-for="o in parentOptions"
              :key="o.id"
              :label="o.label"
              :value="o.id"
              :disabled="o.id === editingId"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="类型" required>
          <el-radio-group v-model="form.type">
            <el-radio :value="1">目录</el-radio>
            <el-radio :value="2">菜单</el-radio>
            <el-radio :value="3">按钮</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="名称" required>
          <el-input v-model="form.name" />
        </el-form-item>
        <el-form-item label="路径">
          <el-input v-model="form.path" placeholder="路由 path" />
        </el-form-item>
        <el-form-item label="组件">
          <el-input v-model="form.component" placeholder="可选" />
        </el-form-item>
        <el-form-item label="权限标识">
          <el-input v-model="form.permission" placeholder="如 system.admins.query" />
        </el-form-item>
        <el-form-item label="图标">
          <el-input v-model="form.icon" placeholder="Element 图标名" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort" :min="0" />
        </el-form-item>
        <el-form-item label="显示">
          <el-radio-group v-model="form.visible">
            <el-radio :value="1">显示</el-radio>
            <el-radio :value="0">隐藏</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="缓存">
          <el-radio-group v-model="form.keep_alive">
            <el-radio :value="1">是</el-radio>
            <el-radio :value="0">否</el-radio>
          </el-radio-group>
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
