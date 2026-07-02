<script lang="ts" setup>
import type { ConfigRow } from '#/api/system/config';

import { computed, onMounted, ref } from 'vue';

import { Button, Card, Input, InputNumber, message, Switch } from 'ant-design-vue';

import { batchUpsertConfigs, fetchConfigs } from '#/api/system/config';

const props = defineProps<{
  canManage: boolean;
}>();

const loading = ref(false);
const saving = ref(false);
const grouped = ref<Record<string, ConfigRow[]>>({});
const draftValues = ref<Record<string, string>>({});

const groupLabels: Record<string, string> = {
  default: '默认',
  site: '站点',
};

function groupTitle(name: string) {
  return groupLabels[name] ?? name;
}

const groupNames = computed(() => Object.keys(grouped.value).sort());

function syncDraftFromRows(rows: ConfigRow[]) {
  const next: Record<string, string> = { ...draftValues.value };
  for (const row of rows) {
    next[row.key] = row.value ?? '';
  }
  draftValues.value = next;
}

async function loadConfigs() {
  loading.value = true;
  try {
    const result = await fetchConfigs();
    const rawGrouped = result.grouped ?? {};
    const filteredGrouped: Record<string, ConfigRow[]> = {};
    for (const [name, rows] of Object.entries(rawGrouped)) {
      if (name === 'param') continue;
      filteredGrouped[name] = rows;
    }
    grouped.value = filteredGrouped;
    syncDraftFromRows(
      (result.list ?? []).filter((row) => row.group_name !== 'param'),
    );
  } finally {
    loading.value = false;
  }
}

async function onSave() {
  const items = (Object.values(grouped.value).flat() as ConfigRow[]).map(
    (row) => ({
      group_name: row.group_name,
      key: row.key,
      name: row.name,
      remark: row.remark,
      sort: row.sort,
      type: row.type,
      value: draftValues.value[row.key] ?? '',
    }),
  );
  saving.value = true;
  try {
    await batchUpsertConfigs(items);
    message.success('配置已保存');
    await loadConfigs();
  } finally {
    saving.value = false;
  }
}

onMounted(() => {
  loadConfigs();
});
</script>

<template>
  <div>
    <div class="mb-4 flex items-center justify-between">
      <div class="text-muted-foreground text-sm">
        维护站点名称等固定配置项，按分组展示。
      </div>
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

    <div v-else class="flex flex-col gap-4">
      <Card
        v-for="group in groupNames"
        :key="group"
        :title="groupTitle(group)"
      >
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div
            v-for="row in grouped[group]"
            :key="row.key"
            class="flex flex-col gap-1"
          >
            <label class="text-sm font-medium">{{ row.name }}</label>
            <p v-if="row.remark" class="text-muted-foreground mb-1 text-xs">
              {{ row.remark }}
            </p>
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
              class="w-full"
              :value="Number(draftValues[row.key] || 0)"
              @update:value="
                (val) => (draftValues[row.key] = String(val ?? ''))
              "
            />
            <Input.TextArea
              v-else-if="row.type === 'json'"
              :disabled="!props.canManage"
              :rows="3"
              :value="draftValues[row.key]"
              @update:value="(val) => (draftValues[row.key] = val)"
            />
            <Input
              v-else
              :disabled="!props.canManage"
              :value="draftValues[row.key]"
              @update:value="(val) => (draftValues[row.key] = val)"
            />
            <span class="text-muted-foreground text-xs">{{ row.key }}</span>
          </div>
        </div>
      </Card>
    </div>
  </div>
</template>
