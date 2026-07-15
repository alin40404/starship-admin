<script lang="ts" setup>
import type { NotificationCategoryTab, NotificationItem } from './types';

import { computed, watch } from 'vue';
import { useRouter } from 'vue-router';

import { Bell } from '@vben/icons';
import { $t } from '@vben/locales';

import { useIsMobile } from '@vben-core/composables';
import {
  VbenIconButton,
  VbenPopover,
} from '@vben-core/shadcn-ui';

import { useScrollLock, useToggle } from '@vueuse/core';

import NotificationPanel from './notification-panel.vue';

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
const { isMobile } = useIsMobile();
const [open, toggle] = useToggle();
const bodyScrollLocked = useScrollLock(document.body);
let suppressOutsideDismissUntil = 0;

watch(
  () => isMobile.value && open.value,
  (locked) => {
    bodyScrollLocked.value = locked;
  },
  { immediate: true },
);

watch(isMobile, (mobile) => {
  if (mobile) {
    open.value = false;
  }
});

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
  onFocusOutside(event: {
    detail: { originalEvent: FocusEvent };
    preventDefault: () => void;
  }) {
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
} as Record<string, unknown>;

function handleView(item: NotificationItem) {
  emit('view', item);
  // 保持面板打开；详情抽屉叠在上层，关闭后面板仍在
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
  if (!item.link) {
    return;
  }
  if (isMobile.value) {
    close();
  }
  navigateTo(item.link, item.query, item.state);
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

const panelBindings = computed(() => ({
  activeCategory: props.activeCategory,
  categoryTabs: props.categoryTabs,
  count: props.count,
  displayCount: displayCount.value,
  hasMore: props.hasMore,
  loadingMore: props.loadingMore,
  notifications: props.notifications,
}));
</script>

<template>
  <!-- 移动端：右侧全屏抽屉 -->
  <template v-if="isMobile">
    <div class="mr-2 flex-center h-full" @click.stop="toggle()">
      <VbenIconButton class="bell-button relative text-foreground">
        <span v-if="showBadge" :class="badgeClass">
          <template v-if="count > 0">{{ displayCount }}</template>
        </span>
        <Bell class="size-4" />
      </VbenIconButton>
    </div>
    <Teleport to="body">
      <div
        class="notification-drawer-root z-popup"
        :class="{ 'is-open': open }"
        role="dialog"
        aria-modal="true"
        :aria-hidden="!open"
        :aria-label="$t('ui.widgets.notifications')"
      >
        <Transition name="notification-drawer-mask">
          <div
            v-if="open"
            class="notification-drawer-mask"
            @click="close"
          ></div>
        </Transition>
        <Transition name="notification-drawer-panel">
          <div
            v-if="open"
            class="notification-drawer-panel"
          >
            <NotificationPanel
              v-bind="panelBindings"
              fullscreen
              show-close
              @category-change="(value) => emit('categoryChange', value)"
              @clear="handleClear"
              @close="close"
              @item-click="handleClick"
              @make-all="handleMakeAll"
              @remove="(item) => emit('remove', item)"
              @scroll-near-bottom="emit('loadMore')"
              @view="handleView"
              @view-all="handleViewAll"
            />
          </div>
        </Transition>
      </div>
    </Teleport>
  </template>

  <!-- 桌面端：保留 Popover -->
  <VbenPopover
    v-else
    v-model:open="open"
    content-class="relative right-2 w-90 p-0"
    :content-props="popoverContentProps"
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

    <NotificationPanel
      v-bind="panelBindings"
      @category-change="(value) => emit('categoryChange', value)"
      @clear="handleClear"
      @item-click="handleClick"
      @make-all="handleMakeAll"
      @remove="(item) => emit('remove', item)"
      @scroll-near-bottom="emit('loadMore')"
      @view="handleView"
      @view-all="handleViewAll"
    />
  </VbenPopover>
</template>

<style scoped>
.notification-drawer-root {
  position: fixed;
  inset: 0;
  pointer-events: none;
}

.notification-drawer-root.is-open {
  pointer-events: auto;
}

.notification-drawer-mask {
  position: absolute;
  inset: 0;
  background: rgb(0 0 0 / 45%);
}

.notification-drawer-panel {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 100vw;
  overflow: hidden;
  background: hsl(var(--popover));
  box-shadow: -8px 0 24px rgb(0 0 0 / 12%);
}

.notification-drawer-panel > :deep(*) {
  flex: 1;
  min-height: 0;
  width: 100%;
}

.notification-drawer-mask-enter-active,
.notification-drawer-mask-leave-active {
  transition: opacity 0.25s ease;
}

.notification-drawer-mask-enter-from,
.notification-drawer-mask-leave-to {
  opacity: 0;
}

.notification-drawer-panel-enter-active,
.notification-drawer-panel-leave-active {
  transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1);
}

.notification-drawer-panel-enter-from,
.notification-drawer-panel-leave-to {
  transform: translateX(100%);
}

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
