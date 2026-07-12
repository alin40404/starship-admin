<script lang="ts" setup>
import type { NotificationCategoryTab, NotificationItem } from './types';

import { computed } from 'vue';
import { useRouter } from 'vue-router';

import { Bell, CircleX, MailCheck } from '@vben/icons';
import { $t } from '@vben/locales';

import {
  VbenButton,
  VbenIconButton,
  VbenPopover,
  VbenTooltip,
} from '@vben-core/shadcn-ui';

import { Icon as IconifyIcon } from '@iconify/vue';
import { useToggle } from '@vueuse/core';

interface Props {
  /**
   * 当前分类
   */
  activeCategory?: string;
  /**
   * 未读数量，优先于圆点展示
   */
  count?: number;
  /**
   * 分类筛选标签
   */
  categoryTabs?: NotificationCategoryTab[];
  /**
   * 显示圆点
   */
  dot?: boolean;
  /**
   * 是否还有更多消息可加载
   */
  hasMore?: boolean;
  /**
   * 消息列表
   */
  notifications?: NotificationItem[];
  /**
   * 是否正在加载更多
   */
  loadingMore?: boolean;
}

defineOptions({ name: 'NotificationPopup' });

const props = withDefaults(defineProps<Props>(), {
  activeCategory: '',
  count: 0,
  categoryTabs: () => [],
  dot: false,
  hasMore: false,
  notifications: () => [],
  loadingMore: false,
});

const emit = defineEmits<{
  categoryChange: [string];
  clear: [];
  loadMore: [];
  makeAll: [];
  read: [NotificationItem];
  remove: [NotificationItem];
  view: [NotificationItem];
  viewAll: [];
}>();

const router = useRouter();
const [open, toggle] = useToggle();
let suppressOutsideDismissUntil = 0;

function isDrawerInteraction(target: EventTarget | null) {
  return (
    target instanceof Element &&
    Boolean(target.closest('.ant-drawer-root, .ant-drawer-mask, .ant-drawer'))
  );
}

function isDrawerOpen() {
  return Boolean(document.querySelector('.ant-drawer-open'));
}

function shouldSuppressDismiss(target: EventTarget | null) {
  return (
    Date.now() < suppressOutsideDismissUntil ||
    isDrawerOpen() ||
    isDrawerInteraction(target)
  );
}

const popoverContentProps = {
  onFocusOutside(event: { detail: { originalEvent: FocusEvent }; preventDefault: () => void }) {
    if (shouldSuppressDismiss(event.detail.originalEvent.target)) {
      event.preventDefault();
    }
  },
  onPointerDownOutside(event: {
    detail: { originalEvent: PointerEvent };
    preventDefault: () => void;
  }) {
    if (shouldSuppressDismiss(event.detail.originalEvent.target)) {
      event.preventDefault();
    }
  },
};

function handleView(item: NotificationItem) {
  emit('view', item);
  suppressOutsideDismissUntil = Date.now() + 500;
  open.value = true;
  requestAnimationFrame(() => {
    open.value = true;
  });
}

const showBadge = computed(() => props.count > 0 || props.dot);

const displayCount = computed(() => {
  if (props.count <= 0) {
    return '';
  }
  return props.count > 99 ? '99+' : String(props.count);
});

const badgeClass = computed(() => {
  if (props.count <= 0) {
    return 'notification-badge notification-badge--dot';
  }
  if (props.count > 99) {
    return 'notification-badge notification-badge--overflow';
  }
  if (props.count > 9) {
    return 'notification-badge notification-badge--wide';
  }
  return 'notification-badge';
});

function close() {
  open.value = false;
}

function handleViewAll() {
  emit('viewAll');
  close();
}

function handleMakeAll() {
  emit('makeAll');
}

function handleClear() {
  emit('clear');
}

function handleClick(item: NotificationItem) {
  if (item.link) {
    navigateTo(item.link, item.query, item.state);
  }
}

function navigateTo(
  link: string,
  query?: Record<string, any>,
  state?: Record<string, any>,
) {
  if (link.startsWith('http://') || link.startsWith('https://')) {
    window.open(link, '_blank');
  } else {
    router.push({
      path: link,
      query: query || {},
      state,
    });
  }
}

