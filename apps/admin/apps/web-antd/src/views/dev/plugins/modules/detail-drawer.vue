<script lang="ts" setup>
import type { PluginDetail, PluginRow } from '#/api/dev-plugin';

import { computed, ref } from 'vue';

import { formatDateTime } from '@vben/utils';

import { Descriptions, Drawer } from 'ant-design-vue';

import { fetchPlugin } from '#/api/dev-plugin';

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
    :title="detail ? `插件详情 · ${detail.name}` : '插件详情'"
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
      <Descriptions.Item label="插件标识">{{ detail.slug }}</Descriptions.Item>
      <Descriptions.Item label="插件名称">{{ detail.name }}</Descriptions.Item>
      <Descriptions.Item label="版本">{{ detail.version }}</Descriptions.Item>
      <Descriptions.Item label="类型">{{ detail.type || '—' }}</Descriptions.Item>
      <Descriptions.Item label="描述">{{ detail.description || '—' }}</Descriptions.Item>
      <Descriptions.Item label="作者">{{ detail.author || '—' }}</Descriptions.Item>
      <Descriptions.Item label="目录">{{ detail.directory }}</Descriptions.Item>
      <Descriptions.Item label="命名空间">{{ detail.namespace || '—' }}</Descriptions.Item>
      <Descriptions.Item label="状态">{{ detail.status_label }}</Descriptions.Item>
      <Descriptions.Item label="安装时间">
        {{ formatTime(detail.installed_at) }}
      </Descriptions.Item>
      <Descriptions.Item label="启用时间">
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
