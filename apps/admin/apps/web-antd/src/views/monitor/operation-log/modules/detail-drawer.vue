<script lang="ts" setup>
import type { OperationLogRow } from '#/api/monitor';

import { computed, ref } from 'vue';

import { Descriptions, Drawer, Tag } from 'ant-design-vue';

import { fetchOperationLog } from '#/api/monitor';
import {
  getOperationLogModuleColor,
  getOperationLogStatusCodeColor,
  MONITOR_HTTP_METHOD_TAG_OPTIONS,
} from '#/constants/monitor-permissions';
import { formatDateTime } from '@vben/utils';

const descriptionsLabelStyle = {
  minWidth: '148px',
  width: '148px',
  whiteSpace: 'nowrap' as const,
  fontSize: 'var(--text-sm, 0.875rem)',
};

const descriptionsContentStyle = {
  fontSize: 'var(--text-sm, 0.875rem)',
};

function resolveTagOption<T extends { color: string; label: string; value: unknown }>(
  options: readonly T[],
  value: unknown,
) {
  return options.find((item) => item.value === value);
}

const open = ref(false);
const loading = ref(false);
const detail = ref<OperationLogRow | null>(null);

const moduleColor = computed(() =>
  detail.value ? getOperationLogModuleColor(detail.value.module) : 'default',
);

const methodTag = computed(() =>
  detail.value
    ? resolveTagOption(MONITOR_HTTP_METHOD_TAG_OPTIONS, detail.value.method)
    : undefined,
);

const statusCodeColor = computed(() =>
  detail.value
    ? getOperationLogStatusCodeColor(detail.value.status_code)
    : 'default',
);

const contentText = computed(() => {
  if (!detail.value?.content) {
    return '';
  }
  if (typeof detail.value.content === 'string') {
    return detail.value.content;
  }
  return JSON.stringify(detail.value.content, null, 2);
});

async function show(id: number) {
  open.value = true;
  loading.value = true;
  detail.value = null;
  try {
    detail.value = await fetchOperationLog(id);
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
    title="操作日志详情"
    width="min(92vw, 880px)"
  >
    <Descriptions
      v-if="detail"
      :column="1"
      bordered
      :content-style="descriptionsContentStyle"
      :label-style="descriptionsLabelStyle"
      size="small"
    >
      <Descriptions.Item label="操作标题">{{ detail.title }}</Descriptions.Item>
      <Descriptions.Item label="操作人">{{ detail.admin_name || '—' }}</Descriptions.Item>
      <Descriptions.Item label="模块">
        <Tag v-if="detail.module" :color="moduleColor">{{ detail.module }}</Tag>
        <span v-else>—</span>
      </Descriptions.Item>
      <Descriptions.Item label="动作">{{ detail.action }}</Descriptions.Item>
      <Descriptions.Item label="HTTP 方法">
        <Tag v-if="methodTag" :color="methodTag.color">{{ methodTag.label }}</Tag>
        <Tag v-else-if="detail.method" color="default">{{ detail.method }}</Tag>
        <span v-else>—</span>
      </Descriptions.Item>
      <Descriptions.Item label="状态码">
        <Tag v-if="detail.status_code != null" :color="statusCodeColor">
          {{ detail.status_code }}
        </Tag>
        <span v-else>—</span>
      </Descriptions.Item>
      <Descriptions.Item label="请求 URL">{{ detail.url || '—' }}</Descriptions.Item>
      <Descriptions.Item label="IP">{{ detail.ip || '—' }}</Descriptions.Item>
      <Descriptions.Item label="User-Agent">{{ detail.user_agent || '—' }}</Descriptions.Item>
      <Descriptions.Item label="操作时间">
        {{ detail.created_at ? formatDateTime(detail.created_at) : '—' }}
      </Descriptions.Item>
      <Descriptions.Item label="请求摘要">
        <pre
          class="max-h-80 overflow-auto whitespace-pre-wrap break-all text-sm leading-6"
        >{{ contentText || '—' }}</pre>
      </Descriptions.Item>
    </Descriptions>
  </Drawer>
</template>
