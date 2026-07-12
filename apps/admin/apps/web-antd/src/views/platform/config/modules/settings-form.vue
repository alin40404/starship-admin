<script lang="ts" setup>
import type { ConfigRow } from '#/api/system/config';

import { computed, onMounted, ref, useTemplateRef, watch } from 'vue';

import { useElementSize } from '@vueuse/core';
import { $t } from '@vben/locales';
import {
  Form,
  Input,
  InputNumber,
  message,
  Select,
  Spin,
  Switch,
  Button,
} from 'ant-design-vue';
import type { DefaultOptionType } from 'ant-design-vue/es/select';

import { batchUpsertConfigs, fetchConfigs, testSmsConfig } from '#/api/system/config';
import { fetchDictOptions } from '#/api/system/dict';
import { AttachmentField } from '#/components/attachment-field';
import { useUserStore } from '@vben/stores';
import { preferences } from '@vben/preferences';

import {
  configFieldLabel,
  configFieldRemark,
  getConfigSelectOptions,
  isAttachmentImageField,
  isHomePathSelectField,
  configAttachmentPlaceholder,
  isStorageFieldVisible,
  mapMenuTreeForHomePathCascader,
  mergeLegacyHomePathCascaderOption,
  mergeSiteTimezoneSelectOptions,
  SITE_FIXED_PAGE_KEY,
  SITE_TIMEZONE_KEY,
  STORAGE_DRIVER_KEY,
  type HomePathCascaderOption,
} from '../data';
import { mapDictItemOptions, DICT_TYPE_CODE } from '#/views/platform/dict/i18n';
import { applySiteBranding } from '#/utils/site-branding';
import { initAdminRuntimeConfig } from '#/utils/admin-runtime-config';
import { fetchMenuOptionsTree } from '#/api/system/menu';
import HomePathCascader from './home-path-cascader.vue';

const props = defineProps<{
  canManage: boolean;
  groupName: string;
}>();

const scrollWrapRef = useTemplateRef<HTMLElement>('scrollWrapRef');
const { height: scrollWrapHeight } = useElementSize(scrollWrapRef);

const loading = ref(false);
const saving = ref(false);
const rows = ref<ConfigRow[]>([]);
const draftValues = ref<Record<string, string>>({});
const homePathCascaderOptions = ref<HomePathCascaderOption[]>([]);
const siteTimezoneOptions = ref<{ label: string; value: string }[]>([]);
const smsTestMobile = ref('');
const smsTesting = ref(false);

const scrollStyle = computed(() => ({
  height: `${Math.max(Math.floor(scrollWrapHeight.value), 280)}px`,
  overflowY: 'auto' as const,
}));

const fieldControlClass = 'config-field-control';

const sortedRows = computed(() =>
  [...rows.value].sort((a, b) => a.sort - b.sort || a.id - b.id),
);

const visibleRows = computed(() => {
  if (props.groupName !== 'storage') {
    return sortedRows.value;
  }

  const driver = draftValues.value[STORAGE_DRIVER_KEY] || 'public';

  return sortedRows.value.filter((row) =>
    isStorageFieldVisible(row.key, driver),
  );
});

function syncDraftFromRows(list: ConfigRow[]) {
  const next: Record<string, string> = {};
  for (const row of list) {
    next[row.key] = row.value ?? '';
  }
  draftValues.value = next;
}

async function loadSiteTimezoneOptions() {
  const items = await fetchDictOptions(DICT_TYPE_CODE.siteTimezone);
  siteTimezoneOptions.value = mapDictItemOptions(
    DICT_TYPE_CODE.siteTimezone,
    items.map((item) => ({
      label: item.label,
      value: item.value,
    })),
  );
}

async function loadConfigs() {
  loading.value = true;
  try {
    if (props.groupName === 'basic') {
      const [result, menuTree] = await Promise.all([
        fetchConfigs(props.groupName),
        fetchMenuOptionsTree(),
        loadSiteTimezoneOptions(),
      ]);
      rows.value = result.list ?? [];
      syncDraftFromRows(rows.value);
      refreshHomePathCascader(menuTree);
      return;
    }

    const result = await fetchConfigs(props.groupName);
    rows.value = result.list ?? [];
    syncDraftFromRows(rows.value);
  } finally {
    loading.value = false;
  }
}

function refreshHomePathCascader(menuTree: Awaited<ReturnType<typeof fetchMenuOptionsTree>>) {
  const options = mapMenuTreeForHomePathCascader(menuTree);
  homePathCascaderOptions.value = mergeLegacyHomePathCascaderOption(
    options,
    draftValues.value[SITE_FIXED_PAGE_KEY] ?? '',
  );
}

async function onSave() {
  const items = sortedRows.value.map((row) => ({
    group_name: row.group_name,
    key: row.key,
    name: row.name,
    remark: row.remark,
    sort: row.sort,
    type: row.type,
    value: draftValues.value[row.key] ?? '',
  }));
  saving.value = true;
  try {
    await batchUpsertConfigs(items);
    message.success($t('platform.config.saved'));
    await loadConfigs();
    if (props.groupName === 'basic') {
      await applySiteBranding();
      const userStore = useUserStore();
      if (userStore.userInfo) {
        userStore.setUserInfo({
          ...userStore.userInfo,
          homePath: preferences.app.defaultHomePath,
        });
      }
    }
    if (props.groupName === 'storage') {
      await initAdminRuntimeConfig(true);
    }
  } finally {
    saving.value = false;
  }
}

function filterTimezoneOption(input: string, option?: DefaultOptionType) {
  const keyword = input.trim().toLowerCase();
  if (!keyword) {
    return true;
  }

  const label = String(option?.label ?? '').toLowerCase();
  const value = String(option?.value ?? '').toLowerCase();

  return label.includes(keyword) || value.includes(keyword);
}