function handleListScroll(event: Event) {
  const target = event.target as HTMLElement;
  const scrollTop = target.scrollTop;
  const clientHeight = target.clientHeight;
  const scrollHeight = target.scrollHeight;
  const atBottom =
    Math.abs(scrollTop) + clientHeight >= scrollHeight - 1;

  if (atBottom && props.hasMore && !props.loadingMore) {
    emit('loadMore');
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
  <VbenPopover
    v-model:open="open"
    :content-props="popoverContentProps"
    content-class="relative right-2 w-90 p-0"
    :modal="false"
  >
    <template #trigger>
      <div class="mr-2 flex-center h-full" @click.stop="toggle()">
        <VbenIconButton class="bell-button relative text-foreground">
          <span v-if="showBadge" :class="badgeClass">
            <template v-if="count > 0">{{ displayCount }}</template>
          </span>
          <Bell class="size-4" />
        </VbenIconButton>
      </div>
    </template>

    <div class="relative">
      <div class="flex items-center justify-between p-4 py-3">
        <div class="text-foreground">
          {{ $t('ui.widgets.notifications') }}
          <span
            v-if="count > 0"
            class="ml-1 text-xs text-muted-foreground tabular-nums"
          >
            ({{ displayCount }})
          </span>
        </div>
        <VbenIconButton
          :disabled="notifications.length <= 0"
          :tooltip="$t('ui.widgets.markAllAsRead')"
          @click="handleMakeAll"
        >
          <MailCheck class="size-4" />
        </VbenIconButton>
      </div>
      <div
        v-if="categoryTabs.length > 0"
        class="notification-category-tabs notification-scrollbar border-b border-border px-3 pb-2"
        @wheel="handleCategoryWheel"
      >
        <button
          v-for="tab in categoryTabs"
          :key="tab.value"
          type="button"
          class="notification-category-tab"
          :class="{ 'notification-category-tab--active': activeCategory === tab.value }"
          @click.stop="emit('categoryChange', tab.value)"
        >
          <IconifyIcon
            v-if="tab.icon"
            class="size-3.5 shrink-0"
            :icon="tab.icon"
          />
          <span class="max-w-16 truncate">{{ tab.label }}</span>
          <span
            v-if="tab.badge"
            class="notification-category-tab__badge"
          >
            {{ tab.badge }}
          </span>
        </button>
      </div>
      <div
        v-if="notifications.length > 0"
        class="notification-list-scroll notification-scrollbar max-h-90 overflow-y-auto"
        @scroll="handleListScroll"
      >
        <ul class="flex! w-full flex-col">
          <template v-for="item in notifications" :key="item.id ?? item.title">
            <li
              class="relative flex w-full cursor-pointer items-start gap-3 border-t border-border p-3 hover:bg-accent"
              :class="{
                'border-l-2 border-l-destructive pl-2.5': item.priority === 'urgent',
              }"
              @click="handleClick(item)"
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
              <div class="min-w-0 flex-1 flex-col gap-1 pr-12 leading-none">
                <p
                  class="cursor-pointer truncate font-semibold hover:text-primary hover:underline"
                  @click.stop="handleView(item)"
                >
                  {{ item.title }}
                </p>
                <p class="line-clamp-2 text-xs text-muted-foreground">
                  {{ item.message }}
                </p>
                <div
                  class="flex items-center gap-2 text-xs text-muted-foreground"
                >
                  <span class="w-24 shrink-0 truncate">
                    {{ item.senderName || '' }}
                  </span>
                  <span class="ml-auto shrink-0 whitespace-nowrap tabular-nums">
                    {{ item.date }}
                  </span>
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
                      @click.stop="handleView(item)"
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

      <template v-else>
        <div class="flex-center min-h-37.5 w-full text-muted-foreground">
          {{ $t('common.noData') }}
        </div>
      </template>

      <div
        class="flex items-center justify-between border-t border-border px-4 py-3"
      >
        <VbenButton
          :disabled="notifications.length <= 0"
          size="sm"
          variant="ghost"
          @click="handleClear"
        >
          {{ $t('ui.widgets.clearNotifications') }}
        </VbenButton>
        <VbenButton size="sm" @click="handleViewAll">
          {{ $t('ui.widgets.viewAll') }}
        </VbenButton>
      </div>
    </div>
  </VbenPopover>
</template>

<style scoped>
.notification-badge {
  position: absolute;
  top: -2px;
  right: -2px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  min-width: 16px;
  height: 16px;
  padding: 0;
  border: 1px solid hsl(var(--background));
  border-radius: 9999px;
  background: hsl(var(--destructive));
  color: #fff;
  font-size: 10px;
  font-weight: 500;
  line-height: 1;
  white-space: nowrap;
  font-variant-numeric: tabular-nums;
  box-sizing: border-box;
}

.notification-badge--dot {
  top: 2px;
  right: 2px;
  width: 8px;
  min-width: 8px;
  height: 8px;
}

.notification-badge--wide {
  width: auto;
  min-width: 20px;
  right: -7px;
  padding: 0 5px;
}

.notification-badge--overflow {
  width: auto;
  min-width: 26px;
  right: -10px;
  padding: 0 4px;
  font-size: 9px;
  letter-spacing: -0.02em;
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
  padding: 4px 10px;
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

:deep(.bell-button) {
  &:hover {
    svg {
      animation: bell-ring 1s both;
    }
  }
}

@keyframes bell-ring {
  0%,
  100% {
    transform-origin: top;
  }

  15% {
    transform: rotateZ(10deg);
  }

  30% {
    transform: rotateZ(-10deg);
  }

  45% {
    transform: rotateZ(5deg);
  }

  60% {
    transform: rotateZ(-5deg);
  }

  75% {
    transform: rotateZ(2deg);
  }
}
</style>
