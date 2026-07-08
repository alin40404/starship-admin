<script lang="ts" setup>
import type { LoginLogRow } from '#/api/monitor';

import { computed, ref } from 'vue';

import { Descriptions, Drawer, Tag } from 'ant-design-vue';

import { fetchLoginLog } from '#/api/monitor';
import {
  MONITOR_LOGIN_ACTION_TAG_OPTIONS,
  MONITOR_LOGIN_STATUS_TAG_OPTIONS,
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
const detail = ref<LoginLogRow | null>(null);

const actionTag = computed(() =>
  detail.value
    ? resolveTagOption(MONITOR_LOGIN_ACTION_TAG_OPTIONS, detail.value.action)
    : undefined,
);

const statusTag = computed(() =>
  detail.value
    ? resolveTagOption(MONITOR_LOGIN_STATUS_TAG_OPTIONS, detail.value.status)
    : undefined,
);

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
      <Descriptions.Item label="账号">{{ detail.username }}</Descriptions.Item>
      <Descriptions.Item label="动作">
        <Tag v-if="actionTag" :color="actionTag.color">{{ actionTag.label }}</Tag>
        <span v-else>—</span>
      </Descriptions.Item>
      <Descriptions.Item label="结果">
        <Tag v-if="statusTag" :color="statusTag.color">{{ statusTag.label }}</Tag>
        <span v-else>—</span>
      </Descriptions.Item>
      <Descriptions.Item label="说明">{{ detail.message || '—' }}</Descriptions.Item>
      <Descriptions.Item label="IP">{{ detail.ip || '—' }}</Descriptions.Item>
      <Descriptions.Item label="User-Agent">{{ detail.user_agent || '—' }}</Descriptions.Item>
      <Descriptions.Item label="时间">
        {{ detail.created_at ? formatDateTime(detail.created_at) : '—' }}
      </Descriptions.Item>
    </Descriptions>
  </Drawer>
</template>
