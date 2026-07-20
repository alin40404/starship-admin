<script lang="ts" setup>
import type { Sortable } from '@vben/hooks';

import {
  computed,
  nextTick,
  onBeforeUnmount,
  onMounted,
  ref,
  useTemplateRef,
  watch,
} from 'vue';

import { useAccess } from '@vben/access';
import { useSortable } from '@vben/hooks';
import { IconifyIcon } from '@vben/icons';

import { Button, Image, message } from 'ant-design-vue';

import AttachmentPickerModal from '#/components/attachment-field/attachment-picker-modal.vue';
import {
  isImageUrl,
  isVideoUrl,
} from '#/components/attachment-field/utils';
import { uploadAttachment } from '#/features/system/api/attachment';
import { ATTACHMENT_PERMISSIONS } from '#/features/system/constants/attachment-permissions';
import { validateUploadFile } from '#/utils/upload-policy';

const props = withDefaults(
  defineProps<{
    disabled?: boolean;
    maxCount?: number;
    modelValue?: string[];
  }>(),
  {
    disabled: false,
    maxCount: 20,
    modelValue: () => [],
  },
);

const emit = defineEmits<{
  'update:modelValue': [value: string[]];
}>();

defineOptions({ name: 'ProductMediaField' });

const { hasAccessByCodes } = useAccess();
const canUpload = computed(() =>
  hasAccessByCodes([ATTACHMENT_PERMISSIONS.UPLOAD]),
);

const pickerOpen = ref(false);
const uploading = ref(false);
const isDragOver = ref(false);
const isSorting = ref(false);
const fileInputRef = useTemplateRef<HTMLInputElement>('fileInputRef');
const gridRef = useTemplateRef<HTMLElement>('gridRef');
const previewUrl = ref('');
const previewVisible = ref(false);
/** Shopify 式勾选删除：选中的媒体下标 */
const selectedIndexes = ref<number[]>([]);
let sortableInstance: null | Sortable = null;

const urls = computed(() =>
  (props.modelValue ?? []).map((url) => String(url).trim()).filter(Boolean),
);

const canAddMore = computed(
  () => !props.maxCount || urls.value.length < props.maxCount,
);

const isInteractive = computed(() => !props.disabled);

const selectedCount = computed(() => selectedIndexes.value.length);

const isAllSelected = computed(
  () => urls.value.length > 0 && selectedCount.value === urls.value.length,
);

const isPartialSelected = computed(
  () => selectedCount.value > 0 && !isAllSelected.value,
);

watch(
  () => urls.value.length,
  () => {
    selectedIndexes.value = selectedIndexes.value.filter(
      (index) => index >= 0 && index < urls.value.length,
    );
  },
);

function emitUrls(next: string[]) {
  const limited =
    props.maxCount && next.length > props.maxCount
      ? next.slice(0, props.maxCount)
      : next;
  emit('update:modelValue', limited);
}

function onDragOver() {
  if (!isInteractive.value) return;
  isDragOver.value = true;
}

function onDragLeave() {
  isDragOver.value = false;
}

async function onDrop(event: DragEvent) {
  isDragOver.value = false;
  if (!isInteractive.value || !canAddMore.value) return;
  const files = [...(event.dataTransfer?.files ?? [])];
  await uploadFiles(files);
}

function onUploadClick() {
  if (!isInteractive.value) return;
  if (!canUpload.value) {
    message.warning('暂无上传权限');
    return;
  }
  fileInputRef.value?.click();
}

function onSelectClick() {
  if (!isInteractive.value) return;
  pickerOpen.value = true;
}

async function onFileInputChange(event: Event) {
  const input = event.target as HTMLInputElement;
  const files = [...(input.files ?? [])];
  input.value = '';
  await uploadFiles(files);
}

async function uploadFiles(files: File[]) {
  if (files.length === 0 || !isInteractive.value) return;
  if (!canUpload.value) {
    message.warning('暂无上传权限');
    return;
  }

  const remain = props.maxCount
    ? Math.max(props.maxCount - urls.value.length, 0)
    : files.length;
  const queue = files.slice(0, remain);
  if (queue.length === 0) {
    message.warning(`最多上传 ${props.maxCount} 个媒体文件`);
    return;
  }

  uploading.value = true;
  try {
    const uploaded: string[] = [];
    for (const file of queue) {
      const validationError = validateUploadFile(file, 'media');
      if (validationError) {
        message.error(validationError);
        continue;
      }
      const row = await uploadAttachment(file);
      const url = String(row.file_url ?? '').trim();
      if (url) uploaded.push(url);
    }
    if (uploaded.length > 0) {
      const merged = [...urls.value];
      for (const url of uploaded) {
        if (!merged.includes(url)) merged.push(url);
      }
      emitUrls(merged);
      message.success('上传成功');
    }
  } catch {
    message.error('上传失败');
  } finally {
    uploading.value = false;
  }
}

