<script lang="ts" setup>
import type { LoginLogRow } from '#/api/monitor';

import { ref } from 'vue';

import { Descriptions, Drawer } from 'ant-design-vue';

import { fetchLoginLog } from '#/api/monitor';
import { formatDateTime } from '@vben/utils';

const open = ref(false);
const loading = ref(false);
const detail = ref<LoginLogRow | null>(null);

async function show(id: number) {
  open.value = true;
  loading.value = true;
  detail.value = null;
  try {
    detail.value = await fetchLoginLog(id);
  } finally {
    loading.value = false;
  }
}

defineExpose({ open: show });
</script>

<template>
  <Drawer
    v-model:open="open"
    :loading="loading"
    title="登录日志详情"
    width="min(90vw, 640px)"
  >
    <Descriptions v-if="detail" :column="1" bordered size="small">
      <Descriptions.Item label="账号">{{ detail.username }}</Descriptions.Item>
      <Descriptions.Item label="动作">{{ detail.action === 'logout' ? '退出' : '登录' }}</Descriptions.Item>
      <Descriptions.Item label="结果">{{ detail.status === 1 ? '成功' : '失败' }}</Descriptions.Item>
      <Descriptions.Item label="说明">{{ detail.message || '—' }}</Descriptions.Item>
      <Descriptions.Item label="IP">{{ detail.ip || '—' }}</Descriptions.Item>
      <Descriptions.Item label="User-Agent">{{ detail.user_agent || '—' }}</Descriptions.Item>
      <Descriptions.Item label="时间">
        {{ detail.created_at ? formatDateTime(detail.created_at) : '—' }}
      </Descriptions.Item>
    </Descriptions>
  </Drawer>
</template>
