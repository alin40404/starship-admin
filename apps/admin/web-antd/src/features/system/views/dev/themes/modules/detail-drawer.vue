<script lang="ts" setup>
import type { ThemeDetail, ThemeRow } from '#/features/system/api/dev-theme';

import { computed, ref } from 'vue';

import { usePreferences } from '@vben/preferences';
import { formatDateTime } from '@vben/utils';

import { Descriptions, Drawer, Tag } from 'ant-design-vue';

import { fetchTheme } from '#/features/system/api/dev-theme';
import { THEME_STATUS } from '#/features/system/constants/theme-permissions';
import { $t } from '#/locales';

import ThemeCover from './theme-cover.vue';

const { isMobile } = usePreferences();
const drawerWidth = computed(() => (isMobile.value ? '100%' : 'min(92vw, 880px)'));

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

function statusColor(status: number) {
  switch (status) {
    case THEME_STATUS.ENABLED: {
      return 'success';
    }
    case THEME_STATUS.INSTALLED: {
      return 'processing';
    }
    case THEME_STATUS.DISABLED: {
      return 'warning';
    }
    default: {
      return 'default';
    }
  }
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
    :width="drawerWidth"
  >
    <div v-if="detail" class="mb-4 overflow-hidden rounded-lg border">
      <ThemeCover
        :cover-url="detail.cover_url"
        :slug="detail.slug"
        :title="detail.title"
      />
    </div>

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
        <Tag :color="detail.is_active ? 'success' : 'default'" class="!m-0">
          {{
            detail.is_active
              ? $t('dev.themes.activeYes')
              : $t('dev.themes.activeNo')
          }}
        </Tag>
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
        <Tag :color="statusColor(detail.status)" class="!m-0">
          {{ detail.status_label }}
        </Tag>
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
