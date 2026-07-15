<script lang="ts" setup>
import type { PluginAccessChecker } from '../data';

import type {
  PluginConfigField,
  PluginDetail,
  PluginPageMeta,
  PluginRow,
} from '#/features/system/api/dev-plugin';

import { computed, reactive, ref, type Component } from 'vue';

import { usePreferences } from '@vben/preferences';

import {
  Button,
  Drawer,
  Form,
  Input,
  InputNumber,
  message,
  Select,
  Switch,
  Tabs,
  Textarea,
} from 'ant-design-vue';

import {
  fetchPlugin,
  testMailSmtpPlugin,
  testSmsAliyunPlugin,
  updatePluginConfig,
} from '#/features/system/api/dev-plugin';
import { PLUGIN_PERMISSIONS } from '#/features/system/constants/plugin-permissions';
import { $t } from '#/locales';

import { resolvePluginCustomComponent } from '../custom-registry';

const { isMobile } = usePreferences();
const drawerWidth = computed(() => (isMobile.value ? '100%' : 'min(90vw, 640px)'));

const props = defineProps<{
  access: PluginAccessChecker;
}>();

const open = ref(false);
const loading = ref(false);
const submitting = ref(false);
const testing = ref(false);
const detail = ref<PluginDetail | null>(null);
const configForm = reactive<Record<string, string | number | boolean>>({});
const testMobile = ref('');
const testEmail = ref('');
const activeTab = ref('config');

const canConfig = computed(() => props.access.can(PLUGIN_PERMISSIONS.CONFIG));
const configFields = computed<PluginConfigField[]>(
  () => detail.value?.config_schema ?? [],
);

const schemaPages = computed(() =>
  (detail.value?.pages ?? []).filter(
    (p) => (p.ui ?? 'schema') === 'schema' && p.key !== 'test',
  ),
);

const extraPages = computed(() =>
  (detail.value?.pages ?? []).filter((p) => {
    const ui = p.ui ?? 'schema';
    return ui === 'link' || ui === 'component';
  }),
);

const showSmsTestTab = computed(
  () => detail.value?.slug === 'sms-aliyun' && detail.value.enabled,
);
const showMailTestTab = computed(
  () => detail.value?.slug === 'mail-smtp' && detail.value.enabled,
);
const showTestTab = computed(
  () =>
    (detail.value?.pages ?? []).some((p) => p.key === 'test') &&
    (showSmsTestTab.value || showMailTestTab.value),
);

const fieldGroups = computed(() => {
  const groups = new Map<string, PluginConfigField[]>();
  for (const field of configFields.value) {
    const groupKey = field.group || '_default';
    const list = groups.get(groupKey) ?? [];
    list.push(field);
    groups.set(groupKey, list);
  }
  return [...groups.entries()];
});

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

  for (const field of detail.value.config_schema) {
    const value = detail.value.config[field.key];
    if (field.type === 'switch') {
      configForm[field.key] =
        value === true || value === 1 || value === '1' || value === 'true';
      continue;
    }

    if (field.type === 'number') {
      configForm[field.key] =
        value === '__configured__' || value === null || value === undefined
          ? ''
          : Number(value);
      continue;
    }

    configForm[field.key] =
      value === '__configured__' || value === null || value === undefined
        ? ''
        : String(value);
  }
}

async function show(row: PluginRow) {
  open.value = true;
  activeTab.value = 'config';
  testMobile.value = '';
  testEmail.value = '';
  detail.value = buildPluginPreview(row);
  loading.value = false;
  await loadDetail(row.slug, row);
}

