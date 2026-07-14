<script lang="ts" setup>
import type { ThemeRow } from '#/api/dev-theme';

import { computed, onMounted, ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { IconifyIcon } from '@vben/icons';

import { Alert, Button, Input, message, Modal, Spin } from 'ant-design-vue';

import {
  disableTheme,
  enableTheme,
  fetchThemes,
  installTheme,
  switchTheme,
  uninstallTheme,
} from '#/api/dev-theme';
import { THEME_PERMISSIONS } from '#/constants/theme-permissions';
import { $t } from '#/locales';

import DetailDrawer from './modules/detail-drawer.vue';
import FlowView from './modules/flow-view.vue';

const detailDrawerRef = ref<InstanceType<typeof DetailDrawer>>();
const loading = ref(false);
const keyword = ref('');
const allRows = ref<ThemeRow[]>([]);

const { hasAccessByCodes } = useAccess();

const themeAccess = {
  can: (code: string) =>
    hasAccessByCodes([THEME_PERMISSIONS.MANAGE, code]),
};

const filteredRows = computed(() => {
  const key = keyword.value.trim().toLowerCase();
  if (!key) {
    return allRows.value;
  }

  return allRows.value.filter((row) => {
    const haystack = [
      row.slug,
      row.title,
      row.name,
      row.description ?? '',
      row.type,
    ]
      .join(' ')
      .toLowerCase();
    return haystack.includes(key);
  });
});

async function reload() {
  loading.value = true;
  try {
    allRows.value = await fetchThemes();
  } finally {
    loading.value = false;
  }
}

async function runAction(
  action: () => Promise<unknown>,
  successMessage: string,
) {
  await action();
  message.success(successMessage);
  await reload();
}

function confirmAction(
  title: string,
  content: string,
  onOk: () => Promise<void>,
) {
  Modal.confirm({
    title,
    content,
    okText: $t('common.confirm'),
    cancelText: $t('common.cancel'),
    onOk,
  });
}

function onAction(code: string, row: ThemeRow) {
  if (code === 'detail') {
    detailDrawerRef.value?.open(row);
    return;
  }

  if (code === 'install') {
    void runAction(
      () => installTheme(row.slug),
      $t('dev.shared.installSuccess'),
    );
    return;
  }

  if (code === 'enable') {
    void runAction(
      () => enableTheme(row.slug),
      $t('dev.themes.enabledSuccess'),
    );
    return;
  }

  if (code === 'switch') {
    confirmAction(
      $t('dev.themes.switchTitle'),
      $t('dev.themes.switchConfirm', [row.title]),
      async () => {
        await runAction(
          () => switchTheme(row.slug, row.type),
          $t('dev.themes.switchedSuccess'),
        );
      },
    );
    return;
  }

  if (code === 'disable') {
    confirmAction(
      $t('dev.themes.disableTitle'),
      $t('dev.themes.disableConfirm', [row.title]),
      async () => {
        await runAction(
          () => disableTheme(row.slug),
          $t('dev.themes.disabledSuccess'),
        );
      },
    );
    return;
  }

  if (code === 'uninstall') {
    confirmAction(
      $t('dev.themes.uninstallTitle'),
      $t('dev.themes.uninstallConfirm', [row.title]),
      async () => {
        await runAction(
          () => uninstallTheme(row.slug),
          $t('dev.shared.uninstallSuccess'),
        );
      },
    );
  }
}

onMounted(() => {
  void reload();
});
</script>

<template>
  <Page auto-content-height>
    <div class="flex h-full min-h-0 flex-col">
      <Alert
        class="mb-4 shrink-0"
        :message="$t('dev.themes.hint')"
        show-icon
        type="info"
      />

      <DetailDrawer ref="detailDrawerRef" />

      <div
        class="bg-card flex min-h-0 flex-1 flex-col overflow-hidden rounded-lg border border-border"
      >
        <div
          class="flex shrink-0 items-center justify-between gap-3 border-b border-border px-4 py-3"
        >
          <div class="theme-toolbar-search">
            <Input
              v-model:value="keyword"
              allow-clear
              class="theme-toolbar-search__input"
              :placeholder="$t('dev.themes.searchPlaceholder')"
            >
              <template #prefix>
                <IconifyIcon
                  class="theme-toolbar-search__prefix-icon"
                  icon="lucide:search"
                />
              </template>
            </Input>
            <span class="theme-toolbar-search__total">
              {{ $t('dev.themes.totalCount', [allRows.length]) }}
            </span>
          </div>
          <Button :loading="loading" @click="reload">
            <IconifyIcon class="size-4" icon="lucide:refresh-cw" />
            {{ $t('common.refresh') }}
          </Button>
        </div>

        <div class="relative min-h-0 flex-1 p-4">
          <div
            v-if="loading && allRows.length === 0"
            class="absolute inset-0 z-10 flex items-center justify-center bg-card/60"
          >
            <Spin />
          </div>
          <FlowView
            :access="themeAccess"
            :loading="loading && allRows.length === 0"
            :rows="filteredRows"
            @action="onAction"
          />
        </div>
      </div>
    </div>
  </Page>
</template>

<style scoped>
.theme-toolbar-search {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
  flex: 1;
}

.theme-toolbar-search__input {
  width: min(280px, 46vw);
}

.theme-toolbar-search__input.ant-input-affix-wrapper {
  height: 32px;
  border-radius: var(--radius);
}

.theme-toolbar-search__prefix-icon {
  width: 0.875rem;
  height: 0.875rem;
  color: hsl(var(--muted-foreground));
}

.theme-toolbar-search__total {
  flex-shrink: 0;
  color: hsl(var(--muted-foreground));
  font-size: 0.875rem;
  line-height: 1.5;
  white-space: nowrap;
}
</style>
