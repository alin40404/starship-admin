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
} from 'ant-design-vue';

import { batchUpsertConfigs, fetchConfigs } from '#/api/system/config';
import { AttachmentField } from '#/components/attachment-field';

import {
  configFieldLabel,
  configFieldRemark,
  getConfigSelectOptions,
  isAttachmentImageField,
  isStorageFieldVisible,
  STORAGE_DRIVER_KEY,
} from '../data';
import { applySiteBranding } from '#/utils/site-branding';

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

async function loadConfigs() {
  loading.value = true;
  try {
    const result = await fetchConfigs(props.groupName);
    rows.value = result.list ?? [];
    syncDraftFromRows(rows.value);
  } finally {
    loading.value = false;
  }
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
    }
  } finally {
    saving.value = false;
  }
}

function selectOptions(key: string) {
  return getConfigSelectOptions()[key] ?? [];
}

function isSelectField(row: ConfigRow) {
  return row.type === 'select' || !!getConfigSelectOptions()[row.key];
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
              <Select
                v-else-if="isSelectField(row)"
                :disabled="!props.canManage"
                :class="fieldControlClass"
                :options="selectOptions(row.key)"
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
                :placeholder="$t('platform.config.logoPlaceholder')"
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
</style>