function onPickerConfirm(selected: string[]) {
  const next = [...urls.value];
  for (const url of selected.map((item) => item.trim()).filter(Boolean)) {
    if (!next.includes(url)) next.push(url);
  }
  emitUrls(next);
  pickerOpen.value = false;
}

function isSelected(index: number) {
  return selectedIndexes.value.includes(index);
}

function toggleSelect(index: number, event?: Event) {
  event?.stopPropagation();
  event?.preventDefault();
  if (!isInteractive.value) return;
  if (isSelected(index)) {
    selectedIndexes.value = selectedIndexes.value.filter((i) => i !== index);
  } else {
    selectedIndexes.value = [...selectedIndexes.value, index];
  }
}

function toggleSelectAll() {
  if (!isInteractive.value || urls.value.length === 0) return;
  if (isAllSelected.value) {
    selectedIndexes.value = [];
    return;
  }
  selectedIndexes.value = urls.value.map((_, index) => index);
}

function clearSelection() {
  selectedIndexes.value = [];
}

function onDeleteSelected() {
  if (selectedIndexes.value.length === 0) return;
  const removeSet = new Set(selectedIndexes.value);
  emitUrls(urls.value.filter((_, index) => !removeSet.has(index)));
  selectedIndexes.value = [];
  message.success('已删除所选媒体');
}

function remapSelectionAfterReorder(oldIndex: number, newIndex: number) {
  selectedIndexes.value = selectedIndexes.value.map((index) => {
    if (index === oldIndex) return newIndex;
    if (oldIndex < newIndex && index > oldIndex && index <= newIndex) {
      return index - 1;
    }
    if (oldIndex > newIndex && index >= newIndex && index < oldIndex) {
      return index + 1;
    }
    return index;
  });
}

function reorderAt(oldIndex: number, newIndex: number) {
  if (oldIndex === newIndex) return;
  const next = [...urls.value];
  const [moved] = next.splice(oldIndex, 1);
  if (!moved) return;
  next.splice(newIndex, 0, moved);
  remapSelectionAfterReorder(oldIndex, newIndex);
  emitUrls(next);
}

function destroySortable() {
  sortableInstance?.destroy();
  sortableInstance = null;
}

async function setupSortable() {
  destroySortable();
  if (!isInteractive.value || urls.value.length < 2) return;
  await nextTick();
  const el = gridRef.value;
  if (!el) return;

  const { initializeSortable } = useSortable(el, {
    animation: 200,
    delay: 0,
    delayOnTouchOnly: true,
    draggable: '.product-media-grid__sortable',
    handle: '.product-media-grid__handle',
    ghostClass: 'product-media-grid__item--ghost',
    chosenClass: 'product-media-grid__item--chosen',
    dragClass: 'product-media-grid__item--drag',
    onStart() {
      isSorting.value = true;
    },
    onEnd(evt) {
      const { oldIndex, newIndex } = evt;
      // 等本轮 click 冒泡过后再清，避免松手误开预览
      requestAnimationFrame(() => {
        isSorting.value = false;
      });
      if (
        oldIndex === undefined ||
        newIndex === undefined ||
        Number.isNaN(oldIndex) ||
        Number.isNaN(newIndex) ||
        oldIndex === newIndex
      ) {
        return;
      }
      reorderAt(oldIndex, newIndex);
    },
  });

  sortableInstance = await initializeSortable();
}

function onPreview(url: string) {
  if (isSorting.value) return;
  if (isImageUrl(url)) {
    previewUrl.value = url;
    previewVisible.value = true;
    return;
  }
  window.open(url, '_blank', 'noopener,noreferrer');
}

function onPreviewVisibleChange(visible: boolean) {
  previewVisible.value = visible;
  if (!visible) previewUrl.value = '';
}

watch([() => urls.value.length, isInteractive], () => {
  void setupSortable();
});

onMounted(() => {
  void setupSortable();
});