function selectOptions(row: ConfigRow) {
  if (row.key === SITE_TIMEZONE_KEY) {
    return mergeSiteTimezoneSelectOptions(
      siteTimezoneOptions.value,
      draftValues.value[row.key],
    );
  }

  return getConfigSelectOptions()[row.key] ?? [];
}

function isSelectField(row: ConfigRow) {
  return row.type === 'select' || !!getConfigSelectOptions()[row.key];
}

async function onSmsTest() {
  const mobile = smsTestMobile.value.trim();
  if (!/^1[3-9]\d{9}$/.test(mobile)) {
    message.warning($t('authentication.mobileErrortip'));
    return;
  }

  smsTesting.value = true;
  try {
    await testSmsConfig(mobile);
    message.success($t('platform.config.smsTest.sent'));
  } finally {
    smsTesting.value = false;
  }
}

watch(
  () => props.groupName,
  () => {
    loadConfigs();
  },
);

onMounted(() => {
  loadConfigs();
});

defineExpose({
  onSave,
  saving: computed(() => saving.value),
});
</script>

<template>
  <div class="flex h-full min-h-0 flex-col overflow-hidden">
    <div ref="scrollWrapRef" class="min-h-0 flex-1">
      <Spin :spinning="loading" :tip="$t('system.shared.loading')">
        <div :style="scrollStyle">
          <Form class="config-settings-form w-full max-w-3xl pb-4" layout="vertical">
            <Form.Item
              v-for="row in visibleRows"
              :key="row.key"
              :label="configFieldLabel(row)"
            >
              <template v-if="configFieldRemark(row)" #extra>
                {{ configFieldRemark(row) }}
              </template>

              <Switch
                v-if="row.type === 'bool'"
                :checked="draftValues[row.key] === '1'"
                :disabled="!props.canManage"
                @update:checked="
                  (checked: boolean) =>
                    (draftValues[row.key] = checked ? '1' : '0')
                "
              />
              <InputNumber
                v-else-if="row.type === 'number'"
                :disabled="!props.canManage"
                :class="fieldControlClass"
                :value="Number(draftValues[row.key] || 0)"
                @update:value="
                  (val) => (draftValues[row.key] = String(val ?? ''))
                "
              />
              <HomePathCascader
                v-else-if="isHomePathSelectField(row.key)"
                :disabled="!props.canManage"
                :options="homePathCascaderOptions"
                :value="draftValues[row.key]"
                @update:value="(val) => (draftValues[row.key] = val)"
              />
              <Select
                v-else-if="isSelectField(row)"
                :disabled="!props.canManage"
                :class="fieldControlClass"
                :options="selectOptions(row)"
                :show-search="row.key === SITE_TIMEZONE_KEY"
                :filter-option="
                  row.key === SITE_TIMEZONE_KEY ? filterTimezoneOption : undefined
                "
                option-filter-prop="label"
                :value="draftValues[row.key] || undefined"
                @update:value="
                  (val) => (draftValues[row.key] = String(val ?? ''))
                "
              />
              <AttachmentField
                v-else-if="isAttachmentImageField(row.key)"
                accept="image"
                :disabled="!props.canManage"
                :multiple="false"
                :placeholder="configAttachmentPlaceholder(row.key)"
                :value="draftValues[row.key]"
                @update:value="(val) => (draftValues[row.key] = String(val ?? ''))"
              />
              <Input.TextArea
                v-else-if="row.type === 'text' || row.type === 'json'"
                :disabled="!props.canManage"
                :class="fieldControlClass"
                :rows="row.type === 'json' ? 4 : 3"
                :value="draftValues[row.key]"
                @update:value="(val) => (draftValues[row.key] = val)"
              />
              <Input
                v-else
                :disabled="!props.canManage"
                :class="fieldControlClass"
                :value="draftValues[row.key]"
                @update:value="(val) => (draftValues[row.key] = val)"
              />

              <div class="text-muted-foreground mt-1 text-xs">
                {{ row.key }}
              </div>
            </Form.Item>

            <div v-if="props.groupName === 'sms'" class="config-sms-test mt-6">
              <div class="config-sms-test__label mb-2 text-sm font-medium">
                {{ $t('platform.config.smsTest.title') }}
              </div>
              <div class="config-sms-test__actions flex w-full items-center gap-2">
                <Input
                  v-model:value="smsTestMobile"
                  class="config-sms-test__input config-field-control min-w-0 flex-1"
                  :disabled="!props.canManage || smsTesting"
                  :placeholder="$t('platform.config.smsTest.mobilePlaceholder')"
                />
                <Button
                  class="config-sms-test__button shrink-0"
                  :disabled="!props.canManage"
                  :loading="smsTesting"
                  type="primary"
                  @click="onSmsTest"
                >
                  {{ $t('platform.config.smsTest.send') }}
                </Button>
              </div>
            </div>
          </Form>
        </div>
      </Spin>
    </div>
  </div>
</template>

<style scoped>
.config-settings-form :deep(.ant-form-item-label) {
  padding: 0 0 2px;
}

.config-settings-form :deep(.ant-form-item-label > label) {
  font-size: 13.5px;
}

.config-settings-form :deep(.ant-form-item-extra) {
  font-size: 13px;
}

.config-settings-form :deep(.config-field-control),
.config-settings-form :deep(.config-field-control.ant-input-textarea) {
  width: 100%;
  max-width: 28rem;
}

.config-settings-form :deep(.attachment-field) {
  width: 100%;
  max-width: 28rem;
}

.config-sms-test {
  width: 100%;
  max-width: 28rem;
}

.config-sms-test__actions :deep(.config-sms-test__input.config-field-control) {
  width: auto;
  max-width: none;
}
</style>
