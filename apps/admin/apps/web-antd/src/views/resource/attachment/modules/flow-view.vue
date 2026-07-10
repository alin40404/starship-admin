<script lang="ts" setup>
import type { AttachmentRow } from '#/api/system/attachment';

import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

import { IconifyIcon } from '@vben/icons';

import { Button, Image, Tooltip } from 'ant-design-vue';

import {
  formatFileSize,
  getAttachmentFileIcon,
  getAttachmentFileIconClass,
  isImageAttachment,
} from '../data';

const props = withDefaults(
  defineProps<{
    canDelete?: boolean;
    hasMore?: boolean;
    loadingMore?: boolean;
    rows: AttachmentRow[];
    scrollRoot?: HTMLElement | null;
    selectedIds: number[];
    showActions?: boolean;
    showCopy?: boolean;
    singlePick?: boolean;
    total?: number;
  }>(),
  {
    canDelete: false,
    hasMore: false,
    loadingMore: false,
    showActions: true,
    showCopy: true,
    singlePick: false,
    total: 0,
  },
);

const emit = defineEmits<{
  copy: [row: AttachmentRow];
  delete: [row: AttachmentRow];
  'load-more': [];
  open: [row: AttachmentRow];
  pick: [row: AttachmentRow];
  'update:selectedIds': [ids: number[]];
}>();

const selectedIdSet = computed(() => new Set(props.selectedIds));
const previewUrl = ref('');
const previewVisible = ref(false);
const rootRef = ref<HTMLElement>();
const sentinelRef = ref<HTMLElement>();
const showLoadFooter = computed(
  () => props.rows.length > 0 && props.total > 0,
);

let intersectionObserver: IntersectionObserver | null = null;

function setupAutoLoadObserver() {
  intersectionObserver?.disconnect();
  intersectionObserver = null;

  const root = props.scrollRoot ?? rootRef.value;
  const sentinel = sentinelRef.value;
  if (!root || !sentinel) {
    return;
  }

  intersectionObserver = new IntersectionObserver(
    (entries) => {
      const entry = entries[0];
      if (
        entry?.isIntersecting &&
        props.hasMore &&
        !props.loadingMore &&
        props.rows.length > 0
      ) {
        emit('load-more');
      }
    },
    {
      root,
      rootMargin: '80px 0px',
      threshold: 0,
    },
  );
  intersectionObserver.observe(sentinel);
}

watch(
  () => [props.scrollRoot, props.hasMore, props.loadingMore, props.rows.length],
  () => {
    setupAutoLoadObserver();
  },
  { flush: 'post' },
);

onMounted(() => {
  setupAutoLoadObserver();
});

onBeforeUnmount(() => {
  intersectionObserver?.disconnect();
});

function toggleSelect(id: number, checked?: boolean) {
  const next = new Set(props.selectedIds);
  const shouldSelect = checked ?? !next.has(id);
  if (shouldSelect) {
    next.add(id);
  } else {
    next.delete(id);
  }
  emit('update:selectedIds', [...next]);
}

function isSelected(id: number) {
  return selectedIdSet.value.has(id);
}

function onCardClick(row: AttachmentRow) {
  if (props.singlePick) {
    emit('pick', row);
    return;
  }
  toggleSelect(row.id);
}

function onPreview(row: AttachmentRow) {
  if (!row.file_url) {
    return;
  }
  if (isImageAttachment(row)) {
    previewUrl.value = row.file_url;
    previewVisible.value = true;
    return;
  }
  emit('open', row);
}

function onPreviewVisibleChange(visible: boolean) {
  previewVisible.value = visible;
  if (!visible) {
    previewUrl.value = '';
  }
}
</script>

