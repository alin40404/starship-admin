<script lang="ts" setup>
import { computed, ref, watch } from 'vue';

import { IconifyIcon } from '@vben/icons';

import {
  Button,
  Image,
  Input,
  InputNumber,
  Select,
  Switch,
  Table,
  Tag,
} from 'ant-design-vue';

import AttachmentPickerModal from '#/components/attachment-field/attachment-picker-modal.vue';

import {
  buildCartesianSpecs,
  createEmptySkuRow,
  mergeSkuRows,
  type ProductVariable,
  type SkuFormRow,
} from '../data';

const enableMulti = defineModel<boolean>('enableMulti', { default: false });
const variables = defineModel<ProductVariable[]>('variables', {
  default: () => [],
});
const skus = defineModel<SkuFormRow[]>('skus', {
  default: () => [createEmptySkuRow()],
});

type SkuEditableField =
  | 'sku_code'
  | 'image'
  | 'price_yuan'
  | 'compare_at_price_yuan'
  | 'stock'
  | 'status';

const batchDraft = ref({
  price_yuan: undefined as number | undefined,
  compare_at_price_yuan: undefined as number | undefined,
  stock: undefined as number | undefined,
});

const imagePickerOpen = ref(false);
const imagePickerIndex = ref(-1);
const previewUrl = ref('');
const previewVisible = ref(false);

const singleSku = computed({
  get: () => skus.value[0] ?? createEmptySkuRow(),
  set: (row: SkuFormRow) => {
    skus.value = [createEmptySkuRow({ ...row, key: '', specs: null })];
  },
});

const skuCount = computed(() => (enableMulti.value ? skus.value.length : 1));

const skuColumns = [
  { title: '规格组合', dataIndex: 'specs', key: 'specs', width: 160 },
  { title: '图片', dataIndex: 'image', key: 'image', width: 72 },
  { title: 'SKU', dataIndex: 'sku_code', key: 'sku_code', width: 140 },
  { title: '售价', dataIndex: 'price_yuan', key: 'price_yuan', width: 110 },
  {
    title: '原价',
    dataIndex: 'compare_at_price_yuan',
    key: 'compare_at_price_yuan',
    width: 110,
  },
  { title: '库存', dataIndex: 'stock', key: 'stock', width: 100 },
  { title: '启用', dataIndex: 'status', key: 'status', width: 70 },
];

function regenerateSkus() {
  if (!enableMulti.value) return;
  const specsList = buildCartesianSpecs(variables.value);
  if (specsList.length === 0) {
    return;
  }
  const first = skus.value[0];
  skus.value = mergeSkuRows(specsList, skus.value, {
    price_yuan: first?.price_yuan ?? 0,
    compare_at_price_yuan: first?.compare_at_price_yuan ?? 0,
    stock: first?.stock ?? 0,
  });
}

watch(
  () =>
    variables.value
      .map((item) => `${item.name}:${item.values.join(',')}`)
      .join('|'),
  () => {
    if (enableMulti.value) regenerateSkus();
  },
);

watch(enableMulti, (enabled) => {
  if (enabled) {
    if (variables.value.length === 0) {
      variables.value = [{ name: '', values: [] }];
    } else {
      variables.value = variables.value.map((item) => ({
        name: item.name,
        values: item.values.map((v) => v.trim()).filter(Boolean),
      }));
    }
    regenerateSkus();
  } else {
    const first = skus.value[0];
    skus.value = [
      createEmptySkuRow({
        id: first?.id,
        key: '',
        specs: null,
        sku_code: first?.sku_code ?? '',
        image: first?.image ?? '',
        price_yuan: first?.price_yuan ?? 0,
        compare_at_price_yuan: first?.compare_at_price_yuan ?? 0,
        stock: first?.stock ?? 0,
        status: first?.status ?? 1,
      }),
    ];
  }
});

function addVariable() {
  if (variables.value.length >= 3) return;
  variables.value = [...variables.value, { name: '', values: [] }];
}

function removeVariable(index: number) {
  variables.value = variables.value.filter((_, i) => i !== index);
}

function updateVariableName(index: number, name: string) {
  const next = [...variables.value];
  const row = next[index];
  if (!row) return;
  next[index] = { ...row, name };
  variables.value = next;
}

function updateVariableValues(index: number, values: string[]) {
  const next = [...variables.value];
  const row = next[index];
  if (!row) return;
  const cleaned = values
    .map((v) => String(v ?? '').trim())
    .filter(Boolean)
    .filter((v, i, arr) => arr.indexOf(v) === i);
  next[index] = { ...row, values: cleaned };
  variables.value = next;
}

