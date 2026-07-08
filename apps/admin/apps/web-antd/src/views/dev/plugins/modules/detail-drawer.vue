<script lang="ts" setup>
import type { PluginAccessChecker } from '../data';

import type { PluginDetail, PluginRow } from '#/api/dev-plugin';

import { computed, reactive, ref } from 'vue';

import {
  Button,
  Descriptions,
  Drawer,
  Form,
  Input,
  message,
  Space,
  Tabs,
} from 'ant-design-vue';

import {
  disablePlugin,
  enablePlugin,
  fetchPlugin,
  installPlugin,
  testSmsAliyunPlugin,
  updatePluginConfig,
} from '#/api/dev-plugin';
import { PLUGIN_PERMISSIONS, PLUGIN_STATUS } from '#/constants/plugin-permissions';

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

const manifestText = computed(() =>
  detail.value?.manifest
    ? JSON.stringify(detail.value.manifest, null, 2)
    : '',
);

const canInstall = computed(
  () =>
    props.access.can(PLUGIN_PERMISSIONS.INSTALL) &&
    detail.value?.status === PLUGIN_STATUS.DISCOVERED,
);

const canEnable = computed(
  () =>
    props.access.can(PLUGIN_PERMISSIONS.ENABLE) &&
    detail.value !== null &&
    [PLUGIN_STATUS.INSTALLED, PLUGIN_STATUS.DISABLED].includes(detail.value.status),
);

const canDisable = computed(
  () =>
    props.access.can(PLUGIN_PERMISSIONS.DISABLE) &&
    detail.value?.status === PLUGIN_STATUS.ENABLED,
);

const canConfig = computed(() => props.access.can(PLUGIN_PERMISSIONS.CONFIG));

const configFields = computed(() => detail.value?.config_schema ?? []);

const showTestTab = computed(
  () => detail.value?.slug === 'sms-aliyun' && detail.value.enabled,
);

async function loadDetail(slug: string) {
  loading.value = true;
  detail.value = null;
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

async function open(row: PluginRow) {
  open.value = true;
  testMobile.value = '';
  await loadDetail(row.slug);
}

async function onInstall() {
  if (!detail.value) return;
  submitting.value = true;
  try {
    await installPlugin(detail.value.slug);
    message.success('安装成功');
    await loadDetail(detail.value.slug);
    emit('success');
  } finally {
    submitting.value = false;
  }
}

async function onEnable() {
  if (!detail.value) return;
  submitting.value = true;
  try {
    await enablePlugin(detail.value.slug);
    message.success('已启用，请重启后端服务后生效');
    await loadDetail(detail.value.slug);
    emit('success');
  } finally {
    submitting.value = false;
  }
}

async function onDisable() {
  if (!detail.value) return;
  submitting.value = true;
  try {
    await disablePlugin(detail.value.slug);
    message.success('已禁用，请重启后端服务后生效');
    await loadDetail(detail.value.slug);
    emit('success');
  } finally {
    submitting.value = false;
  }
}

async function onSaveConfig() {
  if (!detail.value) return;
  submitting.value = true;
  try {
    const payload: Record<string, string> = {};
    for (const key of configFields.value) {
      const value = configForm[key]?.trim() ?? '';
      payload[key] = value === '' && detail.value.config[key] === '__configured__'
        ? '__configured__'
        : value;
    }
    await updatePluginConfig(detail.value.slug, payload);
    message.success('配置已保存');
    await loadDetail(detail.value.slug);
  } finally {
    submitting.value = false;
  }
}

async function onTestSms() {
  if (!testMobile.value.trim()) {
    message.warning('请输入测试手机号');
    return;
  }
  testing.value = true;
  try {
    const result = await testSmsAliyunPlugin({ mobile: testMobile.value.trim() });
    message.success(result.message || '测试成功');
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

defineExpose({ open });
</script>

<template>
  <Drawer
    v-model:open="open"
    :title="detail ? `插件详情 · ${detail.name}` : '插件详情'"
    width="min(90vw, 760px)"
  >
    <Descriptions v-if="detail" :column="1" bordered size="small">
      <Descriptions.Item label="插件标识">{{ detail.slug }}</Descriptions.Item>
      <Descriptions.Item label="插件名称">{{ detail.name }}</Descriptions.Item>
      <Descriptions.Item label="版本">{{ detail.version }}</Descriptions.Item>
      <Descriptions.Item label="类型">{{ detail.type || '--' }}</Descriptions.Item>
      <Descriptions.Item label="描述">{{ detail.description || '--' }}</Descriptions.Item>
      <Descriptions.Item label="目录">{{ detail.directory }}</Descriptions.Item>
      <Descriptions.Item label="状态">{{ detail.status_label }}</Descriptions.Item>
      <Descriptions.Item label="ServiceProvider">
        <div v-if="detail.providers.length > 0" class="space-y-1">
          <div v-for="item in detail.providers" :key="item">{{ item }}</div>
        </div>
        <span v-else class="text-muted-foreground">--</span>
      </Descriptions.Item>
    </Descriptions>

    <Space v-if="detail" class="mt-4">
      <Button v-if="canInstall" :loading="submitting" type="primary" @click="onInstall">
        安装
      </Button>
      <Button v-if="canEnable" :loading="submitting" type="primary" @click="onEnable">
        启用
      </Button>
      <Button v-if="canDisable" :loading="submitting" danger @click="onDisable">
        禁用
      </Button>
    </Space>

    <Tabs v-if="detail" class="mt-4">
      <Tabs.TabPane key="config" tab="配置">
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
                  ? '已配置，留空则不修改'
                  : `请输入 ${fieldLabel(key)}`
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
            保存配置
          </Button>
        </Form>
        <p v-else class="text-muted-foreground text-sm">该插件暂无可编辑配置项。</p>
      </Tabs.TabPane>

      <Tabs.TabPane v-if="showTestTab" key="test" tab="测试">
        <Form layout="vertical">
          <Form.Item label="测试手机号">
            <Input v-model:value="testMobile" placeholder="请输入手机号" />
          </Form.Item>
          <Button :loading="testing" type="primary" @click="onTestSms">
            发送测试
          </Button>
        </Form>
        <p class="text-muted-foreground mt-2 text-xs">
          第一版仅验证插件路由与配置，不调用真实阿里云 SDK。
        </p>
      </Tabs.TabPane>

      <Tabs.TabPane key="manifest" tab="Manifest">
        <pre class="overflow-auto rounded border bg-muted/30 p-3 text-xs leading-5">{{ manifestText }}</pre>
      </Tabs.TabPane>
    </Tabs>
  </Drawer>
</template>
