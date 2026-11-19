<script lang="ts" setup>
import type { NotificationCategoryTab, NotificationItem } from './types';

import { CircleX, MailCheck, X } from '@vben/icons';
import { $t } from '@vben/locales';

import {
  VbenButton,
  VbenIconButton,
  VbenTooltip,
} from '@vben-core/shadcn-ui';

import { Icon as IconifyIcon } from '@iconify/vue';

const props = withDefaults(
  defineProps<{
    activeCategory?: string;
    categoryTabs?: NotificationCategoryTab[];
    count?: number;
    displayCount?: string;
    fullscreen?: boolean;
    hasMore?: boolean;
    loadingMore?: boolean;
    notifications?: NotificationItem[];
    showClose?: boolean;
  }>(),
  {
    activeCategory: '',
    categoryTabs: () => [],
    count: 0,
    displayCount: '',
    fullscreen: false,
    hasMore: false,
    loadingMore: false,
    notifications: () => [],
    showClose: false,
  },
);

const emit = defineEmits<{
  categoryChange: [string];
  clear: [];
  close: [];
  loadMore: [];
  makeAll: [];
  remove: [NotificationItem];
  scrollNearBottom: [];
  view: [NotificationItem];
  viewAll: [];
  itemClick: [NotificationItem];
}>();

function handleListScroll(event: Event) {
  const target = event.target as HTMLElement;
  const atBottom =
    Math.abs(target.scrollTop) + target.clientHeight >=
    target.scrollHeight - 1;

  if (atBottom && props.hasMore && !props.loadingMore) {
    emit('scrollNearBottom');
  }
}

function handleCategoryWheel(event: WheelEvent) {
  const target = event.currentTarget as HTMLElement;
  if (target.scrollWidth <= target.clientWidth) {
    return;
  }
  target.scrollLeft += event.deltaY;
  event.preventDefault();
}

function categoryClass(category?: NotificationItem['category']) {
  switch (category) {
    case 'approval':
      return 'bg-orange-500/10 text-orange-600';
    case 'task':
      return 'bg-amber-500/10 text-amber-600';
    case 'crm':
      return 'bg-emerald-500/10 text-emerald-600';
    case 'alert':
      return 'bg-red-500/10 text-red-600';
    default:
      return 'bg-primary/10 text-primary';
  }
}

function showCategoryIcon(item: NotificationItem) {
  return Boolean(item.icon || item.category);
}
</script>