<template>
  <div ref="rootRef" class="attachment-flow-view h-full min-h-0">
    <div v-if="rows.length > 0" class="attachment-flow-grid">
    <article
      v-for="row in rows"
      :key="row.id"
      class="attachment-flow-item"
      :class="{ 'is-selected': isSelected(row.id) }"
      @click="onCardClick(row)"
    >
      <div class="attachment-flow-item__thumb">
        <Image
          v-if="isImageAttachment(row) && row.file_url"
          :alt="row.file_name"
          class="attachment-flow-item__image"
          :preview="false"
          :src="row.file_url"
        />
        <div v-else class="attachment-flow-item__file">
          <IconifyIcon
            class="size-14"
            :class="getAttachmentFileIconClass(row)"
            :icon="getAttachmentFileIcon(row)"
          />
          <span v-if="row.file_ext" class="attachment-flow-item__ext">
            {{ row.file_ext }}
          </span>
        </div>
      </div>

      <div
        v-if="isSelected(row.id)"
        class="attachment-flow-item__selected-badge"
        aria-hidden="true"
      >
        <IconifyIcon class="size-3.5" icon="lucide:check" />
      </div>

      <div v-if="showActions" class="attachment-flow-item__overlay">
        <div class="attachment-flow-item__actions">
          <Tooltip
            v-if="isImageAttachment(row) && row.file_url"
            title="预览"
          >
            <button
              class="attachment-flow-item__action"
              type="button"
              @click.stop="onPreview(row)"
            >
              <IconifyIcon class="size-3.5" icon="lucide:eye" />
            </button>
          </Tooltip>
          <Tooltip v-if="row.file_url" title="打开">
            <button
              class="attachment-flow-item__action"
              type="button"
              @click.stop="emit('open', row)"
            >
              <IconifyIcon class="size-3.5" icon="lucide:external-link" />
            </button>
          </Tooltip>
          <Tooltip v-if="showCopy && row.file_url" title="复制链接">
            <button
              class="attachment-flow-item__action"
              type="button"
              @click.stop="emit('copy', row)"
            >
              <IconifyIcon class="size-3.5" icon="lucide:link" />
            </button>
          </Tooltip>
          <Tooltip v-if="canDelete" title="删除">
            <button
              class="attachment-flow-item__action attachment-flow-item__action--danger"
              type="button"
              @click.stop="emit('delete', row)"
            >
              <IconifyIcon class="size-3.5" icon="lucide:trash-2" />
            </button>
          </Tooltip>
        </div>

        <Tooltip :title="`${row.file_name} · ${formatFileSize(row.file_size)}`">
          <div class="attachment-flow-item__name">{{ row.file_name }}</div>
        </Tooltip>
      </div>
      <div
        v-else
        class="attachment-flow-item__overlay attachment-flow-item__overlay--picker"
      >
        <Tooltip :title="`${row.file_name} · ${formatFileSize(row.file_size)}`">
          <div class="attachment-flow-item__name">{{ row.file_name }}</div>
        </Tooltip>
      </div>
    </article>
    </div>

    <div
      v-if="showLoadFooter"
      class="attachment-flow-loadmore"
    >
      <span class="attachment-flow-loadmore__meta">
        已加载 {{ rows.length }} / 共 {{ total }} 条
      </span>
      <Button
        v-if="hasMore"
        :loading="loadingMore"
        size="small"
        type="default"
        @click="emit('load-more')"
      >
        加载更多
      </Button>
      <span v-else class="attachment-flow-loadmore__done">已加载全部</span>
    </div>

    <div
      v-if="showLoadFooter && hasMore"
      ref="sentinelRef"
      class="attachment-flow-loadmore__sentinel"
      aria-hidden="true"
    />

    <div v-else-if="rows.length === 0" class="attachment-flow-empty">
      <IconifyIcon class="mx-auto size-16 text-muted-foreground/40" icon="lucide:inbox" />
      <div class="mt-2 text-sm text-muted-foreground">暂无附件</div>
    </div>

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
.attachment-flow-view {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 0;
  overflow: auto;
  border: 1px solid hsl(var(--border));
  border-radius: 6px;
  background-color: hsl(var(--card));
}

.attachment-flow-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(152px, 1fr));
  gap: 12px;
  padding: 12px;
}

