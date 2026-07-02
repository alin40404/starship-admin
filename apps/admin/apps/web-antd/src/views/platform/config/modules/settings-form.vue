<script lang="ts" setup>
import type { ConfigRow } from '#/api/system/config';

import { computed, onMounted, ref, watch } from 'vue';

import {
  Button,
  Form,
  Input,
  InputNumber,
  message,
  Select,
  Switch,
} from 'ant-design-vue';

import { batchUpsertConfigs, fetchConfigs } from '#/api/system/config';

import { CONFIG_SELECT_OPTIONS } from '../data';

const props = defineProps<{
  canManage: boolean;
  groupName: string;
}>();

const loading = ref(false);
const saving = ref(false);
const rows = ref<ConfigRow[]>([]);
const draftValues = ref<Record<string, string>>({});

const sortedRows = computed(() =>
  [...rows.value].sort((a, b) => a.sort - b.sort || a.id - b.id),
);

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
    message.success('配置已保存');
    await loadConfigs();
  } finally {
    saving.value = false;
  }
}

function selectOptions(key: string) {
  return CONFIG_SELECT_OPTIONS[key] ?? [];
}

function isSelectField(row: ConfigRow) {
  return row.type === 'select' || !!CONFIG_SELECT_OPTIONS[row.key];
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
</script>

<template>
  <div class="flex h-full min-h-0 flex-col">
    <div class="mb-4 flex shrink-0 justify-end">
      <Button
        v-if="props.canManage"
        :loading="saving"
        type="primary"
        @click="onSave"
      >
        保存配置
      </Button>
    </div>

    <div v-if="loading" class="text-muted-foreground py-8 text-center">
      加载中…
    </div>

    <div v-else class="min-h-0 flex-1 overflow-auto pb-4">
      <Form class="max-w-3xl" layout="vertical">
        <Form.Item
          v-for="row in sortedRows"
          :key="row.key"
          :label="row.name"
        >
          <template v-if="row.remark" #extra>
            {{ row.remark }}
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
            class="w-full max-w-md"
            :value="Number(draftValues[row.key] || 0)"
            @update:value="
              (val) => (draftValues[row.key] = String(val ?? ''))
            "
          />
          <Select
            v-else-if="isSelectField(row)"
            :disabled="!props.canManage"
            class="w-full max-w-md"
            :options="selectOptions(row.key)"
            :value="draftValues[row.key] || undefined"
            @update:value="
              (val) => (draftValues[row.key] = String(val ?? ''))
            "
          />
          <Input.TextArea
            v-else-if="row.type === 'text' || row.type === 'json'"
            :disabled="!props.canManage"
            :rows="row.type === 'json' ? 4 : 3"
            :value="draftValues[row.key]"
            @update:value="(val) => (draftValues[row.key] = val)"
          />
          <Input
            v-else
            :disabled="!props.canManage"
            class="max-w-md"
            :value="draftValues[row.key]"
            @update:value="(val) => (draftValues[row.key] = val)"
          />

          <div class="text-muted-foreground mt-1 text-xs">{{ row.key }}</div>
        </Form.Item>
      </Form>
    </div>
  </div>
</template>
