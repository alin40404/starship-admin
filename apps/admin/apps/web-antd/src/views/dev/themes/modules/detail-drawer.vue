<script lang="ts" setup>
import type { ThemeDetail, ThemeRow } from '#/api/dev-theme';

import { computed, ref } from 'vue';

import { formatDateTime } from '@vben/utils';

import { Descriptions, Drawer } from 'ant-design-vue';

import { fetchTheme } from '#/api/dev-theme';
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
const detail = ref<ThemeDetail | null>(null);

const drawerTitle = computed(() =>
  detail.value
    ? $t('dev.themes.detailTitle') +
      $t('dev.shared.detailTitleSuffix', [detail.value.title])
    : $t('dev.themes.detailTitle'),
);

const manifestText = computed(() =>
  detail.value?.manifest
    ? JSON.stringify(detail.value.manifest, null, 2)
    : '',
);

function formatTime(value: string | null) {
  return value ? formatDateTime(value) : '—';
}

function buildThemePreview(row: ThemeRow): ThemeDetail {
  return {
    ...row,
    entry: {},
    manifest: {},
    manifest_path: '',
  };
}

async function loadDetail(slug: string, preview?: ThemeRow) {
  if (!preview) {
    loading.value = true;
    detail.value = null;
  }
  try {
    detail.value = await fetchTheme(slug);
  } finally {
    loading.value = false;
  }
}

async function show(row: ThemeRow) {
  open.value = true;
  detail.value = buildThemePreview(row);
  loading.value = false;
  await loadDetail(row.slug, row);
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
      <Descriptions.Item :label="$t('dev.themes.columnSlug')">
        {{ detail.slug }}
      </Descriptions.Item>
      <Descriptions.Item :label="$t('dev.themes.columnTitle')">
        {{ detail.title }}
      </Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.name')">
        {{ detail.name }}
      </Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.version')">
        {{ detail.version }}
      </Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.type')">
        {{ detail.type || '—' }}
      </Descriptions.Item>
      <Descriptions.Item :label="$t('dev.themes.columnActive')">
        {{ detail.is_active ? $t('dev.themes.activeYes') : $t('dev.themes.activeNo') }}
      </Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.description')">
        {{ detail.description || '—' }}
      </Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.author')">
        {{ detail.author || '—' }}
      </Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.directory')">
        {{ detail.directory }}
      </Descriptions.Item>
      <Descriptions.Item :label="$t('dev.themes.layout')">
        {{ detail.layout || '—' }}
      </Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.status')">
        {{ detail.status_label }}
      </Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.installedAt')">
        {{ formatTime(detail.installed_at) }}
      </Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.enabledAt')">
        {{ formatTime(detail.enabled_at) }}
      </Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.manifestPath')">
        {{ detail.manifest_path || '—' }}
      </Descriptions.Item>
    </Descriptions>

    <div v-if="detail" class="mt-4">
      <p class="mb-2 text-sm font-medium">theme.json</p>
      <pre
        class="overflow-auto rounded border bg-muted/30 p-3 text-sm leading-6"
      >{{ manifestText }}</pre>
    </div>
  </Drawer>
</template>
