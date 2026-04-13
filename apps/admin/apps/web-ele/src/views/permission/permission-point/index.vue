<script lang="ts" setup>
import type { MenuNode } from '#/api/system/menu';

import { computed, onMounted, ref } from 'vue';

import { Page } from '@vben/common-ui';

import { ElMessage } from 'element-plus';

import { fetchMenuPage, fetchMenuTree } from '#/api/system/menu';

const loading = ref(false);
const flatRows = ref<MenuNode[]>([]);
const tree = ref<MenuNode[]>([]);

const idNameMap = computed(() => buildIdNameMap(tree.value));

function buildIdNameMap(nodes: MenuNode[]): Map<number, string> {
  const map = new Map<number, string>();
  function walk(list: MenuNode[]) {
    for (const n of list) {
      map.set(n.id, n.name);
      if (n.children?.length) {
        walk(n.children);
      }
    }
  }
  walk(nodes);
  return map;
}

async function load() {
  loading.value = true;
  try {
    tree.value = await fetchMenuTree();
    const { items } = await fetchMenuPage({ page: 1, per_page: 500, type: 3 });
    flatRows.value = items;
  } finally {
    loading.value = false;
  }
}

function parentLabel(row: MenuNode) {
  if (!row.parent_id) {
    return '—';
  }
  return idNameMap.value.get(row.parent_id) ?? '—';
}

function hintEditPoint() {
  ElMessage.info('请在「菜单管理」中编辑该权限点（类型为按钮的菜单项）');
}

onMounted(load);
</script>

<template>
  <Page auto-content-height>
    <div class="flex flex-col gap-4 p-4">
      <el-alert
        title="权限点对应菜单类型为「按钮」的项，可在菜单管理中统一维护。"
        type="info"
        show-icon
        :closable="false"
      />
      <el-table v-loading="loading" :data="flatRows" border row-key="id" stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="name" label="名称" min-width="140" />
        <el-table-column label="所属菜单" min-width="160">
          <template #default="scope">
            {{ scope?.row ? parentLabel(scope.row) : '' }}
          </template>
        </el-table-column>
        <el-table-column prop="permission" label="权限标识" min-width="180" />
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
        <el-table-column label="操作" fixed="right" width="120">
          <template #default>
            <el-button link type="primary" @click="hintEditPoint">去菜单管理</el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>
  </Page>
</template>
