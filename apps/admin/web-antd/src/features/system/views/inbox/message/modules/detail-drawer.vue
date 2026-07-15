<script lang="ts" setup>
import type { InboxMessageRow } from '#/features/system/api/inbox';

import { ref } from 'vue';

import { formatDateTime } from '@vben/utils';

import { Descriptions, Drawer, Tag } from 'ant-design-vue';

import { fetchInboxMessage } from '#/features/system/api/inbox';
import {
  INBOX_CATEGORY_OPTIONS,
  INBOX_MESSAGE_TYPE_OPTIONS,
  INBOX_PRIORITY_OPTIONS,
  resolveInboxCategory,
} from '#/features/system/constants/inbox-permissions';
import { $t } from '#/locales';

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
const row = ref<InboxMessageRow | null>(null);

function typeLabel(type: string) {
  const option = INBOX_MESSAGE_TYPE_OPTIONS.find((item) => item.value === type);
  return option ? $t(option.labelKey) : type;
}

function categoryLabel(message: InboxMessageRow) {
  const category = resolveInboxCategory(message.category, message.type);
  const option = INBOX_CATEGORY_OPTIONS.find((item) => item.value === category);
  return option ? $t(option.labelKey) : category;
}

function priorityLabel(priority: string) {
  const option = INBOX_PRIORITY_OPTIONS.find((item) => item.value === priority);
  return option ? $t(option.labelKey) : priority;
}

function categoryColor(message: InboxMessageRow) {
  const category = resolveInboxCategory(message.category, message.type);
  return (
    INBOX_CATEGORY_OPTIONS.find((item) => item.value === category)?.color ??
    'default'
  );
}

function typeColor(type: string) {
  return (
    INBOX_MESSAGE_TYPE_OPTIONS.find((item) => item.value === type)?.color ??
    'default'
  );
}

function priorityColor(priority: string) {
  return (
    INBOX_PRIORITY_OPTIONS.find((item) => item.value === priority)?.color ??
    'default'
  );
}

function senderLabel(message: InboxMessageRow) {
  if (message.sender?.real_name || message.sender?.username) {
    return message.sender.real_name || message.sender.username;
  }
  return $t('inbox.sender.system');
}

async function show(id: number, preview?: InboxMessageRow) {
  open.value = true;
  row.value = preview?.id === id ? preview : null;
  loading.value = !row.value;

  try {
    row.value = await fetchInboxMessage(id);
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
    :title="$t('inbox.detail.title')"
    :z-index="2100"
    width="min(92vw, 560px)"
  >
    <Descriptions
      v-if="row"
      bordered
      :column="1"
      :content-style="descriptionsContentStyle"
      :label-style="descriptionsLabelStyle"
      size="small"
    >
      <Descriptions.Item :label="$t('inbox.columns.title')">
        {{ row.title }}
      </Descriptions.Item>
      <Descriptions.Item :label="$t('inbox.columns.category')">
        <Tag :color="categoryColor(row)">{{ categoryLabel(row) }}</Tag>
      </Descriptions.Item>
      <Descriptions.Item :label="$t('inbox.columns.type')">
        <Tag :color="typeColor(row.type)">{{ typeLabel(row.type) }}</Tag>
      </Descriptions.Item>
      <Descriptions.Item :label="$t('inbox.columns.sender')">
        {{ senderLabel(row) }}
      </Descriptions.Item>
      <Descriptions.Item :label="$t('inbox.columns.priority')">
        <Tag :color="priorityColor(row.priority)">
          {{ priorityLabel(row.priority) }}
        </Tag>
      </Descriptions.Item>
      <Descriptions.Item :label="$t('inbox.columns.status')">
        {{
          row.is_read === 1
            ? $t('inbox.readStatus.read')
            : $t('inbox.readStatus.unread')
        }}
      </Descriptions.Item>
      <Descriptions.Item :label="$t('inbox.columns.createdAt')">
        {{ formatDateTime(row.created_at) }}
      </Descriptions.Item>
      <Descriptions.Item :label="$t('inbox.detail.content')">
        <span class="whitespace-pre-wrap break-words leading-6">
          {{ row.content || '—' }}
        </span>
      </Descriptions.Item>
      <Descriptions.Item v-if="row.link" :label="$t('inbox.detail.link')">
        <a :href="row.link" rel="noopener noreferrer" target="_blank">
          {{ $t('inbox.detail.openLink') }}
        </a>
      </Descriptions.Item>
    </Descriptions>
  </Drawer>
</template>