.attachment-flow-item {
  position: relative;
  aspect-ratio: 1;
  overflow: hidden;
  cursor: pointer;
  border: 2px solid hsl(var(--border));
  border-radius: 2px;
  background:
    linear-gradient(45deg, hsl(var(--muted) / 0.55) 25%, transparent 25%),
    linear-gradient(-45deg, hsl(var(--muted) / 0.55) 25%, transparent 25%),
    linear-gradient(45deg, transparent 75%, hsl(var(--muted) / 0.55) 75%),
    linear-gradient(-45deg, transparent 75%, hsl(var(--muted) / 0.55) 75%);
  background-color: hsl(var(--background));
  background-size: 12px 12px;
  background-position:
    0 0,
    0 6px,
    6px -6px,
    -6px 0;
  transition:
    border-color 0.15s ease,
    box-shadow 0.15s ease;
}

.attachment-flow-item:hover,
.attachment-flow-item.is-selected {
  border-color: hsl(var(--primary));
  box-shadow: inset 0 0 0 1px hsl(var(--primary) / 0.15);
}

.attachment-flow-item.is-selected {
  border-color: hsl(var(--primary));
  box-shadow:
    inset 0 0 0 1px hsl(var(--primary) / 0.35),
    0 0 0 1px hsl(var(--primary) / 0.12);
}

.attachment-flow-item__selected-badge {
  position: absolute;
  right: 6px;
  bottom: 6px;
  z-index: 4;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  color: #fff;
  background: hsl(var(--primary));
  border-radius: 50%;
  box-shadow: 0 1px 4px rgb(0 0 0 / 22%);
}

.attachment-flow-item.is-selected .attachment-flow-item__name {
  padding-right: 28px;
}

.attachment-flow-item__thumb {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}

.attachment-flow-item__image :deep(img) {
  display: block;
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.attachment-flow-item__file {
  display: flex;
  flex-direction: column;
  gap: 6px;
  align-items: center;
  justify-content: center;
}

.attachment-flow-item__ext {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: hsl(var(--muted-foreground));
}

.attachment-flow-item__overlay {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 6px;
  pointer-events: none;
  opacity: 0;
  background: linear-gradient(
    to bottom,
    rgb(0 0 0 / 28%) 0%,
    transparent 36%,
    transparent 58%,
    rgb(0 0 0 / 52%) 100%
  );
  transition: opacity 0.15s ease;
}

.attachment-flow-item:hover .attachment-flow-item__overlay,
.attachment-flow-item.is-selected .attachment-flow-item__overlay {
  opacity: 1;
}

.attachment-flow-item__overlay--picker {
  justify-content: flex-end;
  opacity: 0;
  background: linear-gradient(
    to top,
    rgb(0 0 0 / 52%) 0%,
    transparent 72%
  );
}

.attachment-flow-item:hover .attachment-flow-item__overlay--picker {
  opacity: 1;
}

.attachment-flow-item__actions,
.attachment-flow-item__action {
  pointer-events: auto;
}

.attachment-flow-item__actions {
  display: flex;
  gap: 4px;
  align-self: flex-start;
  margin-right: auto;
}

.attachment-flow-item__action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  color: #fff;
  cursor: pointer;
  background: rgb(0 0 0 / 45%);
  border: none;
  border-radius: 2px;
  transition: background-color 0.15s ease;
}

.attachment-flow-item__action:hover {
  background: rgb(0 0 0 / 68%);
}

.attachment-flow-item__action--danger:hover {
  background: rgb(220 38 38 / 85%);
}

.attachment-flow-item__name {
  overflow: hidden;
  font-size: 12px;
  line-height: 1.35;
  color: #fff;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.attachment-flow-empty {
  display: flex;
  flex: 1;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 240px;
  padding: 24px 8px;
}

.attachment-flow-loadmore {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 8px 12px;
  padding: 12px 12px 4px;
}

.attachment-flow-loadmore__meta {
  font-size: 12px;
  color: hsl(var(--muted-foreground));
}

.attachment-flow-loadmore__done {
  font-size: 12px;
  color: hsl(var(--muted-foreground));
}

.attachment-flow-loadmore__sentinel {
  width: 100%;
  height: 1px;
  flex-shrink: 0;
}
</style>
