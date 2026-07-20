<script lang="ts" setup>
import {
  computed,
  nextTick,
  onBeforeUnmount,
  ref,
  useTemplateRef,
  watch,
} from 'vue';

import { useAccess } from '@vben/access';
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
const previewUrl = ref('');
const previewVisible = ref(false);
/** Shopify 式勾选删除：选中的媒体下标 */
const selectedIndexes = ref<number[]>([]);

/** 拖拽中的草稿顺序（浮层 + FLIP，不改真实 DOM 插入） */
const draftUrls = ref<null | string[]>(null);

interface DragSession {
  activeUrl: string;
  grabRatioX: number;
  grabRatioY: number;
  mainHeight: number;
  mainWidth: number;
  overIndex: number;
  pointerId: number;
  pointerX: number;
  pointerY: number;
  startX: number;
  startY: number;
  started: boolean;
  thumbHeight: number;
  thumbWidth: number;
}

const drag = ref<null | DragSession>(null);
const itemEls = new Map<string, HTMLElement>();
const FLIP_MS = 200;
const ACTIVATE_PX = 4;
/** FLIP 进行中禁止再次换位，避免动画半截被测量导致来回抖 */
let flipLocked = false;
let pendingOverIndex: null | number = null;
let flipUnlockTimer: null | ReturnType<typeof setTimeout> = null;
/** 当前草稿顺序下的稳定布局盒（不受 FLIP transform 影响） */
let slotRects = new Map<string, DOMRect>();

const urls = computed(() =>
  (props.modelValue ?? []).map((url) => String(url).trim()).filter(Boolean),
);

const displayUrls = computed(() => draftUrls.value ?? urls.value);

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

const overlayStyle = computed(() => {
  const session = drag.value;
  if (!session?.started) return null;
  // 命中主图位 → 大图浮层；命中小图位 → 及时缩小
  const isMainSlot = session.overIndex === 0;
  const width = isMainSlot ? session.mainWidth : session.thumbWidth;
  const height = isMainSlot ? session.mainHeight : session.thumbHeight;
  const offsetX = session.grabRatioX * width;
  const offsetY = session.grabRatioY * height;
  return {
    width: `${width}px`,
    height: `${height}px`,
    transform: `translate3d(${session.pointerX - offsetX}px, ${session.pointerY - offsetY}px, 0) scale(1.04)`,
  };
});

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

function isUrlSelected(url: string) {
  const index = urls.value.indexOf(url);
  return index >= 0 && selectedIndexes.value.includes(index);
}

function toggleSelectByUrl(url: string, event?: Event) {
  const index = urls.value.indexOf(url);
  if (index < 0) return;
  toggleSelect(index, event);
}