onBeforeUnmount(() => {
  destroySortable();
});
</script>

<template>
  <div class="w-full">
    <!-- 标题行：默认「媒体文件」/ 选中时切换为批量操作栏 -->
    <div class="mb-3 flex min-h-8 items-center justify-between gap-3">
      <template v-if="selectedCount > 0 && isInteractive">
        <div class="flex items-center gap-2">
          <button
            class="product-media-header__check"
            type="button"
            title="全选/取消"
            @click="toggleSelectAll"
          >
            <span
              class="product-media-grid__checkbox"
              :class="{
                'product-media-grid__checkbox--checked': isAllSelected,
                'product-media-grid__checkbox--partial': isPartialSelected,
              }"
            >
              <IconifyIcon
                v-if="isAllSelected"
                class="size-3 text-white"
                icon="lucide:check"
              />
              <IconifyIcon
                v-else-if="isPartialSelected"
                class="size-3 text-white"
                icon="lucide:minus"
              />
            </span>
          </button>
          <span class="text-sm font-medium">
            已选择 {{ selectedCount }} 个文件
          </span>
        </div>
        <div class="flex items-center gap-3">
          <button
            class="text-destructive hover:text-destructive/80 text-sm"
            type="button"
            @click="onDeleteSelected"
          >
            移除
          </button>
          <button
            class="text-muted-foreground hover:text-foreground text-sm"
            type="button"
            @click="clearSelection"
          >
            取消
          </button>
        </div>
      </template>
      <template v-else>
        <h3 class="text-sm font-semibold">媒体文件</h3>
      </template>
    </div>

    <div
      class="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-border px-4 py-10 transition-colors"
      :class="{
        'border-primary bg-primary/5': isDragOver,
        'cursor-not-allowed opacity-60': !isInteractive,
      }"
      @dragover.prevent="onDragOver"
      @dragleave.prevent="onDragLeave"
      @drop.prevent="onDrop"
    >
      <div class="flex flex-wrap items-center justify-center gap-3">
        <Button
          :disabled="!isInteractive || !canAddMore"
          :loading="uploading"
          @click="onUploadClick"
        >
          <IconifyIcon class="size-3.5" icon="lucide:upload" />
          上传新文件
        </Button>
        <button
          class="text-primary hover:text-primary/80 text-sm disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="!isInteractive || !canAddMore"
          type="button"
          @click="onSelectClick"
        >
          选择现有文件
        </button>
      </div>
      <p class="text-muted-foreground text-xs">
        支持图片、视频；第一张图片将作为商品主图
      </p>
    </div>

    <div
      v-if="urls.length > 0"
      ref="gridRef"
      class="product-media-grid mt-4"
      :class="{ 'product-media-grid--selecting': selectedCount > 0 }"
    >
      <div
        v-for="(url, index) in urls"
        :key="url"
        class="product-media-grid__item product-media-grid__sortable group relative"
        :class="{
          'product-media-grid__item--main': index === 0,
          'product-media-grid__item--selected': isSelected(index),
        }"
      >
        <button
          class="product-media-grid__card bg-background relative flex size-full items-center justify-center overflow-hidden rounded-lg border border-border p-1.5"
          type="button"
          @click="onPreview(url)"
        >
          <img
            v-if="isImageUrl(url)"
            class="size-full object-contain"
            :alt="`媒体 ${index + 1}`"
            :src="url"
          />
          <div
            v-else-if="isVideoUrl(url)"
            class="text-muted-foreground flex flex-col items-center gap-1 text-xs"
          >
            <IconifyIcon
              :class="index === 0 ? 'size-10' : 'size-6'"
              icon="lucide:video"
            />
            <span>视频</span>
          </div>
          <div
            v-else
            class="text-muted-foreground flex flex-col items-center gap-1 text-xs"
          >
            <IconifyIcon
              :class="index === 0 ? 'size-10' : 'size-6'"
              icon="lucide:file"
            />
            <span>文件</span>
          </div>
          <!-- Hover / 选中蒙板 -->
          <span class="product-media-grid__overlay" aria-hidden="true"></span>
        </button>

        <template v-if="isInteractive">
          <button
            class="product-media-grid__check"
            type="button"
            title="选择"
            @click="toggleSelect(index, $event)"
          >
            <span
              class="product-media-grid__checkbox"
              :class="{
                'product-media-grid__checkbox--checked': isSelected(index),
              }"
            >
              <IconifyIcon
                v-if="isSelected(index)"
                class="size-3 text-white"
                icon="lucide:check"
              />
            </span>
          </button>
          <span class="product-media-grid__handle" title="拖拽排序">
            <IconifyIcon class="size-3.5" icon="lucide:grip" />
          </span>
        </template>
      </div>

      <button
        v-if="isInteractive && canAddMore"
        class="product-media-grid__item product-media-grid__add text-muted-foreground hover:border-primary hover:text-primary flex items-center justify-center rounded-lg border border-dashed border-border transition-colors disabled:cursor-not-allowed disabled:opacity-50"
        type="button"
        :disabled="uploading"
        @click="onSelectClick"
      >
        <IconifyIcon class="size-5" icon="lucide:plus" />
      </button>
    </div>

    <input
      ref="fileInputRef"
      class="hidden"
      type="file"
      accept="image/*,video/*"
      :disabled="disabled"
      multiple
      @change="onFileInputChange"
    />

    <AttachmentPickerModal
      v-model:open="pickerOpen"
      accept="media"
      :initial-urls="urls"
      multiple
      @confirm="onPickerConfirm"
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
.product-media-header__check {
  display: flex;
  padding: 0;
  border: none;
  background: transparent;
  cursor: pointer;
}

