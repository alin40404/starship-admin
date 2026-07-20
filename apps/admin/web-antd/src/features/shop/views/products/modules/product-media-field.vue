<script lang="ts" setup>
import { computed, ref, useTemplateRef } from 'vue';

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
const fileInputRef = useTemplateRef<HTMLInputElement>('fileInputRef');
const previewUrl = ref('');
const previewVisible = ref(false);

const urls = computed(() =>
  (props.modelValue ?? []).map((url) => String(url).trim()).filter(Boolean),
);

const canAddMore = computed(
  () => !props.maxCount || urls.value.length < props.maxCount,
);

const isInteractive = computed(() => !props.disabled);

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

function onRemove(url: string) {
  emitUrls(urls.value.filter((item) => item !== url));
}

function onPreview(url: string) {
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

function isCoverImage(url: string, index: number) {
  if (!isImageUrl(url)) return false;
  const firstImageIndex = urls.value.findIndex((item) => isImageUrl(item));
  return firstImageIndex === index;
}
</script>

<template>
  <div class="w-full">
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
      class="product-media-grid mt-4"
    >
      <div
        v-for="(url, index) in urls"
        :key="`${url}-${index}`"
        class="product-media-grid__item relative"
        :class="{ 'product-media-grid__item--main': index === 0 }"
      >
        <button
          class="bg-background relative flex size-full items-center justify-center overflow-hidden rounded-lg border border-border p-1.5"
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
          <span
            v-if="isCoverImage(url, index)"
            class="bg-primary text-primary-foreground absolute left-2 top-2 rounded px-1.5 py-0.5 text-[10px] leading-none"
          >
            主图
          </span>
        </button>
        <button
          v-if="isInteractive"
          class="bg-background/90 text-foreground hover:bg-destructive hover:text-destructive-foreground absolute -right-1.5 -top-1.5 z-10 flex size-6 items-center justify-center rounded-full border border-border shadow-sm"
          type="button"
          @click="onRemove(url)"
        >
          <IconifyIcon class="size-3.5" icon="lucide:x" />
        </button>
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
.product-media-grid {
  /* 小图基准边长；主图 span 2×2 → 面积为小图的 4 倍（小图 = 主图 1/4） */
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

.product-media-grid__add {
  background: hsl(var(--muted) / 0.35);
}

@media (min-width: 640px) {
  .product-media-grid {
    --media-thumb: 6.5rem;
  }
}
</style>