function updateSkuField(
  index: number,
  field: SkuEditableField,
  value: number | string,
) {
  const next = [...skus.value];
  const row = next[index];
  if (!row) return;
  next[index] = { ...row, [field]: value };
  skus.value = next;
}

function applyBatchSettings() {
  const draft = batchDraft.value;
  skus.value = skus.value.map((row) => {
    const next = { ...row };
    if (draft.price_yuan !== undefined) next.price_yuan = Number(draft.price_yuan);
    if (draft.compare_at_price_yuan !== undefined) {
      next.compare_at_price_yuan = Number(draft.compare_at_price_yuan);
    }
    if (draft.stock !== undefined) next.stock = Number(draft.stock);
    return next;
  });
}

function openImagePicker(index: number) {
  imagePickerIndex.value = index;
  imagePickerOpen.value = true;
}

function clearSkuImage(index: number) {
  updateSkuField(index, 'image', '');
}

function onImagePickerConfirm(urls: string[]) {
  const index = imagePickerIndex.value;
  if (index < 0) return;
  updateSkuField(index, 'image', urls[0] ?? '');
  imagePickerOpen.value = false;
  imagePickerIndex.value = -1;
}

function openPreview(url: string) {
  previewUrl.value = url;
  previewVisible.value = true;
}

function onPreviewVisibleChange(visible: boolean) {
  previewVisible.value = visible;
  if (!visible) previewUrl.value = '';
}

function formatSpecsLabel(specs: Record<string, string> | null) {
  if (!specs) return '-';
  return Object.values(specs).join(' / ');
}

function validate(): string | null {
  if (!enableMulti.value) {
    const row = skus.value[0];
    if (!row) return '请填写商品库存信息';
    if (Number(row.price_yuan) < 0) return '售价不能为负数';
    if (Number(row.stock) < 0) return '库存不能为负数';
    const compareAt = Number(row.compare_at_price_yuan ?? 0);
    if (compareAt > 0 && compareAt <= Number(row.price_yuan)) {
      return '原价须大于售价才会作为划线价生效；请提高原价或清空原价';
    }
    return null;
  }

  const validVars = variables.value.filter(
    (item) => item.name.trim() && item.values.some((v) => v.trim()),
  );
  if (validVars.length === 0) {
    return '请至少添加一个选项及选项值';
  }
  for (const item of validVars) {
    if (!item.name.trim()) return '请填写选项名称';
    if (item.values.length === 0) return `请为「${item.name}」添加至少一个值`;
  }
  if (skus.value.length === 0) {
    return '请完善选项以生成规格组合';
  }
  if (skus.value.length > 120) {
    return '多规格建议不超过 120 个，请减少选项组合';
  }
  for (const [index, row] of skus.value.entries()) {
    if (Number(row.price_yuan) < 0) {
      return `第 ${index + 1} 行售价不能为负数`;
    }
    const compareAt = Number(row.compare_at_price_yuan ?? 0);
    if (compareAt > 0 && compareAt <= Number(row.price_yuan)) {
      return `第 ${index + 1} 行原价须大于售价，或清空原价`;
    }
  }
  return null;
}

defineExpose({ validate });
</script>

