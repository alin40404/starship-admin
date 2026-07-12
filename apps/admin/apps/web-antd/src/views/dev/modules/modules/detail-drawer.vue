<script lang="ts" setup>
import type { DevModuleDetail, DevModuleRow } from '#/api/dev-module';

import { computed, ref } from 'vue';

import { Descriptions, Drawer, message, Switch } from 'ant-design-vue';

import { fetchDevModule, updateDevModuleStatus } from '#/api/dev-module';
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

const props = withDefaults(
  defineProps<{
    canUpdate?: boolean;
  }>(),
  { canUpdate: false },
);

const open = ref(false);
const submitting = ref(false);
const detail = ref<DevModuleDetail | null>(null);

const drawerTitle = computed(() =>
  detail.value
    ? $t('dev.modules.detailTitle') +
      $t('dev.shared.detailTitleSuffix', [detail.value.title])
    : $t('dev.modules.detailTitle'),
);

const manifestText = computed(() =>
  detail.value?.manifest
    ? JSON.stringify(detail.value.manifest, null, 2)
    : '',
);

const canToggle = computed(
  () => props.canUpdate && detail.value?.name.toLowerCase() !== 'system',
);

async function show(row: DevModuleRow) {
  open.value = true;
  detail.value = {
    ...row,
    manifest: {},
    manifest_path: '',
  };
  try {
    detail.value = await fetchDevModule(row.name);
  } catch {
    // 保留列表行预览数据
  }
}

async function onEnabledChange(checked: boolean | string | number) {
  if (!detail.value) {
    return;
  }
  submitting.value = true;
  try {
    const updated = await updateDevModuleStatus(
      detail.value.name,
      checked === true,
    );
    detail.value = {
      ...detail.value,
      ...updated,
      enabled: updated.enabled,
      manifest: {
        ...detail.value.manifest,
        enabled: updated.enabled,
      },
    };
    message.success($t('dev.shared.updatedRestart'));
    emit('success');
  } finally {
    submitting.value = false;
  }
}

const emit = defineEmits<{ success: [] }>();

defineExpose({ open: show });
</script>

<template>
  <Drawer
    v-model:open="open"
    :title="drawerTitle"
    width="min(90vw, 720px)"
  >
    <Descriptions
      v-if="detail"
      :column="1"
      bordered
      :content-style="descriptionsContentStyle"
      :label-style="descriptionsLabelStyle"
      size="small"
    >
      <Descriptions.Item :label="$t('dev.modules.columnName')">{{ detail.name }}</Descriptions.Item>
      <Descriptions.Item :label="$t('dev.modules.columnTitle')">{{ detail.title }}</Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.version')">{{ detail.version || '--' }}</Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.directory')">{{ detail.directory }}</Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.manifestPath')">{{ detail.manifest_path }}</Descriptions.Item>
      <Descriptions.Item label="ServiceProvider">
        <div v-if="detail.providers.length > 0" class="space-y-1">
          <div v-for="item in detail.providers" :key="item">{{ item }}</div>
        </div>
        <span v-else class="text-muted-foreground">--</span>
      </Descriptions.Item>
      <Descriptions.Item label="Seeder">
        <div v-if="detail.seeders.length > 0" class="space-y-1">
          <div v-for="item in detail.seeders" :key="item">{{ item }}</div>
        </div>
        <span v-else class="text-muted-foreground">--</span>
      </Descriptions.Item>
      <Descriptions.Item :label="$t('dev.shared.enableStatus')">
        <Switch
          v-if="canToggle"
          :checked="detail.enabled"
          :loading="submitting"
          :checked-children="$t('dev.shared.enable')"
          :un-checked-children="$t('dev.shared.disable')"
          @change="onEnabledChange"
        />
        <span v-else>{{ $t('dev.shared.alwaysEnabledSystem') }}</span>
      </Descriptions.Item>
    </Descriptions>

    <div v-if="detail" class="mt-4">
      <p class="mb-2 text-sm font-medium">module.json</p>
      <pre
        class="overflow-auto rounded border bg-muted/30 p-3 text-sm leading-6"
      >{{ manifestText }}</pre>
      <p class="text-muted-foreground mt-2 text-sm">
        {{ $t('dev.modules.toggleStatusHint') }}
      </p>
    </div>
  </Drawer>
</template>
