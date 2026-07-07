<script lang="ts" setup>
import type { DevModuleDetail, DevModuleRow } from '#/api/dev-module';

import { computed, ref } from 'vue';

import { Descriptions, Drawer, message, Switch } from 'ant-design-vue';

import { fetchDevModule, updateDevModuleStatus } from '#/api/dev-module';

const open = ref(false);
const loading = ref(false);
const submitting = ref(false);
const detail = ref<DevModuleDetail | null>(null);

const manifestText = computed(() =>
  detail.value?.manifest
    ? JSON.stringify(detail.value.manifest, null, 2)
    : '',
);

const canToggle = computed(
  () => detail.value?.name.toLowerCase() !== 'system',
);

async function show(row: DevModuleRow) {
  open.value = true;
  loading.value = true;
  detail.value = null;
  try {
    detail.value = await fetchDevModule(row.name);
  } finally {
    loading.value = false;
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
    message.success('已更新，请重启后端服务后生效');
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
    :title="detail ? `模块详情 · ${detail.title}` : '模块详情'"
    width="min(90vw, 720px)"
  >
    <Descriptions v-if="detail" :column="1" bordered size="small">
      <Descriptions.Item label="模块标识">{{ detail.name }}</Descriptions.Item>
      <Descriptions.Item label="模块名称">{{ detail.title }}</Descriptions.Item>
      <Descriptions.Item label="版本">{{ detail.version || '--' }}</Descriptions.Item>
      <Descriptions.Item label="目录">{{ detail.directory }}</Descriptions.Item>
      <Descriptions.Item label="manifest 路径">{{ detail.manifest_path }}</Descriptions.Item>
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
      <Descriptions.Item label="启用状态">
        <Switch
          v-if="canToggle"
          :checked="detail.enabled"
          :loading="submitting"
          checked-children="启用"
          un-checked-children="禁用"
          @change="onEnabledChange"
        />
        <span v-else>始终启用（System 模块不可禁用）</span>
      </Descriptions.Item>
    </Descriptions>

    <div v-if="detail" class="mt-4">
      <p class="mb-2 text-sm font-medium">module.json</p>
      <pre class="overflow-auto rounded border bg-muted/30 p-3 text-xs leading-5">{{ manifestText }}</pre>
      <p class="text-muted-foreground mt-2 text-xs">
        修改启用状态会写入 module.json，需重启 PHP 服务后生效。
      </p>
    </div>
  </Drawer>
</template>