<template>
  <div class="sku-editor space-y-4">
    <div class="flex items-center gap-3">
      <span class="text-sm font-medium">启用多规格</span>
      <Switch v-model:checked="enableMulti" size="small" />
    </div>

    <template v-if="!enableMulti">
      <div class="grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2 lg:grid-cols-4">
        <div>
          <div class="mb-1 text-sm">SKU</div>
          <Input
            :value="singleSku.sku_code"
            allow-clear
            placeholder="留空则自动生成"
            @update:value="
              (v) => (singleSku = { ...singleSku, sku_code: String(v ?? '') })
            "
          />
        </div>
        <div>
          <div class="mb-1 text-sm">售价（元）</div>
          <InputNumber
            :value="singleSku.price_yuan"
            class="w-full"
            :min="0"
            :precision="2"
            @update:value="
              (v) => (singleSku = { ...singleSku, price_yuan: Number(v ?? 0) })
            "
          />
        </div>
        <div>
          <div class="mb-1 text-sm">原价（元）</div>
          <InputNumber
            :value="singleSku.compare_at_price_yuan"
            class="w-full"
            :min="0"
            :precision="2"
            placeholder="选填"
            @update:value="
              (v) =>
                (singleSku = {
                  ...singleSku,
                  compare_at_price_yuan: Number(v ?? 0),
                })
            "
          />
        </div>
        <div>
          <div class="mb-1 text-sm">库存</div>
          <InputNumber
            :value="singleSku.stock"
            class="w-full"
            :min="0"
            @update:value="
              (v) => (singleSku = { ...singleSku, stock: Number(v ?? 0) })
            "
          />
        </div>
      </div>
    </template>

    <template v-else>
      <div class="space-y-3 rounded-lg border border-border p-4">
        <div class="flex items-center justify-between gap-3">
          <div>
            <div class="text-sm font-medium">选项</div>
            <div class="text-muted-foreground mt-0.5 text-xs">
              例如：颜色、尺码。输入选项值后按回车添加
            </div>
          </div>
          <Button
            size="small"
            type="dashed"
            :disabled="variables.length >= 3"
            @click="addVariable"
          >
            添加选项
          </Button>
        </div>

        <div
          v-for="(variable, varIndex) in variables"
          :key="varIndex"
          class="rounded-lg bg-muted/30 p-3"
        >
          <div class="sku-editor__option-row flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-[160px] sm:shrink-0">
              <div class="text-muted-foreground mb-1 text-xs">选项名称</div>
              <Input
                :value="variable.name"
                placeholder="如：颜色"
                @update:value="
                  (v) => updateVariableName(varIndex, String(v ?? ''))
                "
              />
            </div>
            <div class="min-w-0 flex-1">
              <div class="text-muted-foreground mb-1 text-xs">选项值</div>
              <Select
                class="w-full"
                mode="tags"
                :token-separators="[',']"
                :value="variable.values"
                placeholder="输入后回车，如：红、蓝、绿"
                @update:value="
                  (v) =>
                    updateVariableValues(varIndex, (v as string[]) ?? [])
                "
              />
            </div>
            <Button
              danger
              size="small"
              type="link"
              class="mb-0.5 shrink-0"
              @click="removeVariable(varIndex)"
            >
              删除
            </Button>
          </div>
        </div>

        <div class="text-muted-foreground text-xs">
          最多 3 个选项；组合数建议不超过 120（当前 {{ skuCount }} 个）
        </div>
      </div>

      <div class="space-y-3">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
          <div class="text-sm font-medium">规格组合（{{ skuCount }}）</div>
          <div class="text-muted-foreground text-xs">
            第一行为前台默认规格
          </div>
        </div>

        <div
          class="sku-editor__batch flex flex-wrap items-center gap-2 rounded-md bg-muted/40 px-3 py-2"
        >
          <InputNumber
            v-model:value="batchDraft.price_yuan"
            class="w-[110px]"
            :min="0"
            :precision="2"
            placeholder="售价"
            size="small"
          />
          <InputNumber
            v-model:value="batchDraft.compare_at_price_yuan"
            class="w-[110px]"
            :min="0"
            :precision="2"
            placeholder="原价"
            size="small"
          />
          <InputNumber
            v-model:value="batchDraft.stock"
            class="w-[100px]"
            :min="0"
            placeholder="库存"
            size="small"
          />
          <Button size="small" type="primary" @click="applyBatchSettings">
            批量设置
          </Button>
        </div>

        <Table
          class="sku-editor__table"
          :columns="skuColumns"
          :data-source="skus"
          :pagination="false"
          :scroll="{ x: 780 }"
          row-key="key"
          size="small"
        >
          <template #bodyCell="{ column, record, index }">
            <template v-if="column.key === 'specs'">
              <Tag class="m-0">{{ formatSpecsLabel(record.specs) }}</Tag>
            </template>
            <template v-else-if="column.key === 'image'">
              <div class="sku-editor__image-cell">
                <button
                  v-if="record.image"
                  type="button"
                  class="sku-editor__image-thumb"
                  @click="openPreview(record.image)"
                >
                  <img :src="record.image" alt="" />
                </button>
                <button
                  v-else
                  type="button"
                  class="sku-editor__image-add"
                  @click="openImagePicker(index)"
                >
                  <IconifyIcon icon="lucide:plus" class="size-4" />
                </button>
                <button
                  v-if="record.image"
                  type="button"
                  class="sku-editor__image-edit"
                  title="更换图片"
                  @click="openImagePicker(index)"
                >
                  <IconifyIcon icon="lucide:pencil" class="size-3" />
                </button>
                <button
                  v-if="record.image"
                  type="button"
                  class="sku-editor__image-clear"
                  title="清除图片"
                  @click="clearSkuImage(index)"
                >
                  <IconifyIcon icon="lucide:x" class="size-3" />
                </button>
              </div>
            </template>
            <template v-else-if="column.key === 'sku_code'">
              <div class="space-y-0.5">
                <Input
                  :value="record.sku_code"
                  allow-clear
                  size="small"
                  placeholder="自动生成"
                  @update:value="
                    (v) => updateSkuField(index, 'sku_code', String(v ?? ''))
                  "
                />
                <div
                  v-if="index === 0"
                  class="text-[11px] leading-none text-emerald-600"
                >
                  默认
                </div>
              </div>
            </template>
            <template v-else-if="column.key === 'price_yuan'">
              <InputNumber
                :value="record.price_yuan"
                class="w-full"
                size="small"
                :min="0"
                :precision="2"
                @update:value="
                  (v) => updateSkuField(index, 'price_yuan', Number(v ?? 0))
                "
              />
            </template>
            <template v-else-if="column.key === 'compare_at_price_yuan'">
              <InputNumber
                :value="record.compare_at_price_yuan"
                class="w-full"
                size="small"
                :min="0"
                :precision="2"
                @update:value="
                  (v) =>
                    updateSkuField(
                      index,
                      'compare_at_price_yuan',
                      Number(v ?? 0),
                    )
                "
              />
            </template>
            <template v-else-if="column.key === 'stock'">
              <InputNumber
                :value="record.stock"
                class="w-full"
                size="small"
                :min="0"
                @update:value="
                  (v) => updateSkuField(index, 'stock', Number(v ?? 0))
                "
              />
            </template>
            <template v-else-if="column.key === 'status'">
              <Switch
                size="small"
                :checked="Number(record.status) === 1"
                @update:checked="
                  (checked) => updateSkuField(index, 'status', checked ? 1 : 0)
                "
              />
            </template>
          </template>
        </Table>
      </div>
    </template>

    <AttachmentPickerModal
      v-model:open="imagePickerOpen"
      accept="image"
      :initial-urls="
        imagePickerIndex >= 0 && skus[imagePickerIndex]?.image
          ? [skus[imagePickerIndex]!.image]
          : []
      "
      :multiple="false"
      @confirm="onImagePickerConfirm"
    />

    <Image
      v-if="previewUrl"
      :preview="{
        visible: previewVisible,
        onVisibleChange: onPreviewVisibleChange,
      }"
      :src="previewUrl"
      :style="{
        position: 'fixed',
        width: 0,
        height: 0,
        opacity: 0,
        pointerEvents: 'none',
      }"
    />
  </div>
