<script lang="ts" setup>
import type { InboxMessageRow } from '#/api/inbox';

import { ref } from 'vue';

import { useVbenDrawer } from '@vben/common-ui';
import { formatDateTime } from '@vben/utils';

import { Descriptions, Tag } from 'ant-design-vue';

import { fetchInboxMessage } from '#/api/inbox';
import {
  INBOX_CATEGORY_OPTIONS,
  INBOX_MESSAGE_TYPE_OPTIONS,
  INBOX_PRIORITY_OPTIONS,
  resolveInboxCategory,
} from '#/constants/inbox-permissions';
import { $t } from '#/locales';

const loading = ref(false);
const row = ref<InboxMessageRow | null>(null);

const [Drawer, drawerApi] = useVbenDrawer({
  destroyOnClose: true,
  showConfirmButton: false,
  cancelText: $t('system.shared.close'),
});

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

function senderLabel(message: InboxMessageRow) {
  if (message.sender?.real_name || message.sender?.username) {
    return message.sender.real_name || message.sender.username;
  }
  return $t('inbox.sender.system');
}

async function open(id: number) {
  loading.value = true;
  drawerApi.open();
  try {
    row.value = await fetchInboxMessage(id);
  } finally {
    loading.value = false;
  }
}

defineExpose({ open });
</script>

<template>
  <Drawer :loading="loading" :title="$t('inbox.detail.title')" class="w-[560px]">
    <Descriptions v-if="row" bordered :column="1" size="small">
      <Descriptions.Item :label="$t('inbox.columns.title')">
        {{ row.title }}
      </Descriptions.Item>
      <Descriptions.Item :label="$t('inbox.columns.category')">
        <Tag>{{ categoryLabel(row) }}</Tag>
      </Descriptions.Item>
      <Descriptions.Item :label="$t('inbox.columns.type')">
        <Tag>{{ typeLabel(row.type) }}</Tag>
      </Descriptions.Item>
      <Descriptions.Item :label="$t('inbox.columns.sender')">
        {{ senderLabel(row) }}
      </Descriptions.Item>
      <Descriptions.Item :label="$t('inbox.columns.priority')">
        <Tag>{{ priorityLabel(row.priority) }}</Tag>
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
        {{ row.content || '—' }}
      </Descriptions.Item>
      <Descriptions.Item v-if="row.link" :label="$t('inbox.detail.link')">
        <a :href="row.link" rel="noopener noreferrer" target="_blank">
          {{ $t('inbox.detail.openLink') }}
        </a>
      </Descriptions.Item>
    </Descriptions>
  </Drawer>
</template>
