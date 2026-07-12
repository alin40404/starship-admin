<script lang="ts" setup>
import type { NotificationItem } from './types';

import { computed } from 'vue';
import { useRouter } from 'vue-router';

import { Bell, CircleCheckBig, CircleX, MailCheck } from '@vben/icons';
import { $t } from '@vben/locales';

import {
  VbenButton,
  VbenIconButton,
  VbenPopover,
  VbenScrollbar,
} from '@vben-core/shadcn-ui';

import { Icon as IconifyIcon } from '@iconify/vue';
import { useToggle } from '@vueuse/core';

interface Props {
  /**
   * 未读数量，优先于圆点展示
   */
  count?: number;
  /**
   * 显示圆点
   */
  dot?: boolean;
  /**
   * 消息列表
   */
  notifications?: NotificationItem[];
}

defineOptions({ name: 'NotificationPopup' });

const props = withDefaults(defineProps<Props>(), {
  count: 0,
  dot: false,
  notifications: () => [],
});

const emit = defineEmits<{
  clear: [];
  makeAll: [];
  read: [NotificationItem];
  remove: [NotificationItem];
  viewAll: [];
}>();

const router = useRouter();
const [open, toggle] = useToggle();

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
  <VbenPopover v-model:open="open" content-class="relative right-2 w-90 p-0">
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
      <VbenScrollbar v-if="notifications.length > 0">
        <ul class="flex! max-h-90 w-full flex-col">
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
              <div class="min-w-0 flex-1 flex-col gap-1 leading-none">
                <p class="truncate font-semibold">{{ item.title }}</p>
                <p
                  v-if="item.senderName"
                  class="truncate text-xs text-muted-foreground"
                >
                  {{ item.senderName }}
                </p>
                <p class="my-1 line-clamp-2 text-xs text-muted-foreground">
                  {{ item.message }}
                </p>
                <p class="text-xs text-muted-foreground">
                  {{ item.date }}
                </p>
              </div>
              <div
                class="absolute top-1/2 right-3 flex -translate-y-1/2 flex-col gap-2"
              >
                <VbenIconButton
                  v-if="!item.isRead"
                  size="xs"
                  variant="ghost"
                  class="h-6 px-2"
                  :tooltip="$t('common.confirm')"
                  @click.stop="emit('read', item)"
                >
                  <CircleCheckBig class="size-4" />
                </VbenIconButton>
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
        </ul>
      </VbenScrollbar>

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
