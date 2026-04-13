<script lang="ts" setup>
import type { RoleRow, RoleSavePayload } from '#/api/system/role';

import { onMounted, reactive, ref } from 'vue';

import { Page } from '@vben/common-ui';

import { ElMessage } from 'element-plus';

import { fetchRolePage, updateRole } from '#/api/system/role';

const loading = ref(false);
const tableData = ref<RoleRow[]>([]);
const total = ref(0);
const page = ref(1);
const perPage = ref(15);

const dialogVisible = ref(false);
const editing = ref<RoleRow | null>(null);

const form = reactive<RoleSavePayload>({
  data_scope: 1,
  remark: '',
});

const dataScopeOptions = [
  { value: 1, label: '全部数据' },
  { value: 2, label: '本部门' },
  { value: 3, label: '本部门及下级' },
  { value: 4, label: '仅本人' },
  { value: 5, label: '自定义' },
];

function dataScopeText(v: number) {
  return dataScopeOptions.find((o) => o.value === v)?.label ?? String(v);
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

function openEdit(row: RoleRow) {
  editing.value = row;
  form.data_scope = row.data_scope;
  form.remark = row.remark ?? '';
  dialogVisible.value = true;
}

async function submitForm() {
  if (!editing.value) {
    return;
  }
  await updateRole(editing.value.id, {
    data_scope: form.data_scope,
    remark: form.remark,
  });
  ElMessage.success('数据范围已更新');
  dialogVisible.value = false;
  await loadTable();
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
      <el-alert
        title="数据权限通过角色的「数据范围」控制业务数据的可见范围，与功能菜单权限相互独立。"
        type="info"
        show-icon
        :closable="false"
      />
      <el-form :inline="true">
        <el-form-item>
          <el-button type="primary" @click="onSearch">刷新</el-button>
        </el-form-item>
      </el-form>

      <el-table v-loading="loading" :data="tableData" border row-key="id" stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="name" label="角色名称" min-width="140" />
        <el-table-column prop="code" label="编码" min-width="140" />
        <el-table-column label="数据范围" min-width="160">
          <template #default="scope">
            {{ scope?.row ? dataScopeText(scope.row.data_scope) : '' }}
          </template>
        </el-table-column>
        <el-table-column prop="remark" label="备注" min-width="160" show-overflow-tooltip />
        <el-table-column label="操作" fixed="right" width="120">
          <template #default="scope">
            <el-button
              v-if="scope?.row"
              link
              type="primary"
              @click="openEdit(scope.row)"
            >
              配置范围
            </el-button>
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

    <el-dialog v-model="dialogVisible" title="数据范围" width="480px" destroy-on-close>
      <el-form label-width="96px">
        <el-form-item label="角色">
          <el-input :model-value="editing?.name" disabled />
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
        <el-form-item label="备注">
          <el-input v-model="form.remark" type="textarea" rows="2" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm">保存</el-button>
      </template>
    </el-dialog>
  </Page>
</template>