<template>
  <div
    class="relative flex flex-col bg-popover text-popover-foreground"
    :class="fullscreen ? 'h-full' : ''"
  >
    <div class="flex shrink-0 items-center gap-2 p-4 py-3">
      <VbenIconButton
        v-if="showClose"
        class="shrink-0"
        :tooltip="$t('common.close')"
        @click="emit('close')"
      >
        <X class="size-4" />
      </VbenIconButton>
      <div class="min-w-0 flex-1 text-foreground">
        {{ $t('ui.widgets.notifications') }}
        <span
          v-if="count > 0"
          class="ml-1 text-xs text-muted-foreground tabular-nums"
        >
          ({{ displayCount }})
        </span>
      </div>
      <VbenIconButton
        class="shrink-0"
        :disabled="notifications.length <= 0"
        :tooltip="$t('ui.widgets.markAllAsRead')"
        @click="emit('makeAll')"
      >
        <MailCheck class="size-4" />
      </VbenIconButton>
    </div>

    <div
      v-if="categoryTabs.length > 0"
      class="notification-category-tabs notification-scrollbar shrink-0 border-b border-border px-3 pb-2"
      @wheel="handleCategoryWheel"
    >
      <button
        v-for="tab in categoryTabs"
        :key="tab.value"
        type="button"
        class="notification-category-tab"
        :class="{
          'notification-category-tab--active': activeCategory === tab.value,
        }"
        @click.stop="emit('categoryChange', tab.value)"
      >
        <IconifyIcon
          v-if="tab.icon"
          class="size-3.5 shrink-0"
          :icon="tab.icon"
        />
        <span class="max-w-16 truncate">{{ tab.label }}</span>
        <span v-if="tab.badge" class="notification-category-tab__badge">
          {{ tab.badge }}
        </span>
      </button>
    </div>

    <div
      v-if="notifications.length > 0"
      class="notification-list-scroll notification-scrollbar min-h-0 flex-1 overflow-y-auto"
      :class="fullscreen ? '' : 'max-h-90'"
      @scroll="handleListScroll"
    >
      <ul class="flex! w-full flex-col">
        <template v-for="item in notifications" :key="item.id ?? item.title">
          <li
            class="relative flex w-full cursor-pointer items-start gap-3 border-t border-border p-3 hover:bg-accent"
            :class="{
              'border-l-2 border-l-destructive pl-2.5':
                item.priority === 'urgent',
            }"
            @click="emit('itemClick', item)"
          >
            <span
              v-if="!item.isRead"
              class="absolute top-2 right-2 size-2 rounded-full bg-primary"
            ></span>

            <span
              v-if="showCategoryIcon(item)"
              class="relative flex size-10 shrink-0 items-center justify-center rounded-full"
              :class="categoryClass(item.category)"
            >
              <IconifyIcon
                class="size-5"
                :icon="item.icon || 'lucide:bell'"
              />
            </span>
            <span
              v-else
              class="relative flex size-10 shrink-0 overflow-hidden rounded-full"
            >
              <img
                :src="item.avatar"
                class="aspect-square size-full object-cover"
              />
            </span>
            <div class="flex min-w-0 flex-1 flex-col gap-1 pr-12 leading-normal">
              <p
                class="notification-item-title cursor-pointer truncate hover:underline"
                :class="
                  item.isRead
                    ? 'font-normal text-muted-foreground hover:text-muted-foreground'
                    : 'font-semibold hover:text-primary'
                "
                @click.stop="emit('view', item)"
              >
                {{ item.title }}
              </p>
              <div class="flex flex-col gap-0.5">
                <p class="line-clamp-2 text-xs text-muted-foreground">
                  {{ item.message }}
                </p>
                <div
                  class="flex items-center gap-2 text-xs text-muted-foreground"
                >
                  <span class="shrink-0 whitespace-nowrap tabular-nums">
                    {{ item.date }}
                  </span>
                </div>
              </div>
            </div>
            <div
              class="absolute top-1/2 right-3 flex -translate-y-1/2 flex-col gap-2"
            >
              <VbenTooltip :delay-duration="200" side="bottom">
                <template #trigger>
                  <VbenButton
                    v-if="!item.isRead"
                    size="xs"
                    variant="ghost"
                    class="h-6 shrink-0 px-2 text-xs"
                    @click.stop="emit('view', item)"
                  >
                    {{ $t('common.view') }}
                  </VbenButton>
                </template>
                {{ $t('common.viewDetail') }}
              </VbenTooltip>
              <VbenIconButton
                v-if="item.isRead"
                size="xs"
                variant="ghost"
                class="h-6 px-2 text-destructive"
                :tooltip="$t('common.delete')"
                @click.stop="emit('remove', item)"
              >
                <CircleX class="size-4" />
              </VbenIconButton>
            </div>
          </li>
        </template>
        <li
          v-if="loadingMore"
          class="flex-center border-t border-border py-3 text-xs text-muted-foreground"
        >
          {{ $t('common.loading') }}
        </li>
      </ul>
    </div>

    <div
      v-else
      class="flex-center w-full flex-1 text-muted-foreground"
      :class="fullscreen ? '' : 'min-h-37.5'"
    >
      {{ $t('common.noData') }}
    </div>

    <div
      class="flex shrink-0 items-center justify-between border-t border-border px-4 py-3"
    >
      <VbenButton
        :disabled="notifications.length <= 0"
        size="sm"
        variant="ghost"
        @click="emit('clear')"
      >
        {{ $t('ui.widgets.clearNotifications') }}
      </VbenButton>
      <VbenButton size="sm" @click="emit('viewAll')">
        {{ $t('ui.widgets.viewAll') }}
      </VbenButton>
    </div>
  </div>
</template>

<style scoped>
/* 通知标题：跟随控件字号（base×13/16，主题 16 → 13px） */
.notification-item-title {
  font-size: var(
    --admin-font-control,
    var(--vben-button-font-size, calc(var(--font-size-base, 16px) * 13 / 16))
  );
}

.notification-category-tabs {
  display: flex;
  gap: 6px;
  overflow-x: auto;
  overscroll-behavior-x: contain;
  -webkit-overflow-scrolling: touch;
}

.notification-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: hsl(var(--border)) transparent;
}

.notification-scrollbar::-webkit-scrollbar {
  width: 3px;
  height: 3px;
}

.notification-scrollbar::-webkit-scrollbar-thumb {
  border-radius: 1.5px;
  background-color: hsl(var(--border));
}

.notification-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.notification-scrollbar::-webkit-scrollbar-button {
  display: none;
}

.notification-list-scroll {
  overscroll-behavior-y: contain;
  -webkit-overflow-scrolling: touch;
}

.notification-category-tab {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  gap: 4px;
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  background: transparent;
  padding: 6px 10px;
  font-size: 12px;
  line-height: 1;
  color: hsl(var(--muted-foreground));
  cursor: pointer;
  transition:
    color 0.2s,
    border-color 0.2s,
    background-color 0.2s;
}

.notification-category-tab:hover {
  color: hsl(var(--foreground));
  background-color: hsl(var(--accent));
}

.notification-category-tab--active {
  border-color: hsl(var(--primary));
  background-color: hsl(var(--primary) / 0.08);
  color: hsl(var(--primary));
}

.notification-category-tab__badge {
  min-width: 16px;
  border-radius: 9999px;
  background: hsl(var(--destructive));
  padding: 0 5px;
  font-size: 10px;
  line-height: 16px;
  color: #fff;
  text-align: center;
  font-variant-numeric: tabular-nums;
}
</style>