.product-media-grid {
  --media-thumb: 5.5rem;
  display: grid;
  grid-template-columns: repeat(auto-fill, var(--media-thumb));
  grid-auto-rows: var(--media-thumb);
  gap: 0.75rem;
  width: 100%;
  align-items: start;
}

.product-media-grid__item {
  width: var(--media-thumb);
  height: var(--media-thumb);
  min-width: 0;
}

.product-media-grid__item--main {
  width: calc(var(--media-thumb) * 2 + 0.75rem);
  height: calc(var(--media-thumb) * 2 + 0.75rem);
  grid-column: span 2;
  grid-row: span 2;
}

.product-media-grid__card {
  position: relative;
}

.product-media-grid__overlay {
  position: absolute;
  inset: 0;
  background: rgb(33 33 33 / 45%);
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.15s ease;
}

.product-media-grid__item:hover .product-media-grid__overlay,
.product-media-grid__item--selected .product-media-grid__overlay {
  opacity: 1;
}

.product-media-grid__add {
  background: hsl(var(--muted) / 0.35);
}

/* 左上勾选：悬停显示；进入选择模式后全部常显 */
.product-media-grid__check {
  position: absolute;
  top: 6px;
  left: 6px;
  z-index: 3;
  display: flex;
  padding: 0;
  border: none;
  background: transparent;
  cursor: pointer;
  opacity: 0;
  transition: opacity 0.15s ease;
}

.product-media-grid__item:hover .product-media-grid__check,
.product-media-grid__item--selected .product-media-grid__check,
.product-media-grid--selecting .product-media-grid__check {
  opacity: 1;
}

.product-media-grid__checkbox {
  display: flex;
  width: 18px;
  height: 18px;
  align-items: center;
  justify-content: center;
  border: 1px solid hsl(var(--border));
  border-radius: 4px;
  background: #fff;
  box-shadow: 0 1px 2px rgb(0 0 0 / 8%);
}

.product-media-grid__checkbox--checked,
.product-media-grid__checkbox--partial {
  border-color: #303030;
  background: #303030;
}

/* 右上拖拽柄：悬停显示 */
.product-media-grid__handle {
  position: absolute;
  top: 6px;
  right: 6px;
  z-index: 3;
  display: flex;
  width: 22px;
  height: 22px;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  background: hsl(var(--background) / 92%);
  box-shadow: 0 1px 2px rgb(0 0 0 / 8%);
  color: hsl(var(--muted-foreground));
  cursor: grab;
  opacity: 0;
  transition: opacity 0.15s ease;
}

.product-media-grid__item:hover .product-media-grid__handle,
.product-media-grid__item--chosen .product-media-grid__handle,
.product-media-grid__item--drag .product-media-grid__handle {
  opacity: 1;
}

.product-media-grid__item--ghost {
  opacity: 0.35;
}

.product-media-grid__item--drag {
  opacity: 1;
  cursor: grabbing;
}

.product-media-grid__item--drag .product-media-grid__handle {
  cursor: grabbing;
}

@media (min-width: 640px) {
  .product-media-grid {
    --media-thumb: 6.5rem;
  }
}
</style>
