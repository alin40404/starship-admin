<script lang="ts" setup>
import type { ExceptionLogRow } from '#/api/monitor';

import { computed, ref } from 'vue';

import { Alert, Descriptions, Drawer, Tag } from 'ant-design-vue';

import { fetchExceptionLog } from '#/api/monitor';
import {
  formatExceptionClassLabel,
  getExceptionTypeColor,
} from '#/constants/monitor-permissions';
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

const exceptionTypeTag = computed(() => {
  const exceptionClass = detail.value?.exception_class;
  if (!exceptionClass) {
    return null;
  }

  return {
    color: getExceptionTypeColor(exceptionClass),
    label: formatExceptionClassLabel(exceptionClass),
  };
});

async function show(id: number, preview?: ExceptionLogRow) {
  open.value = true;
  detail.value = preview?.id === id ? preview : null;
  loading.value = !detail.value;
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
    :title="$t('monitor.exceptionLog.detailTitle')"
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
      <Descriptions.Item :label="$t('monitor.exceptionLog.columnExceptionClass')">
        <Tag v-if="exceptionTypeTag" :color="exceptionTypeTag.color">
          {{ exceptionTypeTag.label }}
        </Tag>
        <span v-else>—</span>
      </Descriptions.Item>
      <Descriptions.Item :label="$t('monitor.exceptionLog.columnMessage')">{{ detail.message }}</Descriptions.Item>
      <Descriptions.Item :label="$t('monitor.exceptionLog.columnFile')">{{ detail.file || '—' }}</Descriptions.Item>
      <Descriptions.Item :label="$t('monitor.exceptionLog.columnLine')">{{ detail.line ?? '—' }}</Descriptions.Item>
      <Descriptions.Item :label="$t('monitor.shared.method')">{{ detail.request_method || '—' }}</Descriptions.Item>
      <Descriptions.Item :label="$t('monitor.operationLog.requestUrl')">{{ detail.request_url || '—' }}</Descriptions.Item>
      <Descriptions.Item :label="$t('monitor.shared.operator')">{{ detail.admin_name || '—' }}</Descriptions.Item>
      <Descriptions.Item label="IP">{{ detail.ip || '—' }}</Descriptions.Item>
      <Descriptions.Item label="User-Agent">{{ detail.user_agent || '—' }}</Descriptions.Item>
      <Descriptions.Item :label="$t('monitor.exceptionLog.columnOccurredAt')">
        {{ detail.created_at ? formatDateTime(detail.created_at) : '—' }}
      </Descriptions.Item>
    </Descriptions>
  </Drawer>
</template>
