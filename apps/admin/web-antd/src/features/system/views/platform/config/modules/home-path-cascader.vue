<script lang="ts" setup>
import type { HomePathCascaderOption } from '../data';

import { ref, watch } from 'vue';

import { Cascader } from 'ant-design-vue';

import { $t } from '#/locales';

import {
  cascaderValueToHomePath,
  formatHomePathDisplay,
  homePathToCascaderValue,
  resolveHomePathCascaderLabels,
} from '../data';

const props = defineProps<{
  disabled?: boolean;
  options: HomePathCascaderOption[];
  value?: string;
}>();

const emit = defineEmits<{
  'update:value': [string];
}>();

const cascaderValue = ref<string[]>([]);

watch(
  () => [props.options, props.value] as const,
  () => {
    cascaderValue.value = homePathToCascaderValue(
      props.options,
      props.value ?? '',
    );
  },
  { immediate: true },
);

function filterCascaderOption(input: string, path: HomePathCascaderOption[]) {
  const keyword = input.trim().toLowerCase();
  if (!keyword) {
    return true;
  }
  return path.some(
    (option) =>
      option.label.toLowerCase().includes(keyword) ||
      (option.value.startsWith('/') &&
        option.value.toLowerCase().includes(keyword)),
  );
}

function onCascaderChange(values: string[]) {
  cascaderValue.value = values;
  emit('update:value', cascaderValueToHomePath(values));
}

function displayRender({
  labels,
}: {
  labels: string[];
  selectedOptions?: HomePathCascaderOption[];
}) {
  const values = cascaderValue.value;
  const resolvedLabels =
    labels.length > 0
      ? labels
      : resolveHomePathCascaderLabels(props.options, values);
  const homePath = props.value ?? cascaderValueToHomePath(values);
  return formatHomePathDisplay(resolvedLabels, homePath);
}
</script>

<template>
  <Cascader
    :value="cascaderValue"
    change-on-select
    class="home-path-cascader config-field-control w-full"
    :disabled="disabled"
    :display-render="displayRender"
    :options="options"
    :placeholder="$t('platform.config.selectOptions.homePathPlaceholder')"
    :show-search="{ filter: filterCascaderOption }"
    @update:value="onCascaderChange"
  />
</template>

<style scoped>
.home-path-cascader :deep(.ant-select-selector),
.home-path-cascader :deep(.ant-select-selection-item),
.home-path-cascader :deep(.ant-select-selection-placeholder) {
  font-size: var(--text-sm, 0.875rem) !important;
}
</style>
