<script lang="ts" setup>
import type { ExceptionLogRow } from '#/api/monitor';

import { ref } from 'vue';

import { Alert, Descriptions, Drawer } from 'ant-design-vue';

import { fetchExceptionLog } from '#/api/monitor';
import { $t } from '#/locales';
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
    :title="$t('system.monitor.exceptionLog.detailTitle')"
    width="min(92vw, 880px)"
  >
    <Alert
      v-if="detail?.log_hint"
      class="mb-4"
      :message="detail.log_hint"
      show-icon
      type="info"
    />
    <Descriptions
      v-if="detail"
      :column="1"
      bordered
      :content-style="descriptionsContentStyle"
      :label-style="descriptionsLabelStyle"
      size="small"
    >
      <Descriptions.Item label="Trace ID">{{ detail.trace_id }}</Descriptions.Item>
      <Descriptions.Item :label="$t('system.monitor.exceptionLog.columnExceptionClass')">{{ detail.exception_class }}</Descriptions.Item>
      <Descriptions.Item :label="$t('system.monitor.exceptionLog.columnMessage')">{{ detail.message }}</Descriptions.Item>
      <Descriptions.Item :label="$t('system.monitor.exceptionLog.columnFile')">{{ detail.file || '—' }}</Descriptions.Item>
      <Descriptions.Item :label="$t('system.monitor.exceptionLog.columnLine')">{{ detail.line ?? '—' }}</Descriptions.Item>
      <Descriptions.Item :label="$t('system.monitor.shared.method')">{{ detail.request_method || '—' }}</Descriptions.Item>
      <Descriptions.Item :label="$t('system.monitor.operationLog.requestUrl')">{{ detail.request_url || '—' }}</Descriptions.Item>
      <Descriptions.Item :label="$t('system.monitor.shared.operator')">{{ detail.admin_name || '—' }}</Descriptions.Item>
      <Descriptions.Item label="IP">{{ detail.ip || '—' }}</Descriptions.Item>
      <Descriptions.Item label="User-Agent">{{ detail.user_agent || '—' }}</Descriptions.Item>
      <Descriptions.Item :label="$t('system.monitor.exceptionLog.columnOccurredAt')">
        {{ detail.created_at ? formatDateTime(detail.created_at) : '—' }}
      </Descriptions.Item>
    </Descriptions>
  </Drawer>
</template>
