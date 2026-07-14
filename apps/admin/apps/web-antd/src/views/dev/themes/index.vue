<script lang="ts" setup>
import type { ThemeRow } from '#/api/dev-theme';

import { onMounted, ref } from 'vue';

import { useAccess } from '@vben/access';
import { Page } from '@vben/common-ui';
import { IconifyIcon } from '@vben/icons';

import { Alert, Button, message, Modal, Spin } from 'ant-design-vue';

import {
  disableTheme,
  discoverThemes,
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
const discovering = ref(false);
const loading = ref(false);
const rows = ref<ThemeRow[]>([]);

const { hasAccessByCodes } = useAccess();

const themeAccess = {
  can: (code: string) =>
    hasAccessByCodes([THEME_PERMISSIONS.MANAGE, code]),
};

async function reload() {
  loading.value = true;
  try {
    rows.value = await fetchThemes();
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

async function onDiscover() {
  discovering.value = true;
  try {
    const result = await discoverThemes();
    message.success(
      $t('dev.themes.discoverResult', [
        result.discovered,
        result.created,
        result.updated,
      ]),
    );
    await reload();
  } finally {
    discovering.value = false;
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
          <div class="text-sm text-muted-foreground">
            {{ $t('dev.themes.flowCount', [rows.length]) }}
          </div>
          <div class="flex items-center gap-2">
            <Button
              v-if="themeAccess.can(THEME_PERMISSIONS.DISCOVER)"
              :loading="discovering"
              type="primary"
              @click="onDiscover"
            >
              <IconifyIcon class="size-4" icon="ep:search" />
              {{ $t('dev.themes.discoverThemes') }}
            </Button>
            <Button :loading="loading" @click="reload">
              <IconifyIcon class="size-4" icon="lucide:refresh-cw" />
              {{ $t('common.refresh') }}
            </Button>
          </div>
        </div>

        <div class="relative min-h-0 flex-1 p-4">
          <div
            v-if="loading && rows.length === 0"
            class="absolute inset-0 z-10 flex items-center justify-center bg-card/60"
          >
            <Spin />
          </div>
          <FlowView
            :access="themeAccess"
            :loading="loading && rows.length === 0"
            :rows="rows"
            @action="onAction"
          />
        </div>
      </div>
    </div>
  </Page>
</template>
