<script lang="ts" setup>
import type { PluginDetail, PluginRow } from '#/api/dev-plugin';

import { computed, ref } from 'vue';

import { formatDateTime } from '@vben/utils';

import { Descriptions, Drawer } from 'ant-design-vue';

import { fetchPlugin } from '#/api/dev-plugin';
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
const detail = ref<PluginDetail | null>(null);

const drawerTitle = computed(() =>
  detail.value
    ? $t('dev.plugins.detailTitle') +
      $t('dev.shared.detailTitleSuffix', [detail.value.name])
    : $t('dev.plugins.detailTitle'),
);

const manifestText = computed(() =>
  detail.value?.manifest
    ? JSON.stringify(detail.value.manifest, null, 2)
    : '',
);

function formatTime(value: string | null) {
  return value ? formatDateTime(value) : '—';
}

async function loadDetail(slug: string) {
  loading.value = true;
  detail.value = null;
  try {
    detail.value = await fetchPlugin(slug);
  } finally {
    loading.value = false;
  }
}

async function show(row: PluginRow) {
  open.value = true;
  await loadDetail(row.slug);
}

defineExpose({ open: show });
</script>

<template>
  <Drawer
    v-model:open="open"
    :loading="loading"
    :title="drawerTitle"
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
      <Descriptions.Item :label="$t('dev.plugins.columnSlug')">{{ detail.slug }}</Descriptions.Item>
      <Descriptions.Item :label="$t('dev.plugins.columnName')">{{ detail.name }}</Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.version')">{{ detail.version }}</Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.type')">{{ detail.type || '—' }}</Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.description')">{{ detail.description || '—' }}</Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.author')">{{ detail.author || '—' }}</Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.directory')">{{ detail.directory }}</Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.namespace')">{{ detail.namespace || '—' }}</Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.status')">{{ detail.status_label }}</Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.installedAt')">
        {{ formatTime(detail.installed_at) }}
      </Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.enabledAt')">
        {{ formatTime(detail.enabled_at) }}
      </Descriptions.Item>
      <Descriptions.Item label="ServiceProvider">
        <div v-if="detail.providers.length > 0" class="space-y-1">
          <div v-for="item in detail.providers" :key="item">{{ item }}</div>
        </div>
        <span v-else class="text-muted-foreground">—</span>
      </Descriptions.Item>
    </Descriptions>

    <div v-if="detail" class="mt-4">
      <p class="mb-2 text-sm font-medium">plugin.json</p>
      <pre
        class="overflow-auto rounded border bg-muted/30 p-3 text-sm leading-6"
      >{{ manifestText }}</pre>
    </div>
  </Drawer>
</template>