function toggleSelect(index: number, event?: Event) {
  event?.stopPropagation();
  event?.preventDefault();
  if (!isInteractive.value) return;
  if (selectedIndexes.value.includes(index)) {
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

function moveItem(list: string[], from: number, to: number) {
  if (from === to) return list;
  const next = [...list];
  const [moved] = next.splice(from, 1);
  if (!moved) return list;
  next.splice(to, 0, moved);
  return next;
}

function setItemRef(url: string, el: Element | null) {
  if (el instanceof HTMLElement) {
    itemEls.set(url, el);
  } else {
    itemEls.delete(url);
  }
}

/** 清掉进行中的 FLIP，读真实布局盒，避免 transform 污染命中/测量 */
function snapFlipStyles() {
  for (const el of itemEls.values()) {
    el.style.transition = 'none';
    el.style.transform = '';
    el.style.transformOrigin = '';
  }
}

function captureLayoutRects() {
  snapFlipStyles();
  const map = new Map<string, DOMRect>();
  for (const [url, el] of itemEls) {
    map.set(url, el.getBoundingClientRect());
  }
  slotRects = map;
  return map;
}

function playFlip(before: Map<string, DOMRect>) {
  for (const [url, el] of itemEls) {
    const prev = before.get(url);
    if (!prev) continue;
    const next = slotRects.get(url) ?? el.getBoundingClientRect();
    const dx = prev.left - next.left;
    const dy = prev.top - next.top;
    // 只做位移 FLIP；尺寸交给 grid 直接变，避免 scale 与布局互相打架抖动
    if (Math.abs(dx) < 0.5 && Math.abs(dy) < 0.5) continue;

    el.style.transition = 'none';
    el.style.transform = `translate(${dx}px, ${dy}px)`;
    void el.offsetWidth;
    el.style.transition = `transform ${FLIP_MS}ms cubic-bezier(0.2, 0, 0, 1)`;
    el.style.transform = '';
  }
}

function lockFlip() {
  flipLocked = true;
  if (flipUnlockTimer) clearTimeout(flipUnlockTimer);
  flipUnlockTimer = setTimeout(() => {
    flipLocked = false;
    flipUnlockTimer = null;
    snapFlipStyles();
    if (pendingOverIndex !== null) {
      const next = pendingOverIndex;
      pendingOverIndex = null;
      void moveActiveTo(next);
    }
  }, FLIP_MS + 16);
}

/**
 * 用缓存布局盒命中，并对当前格做粘滞，
 * 防止主图/小图交界处分数来回跳。
 */
function hitTestIndex(clientX: number, clientY: number) {
  const list = draftUrls.value ?? urls.value;
  const session = drag.value;
  const stickyIndex = session?.overIndex ?? 0;
  let bestIndex = stickyIndex;
  let bestScore = Infinity;

  list.forEach((url, index) => {
    const rect = slotRects.get(url);
    if (!rect) return;

    const cx = rect.left + rect.width / 2;
    const cy = rect.top + rect.height / 2;
    let score = (clientX - cx) ** 2 + (clientY - cy) ** 2;
    const inside =
      clientX >= rect.left &&
      clientX <= rect.right &&
      clientY >= rect.top &&
      clientY <= rect.bottom;
    if (inside) score *= 0.15;
    // 当前落点更「粘」，需要明显更近才切换
    if (index === stickyIndex) score *= 0.55;

    if (score < bestScore) {
      bestScore = score;
      bestIndex = index;
    }
  });

  return bestIndex;
}

async function moveActiveTo(index: number) {
  const session = drag.value;
  const list = draftUrls.value;
  if (!session || !list) return;

  const from = list.indexOf(session.activeUrl);
  if (from < 0 || from === index) {
    session.overIndex = index;
    return;
  }

  if (flipLocked) {
    pendingOverIndex = index;
    return;
  }

  const before = captureLayoutRects();
  draftUrls.value = moveItem(list, from, index);
  session.overIndex = index;
  lockFlip();
  await nextTick();
  // 换位后先记下最终布局，再开 FLIP；命中始终读这套稳定盒
  snapFlipStyles();
  for (const [url, el] of itemEls) {
    slotRects.set(url, el.getBoundingClientRect());
  }
  playFlip(before);
}

function bindDragListeners() {
  window.addEventListener('pointermove', onWindowPointerMove, {
    passive: false,
  });
  window.addEventListener('pointerup', onWindowPointerUp);
  window.addEventListener('pointercancel', onWindowPointerUp);
}

function unbindDragListeners() {
  window.removeEventListener('pointermove', onWindowPointerMove);
  window.removeEventListener('pointerup', onWindowPointerUp);
  window.removeEventListener('pointercancel', onWindowPointerUp);
}

function resetDrag() {
  unbindDragListeners();
  if (flipUnlockTimer) {
    clearTimeout(flipUnlockTimer);
    flipUnlockTimer = null;
  }
  flipLocked = false;
  pendingOverIndex = null;
  slotRects = new Map();
  snapFlipStyles();
  document.body.style.removeProperty('user-select');
  document.body.style.removeProperty('cursor');
  drag.value = null;
  draftUrls.value = null;
  requestAnimationFrame(() => {
    isSorting.value = false;
  });
}

function commitDrag() {
  const session = drag.value;
  const next = draftUrls.value;
  if (!session?.started || !next) {
    resetDrag();
    return;
  }

  const prev = urls.value;
  const selectedUrls = new Set(
    selectedIndexes.value
      .map((index) => prev[index])
      .filter((url): url is string => Boolean(url)),
  );
  selectedIndexes.value = next
    .map((url, index) => (selectedUrls.has(url) ? index : -1))
    .filter((index) => index >= 0);

  if (next.join('\0') !== prev.join('\0')) {
    emitUrls(next);
  }
  resetDrag();
}

function onWindowPointerMove(event: PointerEvent) {
  const session = drag.value;
  if (!session || event.pointerId !== session.pointerId) return;

  session.pointerX = event.clientX;
  session.pointerY = event.clientY;

  if (!session.started) {
    const dist = Math.hypot(
      event.clientX - session.startX,
      event.clientY - session.startY,
    );
    if (dist < ACTIVATE_PX) return;
    session.started = true;
    isSorting.value = true;
    document.body.style.userSelect = 'none';
    document.body.style.cursor = 'grabbing';
  }

  event.preventDefault();
  void moveActiveTo(hitTestIndex(event.clientX, event.clientY));
}

function onWindowPointerUp(event: PointerEvent) {
  const session = drag.value;
  if (!session || event.pointerId !== session.pointerId) return;
  if (session.started) {
    commitDrag();
  } else {
    resetDrag();
  }
}

function onHandlePointerDown(index: number, url: string, event: PointerEvent) {
  if (!isInteractive.value || urls.value.length < 2) return;
  if (event.button !== 0) return;
  event.preventDefault();
  event.stopPropagation();

  const el = itemEls.get(url);
  const rect = el?.getBoundingClientRect();
  if (!rect) return;

  const list = urls.value;
  const mainRect =
    index === 0
      ? rect
      : itemEls.get(list[0] ?? '')?.getBoundingClientRect() ?? null;
  const thumbSourceUrl = list.find((item, i) => i !== 0 && item !== url) ?? list.find((_, i) => i !== 0);
  const thumbRect =
    index === 0
      ? thumbSourceUrl
        ? itemEls.get(thumbSourceUrl)?.getBoundingClientRect()
        : null
      : rect;

  const thumbWidth = thumbRect?.width ?? Math.max((mainRect?.width ?? rect.width) / 2 - 6, 48);
  const thumbHeight = thumbRect?.height ?? thumbWidth;
  const mainWidth = mainRect?.width ?? thumbWidth * 2 + 12;
  const mainHeight = mainRect?.height ?? mainWidth;

  drag.value = {
    activeUrl: url,
    grabRatioX: (event.clientX - rect.left) / Math.max(rect.width, 1),
    grabRatioY: (event.clientY - rect.top) / Math.max(rect.height, 1),
    mainHeight,
    mainWidth,
    overIndex: index,
    pointerId: event.pointerId,
    pointerX: event.clientX,
    pointerY: event.clientY,
    startX: event.clientX,
    startY: event.clientY,
    started: false,
    thumbHeight,
    thumbWidth,
  };
  draftUrls.value = [...urls.value];
  bindDragListeners();
  void nextTick(() => {
    captureLayoutRects();
  });
}

function onPreview(url: string) {
  if (isSorting.value || drag.value?.started) return;
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

onBeforeUnmount(() => {
  resetDrag();
  itemEls.clear();
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
      v-if="displayUrls.length > 0"
      class="product-media-grid mt-4"
      :class="{
        'product-media-grid--selecting': selectedCount > 0,
        'product-media-grid--sorting': drag?.started,
      }"
    >
      <div
        v-for="(url, index) in displayUrls"
        :key="url"
        :ref="(el) => setItemRef(url, el as Element | null)"
        class="product-media-grid__item group relative"
        :class="{
          'product-media-grid__item--main': index === 0,
          'product-media-grid__item--selected': isUrlSelected(url),
          'product-media-grid__item--placeholder':
            drag?.started && drag.activeUrl === url,
        }"
      >
        <button
          class="product-media-grid__card bg-background relative flex size-full items-center justify-center overflow-hidden rounded-lg border border-border"
          type="button"
          @click="onPreview(url)"
        >
          <template v-if="!(drag?.started && drag.activeUrl === url)">
            <img
              v-if="isImageUrl(url)"
              class="size-full object-contain"
              :alt="`媒体 ${index + 1}`"
              :src="url"
              draggable="false"
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
            <span class="product-media-grid__overlay" aria-hidden="true"></span>
          </template>
        </button>

        <template v-if="isInteractive && !(drag?.started && drag.activeUrl === url)">
          <button
            class="product-media-grid__check"
            type="button"
            title="选择"
            @click="toggleSelectByUrl(url, $event)"
          >
            <span
              class="product-media-grid__checkbox"
              :class="{
                'product-media-grid__checkbox--checked': isUrlSelected(url),
              }"
            >
              <IconifyIcon
                v-if="isUrlSelected(url)"
                class="size-3 text-white"
                icon="lucide:check"
              />
            </span>
          </button>
          <span
            class="product-media-grid__handle"
            title="拖拽排序"
            @pointerdown="onHandlePointerDown(index, url, $event)"
          >
            <IconifyIcon class="size-3.5" icon="lucide:grip" />
          </span>
        </template>
      </div>

      <button
        v-if="isInteractive && canAddMore"
        class="product-media-grid__item product-media-grid__add text-muted-foreground hover:border-primary hover:text-primary flex items-center justify-center rounded-lg border border-dashed border-border transition-colors disabled:cursor-not-allowed disabled:opacity-50"
        type="button"
        :disabled="uploading || !!drag?.started"
        @click="onSelectClick"
      >
        <IconifyIcon class="size-5" icon="lucide:plus" />
      </button>
    </div>

    <!-- 拖拽浮层：跟随指针，不参与文档流重排 -->
    <Teleport to="body">
      <div
        v-if="drag?.started && overlayStyle"
        class="product-media-grid__drag-overlay"
        :style="overlayStyle"
      >
        <div
          class="bg-background flex size-full items-center justify-center overflow-hidden rounded-lg border border-border shadow-lg"
        >
          <img
            v-if="isImageUrl(drag.activeUrl)"
            class="size-full object-contain"
            alt=""
            :src="drag.activeUrl"
            draggable="false"
          />
          <div
            v-else-if="isVideoUrl(drag.activeUrl)"
            class="text-muted-foreground flex flex-col items-center gap-1 text-xs"
          >
            <IconifyIcon class="size-8" icon="lucide:video" />
            <span>视频</span>
          </div>
          <div
            v-else
            class="text-muted-foreground flex flex-col items-center gap-1 text-xs"
          >
            <IconifyIcon class="size-8" icon="lucide:file" />
            <span>文件</span>
          </div>
        </div>
      </div>
    </Teleport>

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

.product-media-grid--sorting {
  cursor: grabbing;
}

.product-media-grid__item {
  width: var(--media-thumb);
  height: var(--media-thumb);
  min-width: 0;
  will-change: transform;
}

.product-media-grid__item--main {
  width: calc(var(--media-thumb) * 2 + 0.75rem);
  height: calc(var(--media-thumb) * 2 + 0.75rem);
  grid-column: span 2;
  grid-row: span 2;
}

.product-media-grid__item--placeholder > .product-media-grid__card {
  border-style: dashed;
  background: hsl(var(--muted) / 0.35);
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

.product-media-grid--sorting .product-media-grid__overlay {
  opacity: 0 !important;
}

.product-media-grid__add {
  background: hsl(var(--muted) / 0.35);
}

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

.product-media-grid--sorting .product-media-grid__check {
  opacity: 0 !important;
  pointer-events: none;
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
  touch-action: none;
  transition: opacity 0.15s ease;
}

.product-media-grid__item:hover .product-media-grid__handle {
  opacity: 1;
}

.product-media-grid--sorting .product-media-grid__handle {
  opacity: 0 !important;
  pointer-events: none;
}

.product-media-grid__drag-overlay {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 10000;
  pointer-events: none;
  will-change: transform, width, height;
  transition:
    width 180ms cubic-bezier(0.2, 0, 0, 1),
    height 180ms cubic-bezier(0.2, 0, 0, 1);
}

@media (min-width: 640px) {
  .product-media-grid {
    --media-thumb: 6.5rem;
  }
}
</style>
