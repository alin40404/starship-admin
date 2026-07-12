<script lang="ts" setup>
import type { PluginAccessChecker } from '../data';

import type { PluginDetail, PluginRow } from '#/api/dev-plugin';

import { computed, reactive, ref } from 'vue';

import { Button, Drawer, Form, Input, message, Tabs } from 'ant-design-vue';

import {
  fetchPlugin,
  testSmsAliyunPlugin,
  updatePluginConfig,
} from '#/api/dev-plugin';
import { PLUGIN_PERMISSIONS } from '#/constants/plugin-permissions';
import { $t } from '#/locales';

const props = defineProps<{
  access: PluginAccessChecker;
}>();

const open = ref(false);
const loading = ref(false);
const submitting = ref(false);
const testing = ref(false);
const detail = ref<PluginDetail | null>(null);
const configForm = reactive<Record<string, string>>({});
const testMobile = ref('');

const canConfig = computed(() => props.access.can(PLUGIN_PERMISSIONS.CONFIG));
const configFields = computed(() => detail.value?.config_schema ?? []);
const showTestTab = computed(
  () => detail.value?.slug === 'sms-aliyun' && detail.value.enabled,
);

const drawerTitle = computed(() =>
  detail.value
    ? $t('dev.plugins.configTitle') +
      $t('dev.shared.detailTitleSuffix', [detail.value.name])
    : $t('dev.plugins.configTitle'),
);

function buildPluginPreview(row: PluginRow): PluginDetail {
  return {
    ...row,
    config: {},
    config_schema: [],
    manifest: {},
    manifest_path: '',
  };
}

async function loadDetail(slug: string, preview?: PluginRow) {
  if (!preview) {
    loading.value = true;
    detail.value = null;
  }
  try {
    detail.value = await fetchPlugin(slug);
    syncConfigForm();
  } finally {
    loading.value = false;
  }
}

function syncConfigForm() {
  Object.keys(configForm).forEach((key) => delete configForm[key]);
  if (!detail.value) {
    return;
  }

  for (const key of detail.value.config_schema) {
    const value = detail.value.config[key];
    configForm[key] =
      value === '__configured__' || value === null || value === undefined
        ? ''
        : String(value);
  }
}

async function show(row: PluginRow) {
  open.value = true;
  testMobile.value = '';
  detail.value = buildPluginPreview(row);
  loading.value = false;
  await loadDetail(row.slug, row);
}

async function onSaveConfig() {
  if (!detail.value) return;
  submitting.value = true;
  try {
    const payload: Record<string, string> = {};
    for (const key of configFields.value) {
      const value = configForm[key]?.trim() ?? '';
      payload[key] =
        value === '' && detail.value.config[key] === '__configured__'
          ? '__configured__'
          : value;
    }
    await updatePluginConfig(detail.value.slug, payload);
    message.success($t('dev.shared.configSaved'));
    await loadDetail(detail.value.slug);
    emit('success');
  } finally {
    submitting.value = false;
  }
}

async function onTestSms() {
  if (!testMobile.value.trim()) {
    message.warning($t('dev.shared.testMobilePlaceholder'));
    return;
  }
  testing.value = true;
  try {
    const result = await testSmsAliyunPlugin({ mobile: testMobile.value.trim() });
    message.success(result.message || $t('dev.shared.testSuccess'));
  } finally {
    testing.value = false;
  }
}

function isSensitiveField(key: string) {
  return /access_key|secret_key|app_secret|token|private_key/i.test(key);
}

function fieldLabel(key: string) {
  return key.replace(/_/g, ' ');
}

const emit = defineEmits<{ success: [] }>();

defineExpose({ open: show });
</script>

<template>
  <Drawer
    v-model:open="open"
    :title="drawerTitle"
    width="min(90vw, 640px)"
  >
    <Tabs v-if="detail">
      <Tabs.TabPane key="config" :tab="$t('dev.plugins.configTab')">
        <Form v-if="configFields.length > 0" layout="vertical">
          <Form.Item
            v-for="key in configFields"
            :key="key"
            :label="fieldLabel(key)"
          >
            <Input
              v-model:value="configForm[key]"
              :disabled="!canConfig"
              :placeholder="
                isSensitiveField(key) && detail.config[key] === '__configured__'
                  ? $t('dev.shared.configuredKeepEmpty')
                  : $t('dev.shared.enterField', [fieldLabel(key)])
              "
              :type="isSensitiveField(key) ? 'password' : 'text'"
            />
          </Form.Item>
          <Button
            v-if="canConfig"
            :loading="submitting"
            type="primary"
            @click="onSaveConfig"
          >
            {{ $t('dev.shared.saveConfig') }}
          </Button>
        </Form>
        <p v-else class="text-muted-foreground text-sm">
          {{ $t('dev.shared.noConfigFields') }}
        </p>
      </Tabs.TabPane>

      <Tabs.TabPane v-if="showTestTab" key="test" :tab="$t('dev.plugins.testTab')">
        <Form layout="vertical">
          <Form.Item :label="$t('dev.shared.testMobile')">
            <Input
              v-model:value="testMobile"
              :placeholder="$t('dev.shared.testMobilePlaceholder')"
            />
          </Form.Item>
          <Button :loading="testing" type="primary" @click="onTestSms">
            {{ $t('dev.shared.sendTest') }}
          </Button>
        </Form>
        <p class="text-muted-foreground mt-2 text-xs">
          {{ $t('dev.plugins.testHint') }}
        </p>
      </Tabs.TabPane>
    </Tabs>
  </Drawer>
</template>