</template>

<style scoped>
.sku-editor__option-row :deep(.ant-input) {
  height: 32px;
}

.sku-editor__option-row :deep(.ant-select-selector) {
  min-height: 32px !important;
  padding-block: 0 !important;
  align-items: center;
}

.sku-editor__option-row :deep(.ant-select-selection-overflow) {
  min-height: 30px;
  align-items: center;
}

.sku-editor__option-row :deep(.ant-select-selection-search-input) {
  height: 30px !important;
}

.sku-editor__option-row :deep(.ant-select-selection-placeholder) {
  line-height: 30px;
}

.sku-editor__table :deep(.ant-table-thead > tr > th) {
  font-size: 12px;
  font-weight: 500;
  padding: 8px 6px;
  white-space: nowrap;
}

.sku-editor__table :deep(.ant-table-tbody > tr > td) {
  padding: 8px 6px;
  vertical-align: middle;
}

.sku-editor__image-cell {
  position: relative;
  width: 44px;
  height: 44px;
}

.sku-editor__image-thumb,
.sku-editor__image-add {
  display: flex;
  width: 44px;
  height: 44px;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border: 1px dashed hsl(var(--border));
  border-radius: 6px;
  background: hsl(var(--muted) / 0.35);
  cursor: pointer;
  padding: 0;
}

.sku-editor__image-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.sku-editor__image-add:hover,
.sku-editor__image-thumb:hover {
  border-color: hsl(var(--primary));
}

.sku-editor__image-edit,
.sku-editor__image-clear {
  position: absolute;
  top: -4px;
  display: flex;
  width: 16px;
  height: 16px;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 999px;
  background: hsl(var(--background));
  box-shadow: 0 0 0 1px hsl(var(--border));
  color: hsl(var(--muted-foreground));
  cursor: pointer;
  padding: 0;
}

.sku-editor__image-edit {
  right: 14px;
}

.sku-editor__image-clear {
  right: -4px;
}

.sku-editor__image-edit:hover,
.sku-editor__image-clear:hover {
  color: hsl(var(--foreground));
}
</style>