async function onSaveConfig() {
  if (!detail.value) return;
  submitting.value = true;
  try {
    const payload: Record<string, string | number | boolean> = {};
    for (const field of configFields.value) {
      let value = configForm[field.key];
      if (field.type === 'switch') {
        payload[field.key] = Boolean(value);
        continue;
      }

      if (typeof value === 'string') {
        value = value.trim();
      }

      payload[field.key] =
        value === '' && detail.value.config[field.key] === '__configured__'
          ? '__configured__'
          : (value as string | number | boolean);
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

async function onTestMail() {
  if (!testEmail.value.trim()) {
    message.warning($t('dev.shared.testEmailPlaceholder'));
    return;
  }
  testing.value = true;
  try {
    const result = await testMailSmtpPlugin({ email: testEmail.value.trim() });
    message.success(result.message || $t('dev.shared.testSuccess'));
  } finally {
    testing.value = false;
  }
}

function isEncryptedField(field: PluginConfigField) {
  return field.encrypted || field.type === 'password';
}

function fieldPlaceholder(field: PluginConfigField) {
  if (
    detail.value &&
    isEncryptedField(field) &&
    detail.value.config[field.key] === '__configured__'
  ) {
    return $t('dev.shared.configuredKeepEmpty');
  }
  return $t('dev.shared.enterField', [field.label]);
}

function resolveCustomComponent(page: PluginPageMeta): Component | null {
  if (!page.component) {
    return null;
  }
  return resolvePluginCustomComponent(page.component);
}

function openLink(page: PluginPageMeta) {
  if (!page.href) {
    message.warning($t('dev.plugins.linkMissing'));
    return;
  }
  window.open(page.href, '_blank', 'noopener,noreferrer');
}

function groupTitle(groupKey: string) {
  if (groupKey === '_default') {
    return '';
  }
  return groupKey;
}

const emit = defineEmits<{ success: [] }>();

defineExpose({ open: show });
</script>

<template>
  <Drawer
    v-model:open="open"
    :title="drawerTitle"
    :width="drawerWidth"
  >
    <Tabs v-if="detail" v-model:active-key="activeTab">
      <Tabs.TabPane
        v-if="schemaPages.length > 0 || configFields.length > 0"
        key="config"
        :tab="$t('dev.plugins.configTab')"
      >
        <Form v-if="configFields.length > 0" layout="vertical">
          <template v-for="[groupKey, fields] in fieldGroups" :key="groupKey">
            <div
              v-if="groupTitle(groupKey)"
              class="text-muted-foreground mb-2 mt-4 text-xs font-medium uppercase tracking-wide first:mt-0"
            >
              {{ groupTitle(groupKey) }}
            </div>
            <Form.Item
              v-for="field in fields"
              :key="field.key"
              :label="field.label"
              :extra="field.help || undefined"
            >
              <Select
                v-if="field.type === 'select'"
                v-model:value="configForm[field.key]"
                :disabled="!canConfig"
                :options="field.options"
                :placeholder="fieldPlaceholder(field)"
                allow-clear
              />
              <Switch
                v-else-if="field.type === 'switch'"
                :checked="Boolean(configForm[field.key])"
                :disabled="!canConfig"
                @update:checked="(v) => (configForm[field.key] = v)"
              />
              <InputNumber
                v-else-if="field.type === 'number'"
                v-model:value="configForm[field.key] as number"
                class="w-full"
                :disabled="!canConfig"
                :placeholder="fieldPlaceholder(field)"
              />
              <Textarea
                v-else-if="field.type === 'textarea'"
                v-model:value="configForm[field.key] as string"
                :disabled="!canConfig"
                :placeholder="fieldPlaceholder(field)"
                :rows="4"
              />
              <Input
                v-else
                v-model:value="configForm[field.key] as string"
                :disabled="!canConfig"
                :placeholder="fieldPlaceholder(field)"
                :type="isEncryptedField(field) ? 'password' : 'text'"
              />
            </Form.Item>
          </template>
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

      <Tabs.TabPane
        v-for="page in extraPages"
        :key="page.key"
        :tab="page.title"
      >
        <template v-if="page.ui === 'link'">
          <p class="text-muted-foreground mb-3 text-sm">
            {{ $t('dev.plugins.linkTabHint') }}
          </p>
          <Button type="primary" @click="openLink(page)">
            {{ $t('dev.plugins.openLink') }}
          </Button>
        </template>
        <component
          :is="resolveCustomComponent(page)"
          v-else-if="page.ui === 'component' && resolveCustomComponent(page)"
          :slug="detail.slug"
          :config="detail.config"
        />
        <p v-else class="text-muted-foreground text-sm">
          {{ $t('dev.plugins.customComponentMissing') }}
        </p>
      </Tabs.TabPane>

      <Tabs.TabPane v-if="showTestTab" key="test" :tab="$t('dev.plugins.testTab')">
        <Form v-if="showSmsTestTab" layout="vertical">
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
        <Form v-else-if="showMailTestTab" layout="vertical">
          <Form.Item :label="$t('dev.shared.testEmail')">
            <Input
              v-model:value="testEmail"
              :placeholder="$t('dev.shared.testEmailPlaceholder')"
            />
          </Form.Item>
          <Button :loading="testing" type="primary" @click="onTestMail">
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
