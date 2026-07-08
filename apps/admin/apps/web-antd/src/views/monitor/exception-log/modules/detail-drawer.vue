<script lang="ts" setup>
import type { ExceptionLogRow } from '#/api/monitor';

import { ref } from 'vue';

import { Alert, Descriptions, Drawer } from 'ant-design-vue';

import { fetchExceptionLog } from '#/api/monitor';
import { formatDateTime } from '@vben/utils';

const open = ref(false);
const loading = ref(false);
const detail = ref<ExceptionLogRow | null>(null);

async function show(id: number) {
  open.value = true;
  loading.value = true;
  detail.value = null;
  try {
    detail.value = await fetchExceptionLog(id);
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
    title="异常日志详情"
    width="min(90vw, 760px)"
  >
    <Alert
      v-if="detail?.log_hint"
      class="mb-4"
      :message="detail.log_hint"
      show-icon
      type="info"
    />
    <Descriptions v-if="detail" :column="1" bordered size="small">
      <Descriptions.Item label="Trace ID">{{ detail.trace_id }}</Descriptions.Item>
      <Descriptions.Item label="异常类">{{ detail.exception_class }}</Descriptions.Item>
      <Descriptions.Item label="消息">{{ detail.message }}</Descriptions.Item>
      <Descriptions.Item label="文件">{{ detail.file || '—' }}</Descriptions.Item>
      <Descriptions.Item label="行号">{{ detail.line ?? '—' }}</Descriptions.Item>
      <Descriptions.Item label="HTTP 方法">{{ detail.request_method || '—' }}</Descriptions.Item>
      <Descriptions.Item label="请求 URL">{{ detail.request_url || '—' }}</Descriptions.Item>
      <Descriptions.Item label="操作人">{{ detail.admin_name || '—' }}</Descriptions.Item>
      <Descriptions.Item label="IP">{{ detail.ip || '—' }}</Descriptions.Item>
      <Descriptions.Item label="User-Agent">{{ detail.user_agent || '—' }}</Descriptions.Item>
      <Descriptions.Item label="发生时间">
        {{ detail.created_at ? formatDateTime(detail.created_at) : '—' }}
      </Descriptions.Item>
    </Descriptions>
  </Drawer>
</template>
