<script lang="ts" setup>
import type { NotificationItem } from '@vben/layouts';

import { computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';

import { storeToRefs } from 'pinia';

import { AuthenticationLoginExpiredModal } from '@vben/common-ui';
import { VBEN_DOC_URL, VBEN_GITHUB_URL } from '@vben/constants';
import { useWatermark } from '@vben/hooks';
import { BookOpenText, CircleHelp, SvgGithubIcon } from '@vben/icons';
import {
  BasicLayout,
  LockScreen,
  Notification,
  UserDropdown,
} from '@vben/layouts';
import { useAccess } from '@vben/access';
import { preferences } from '@vben/preferences';
import { useAccessStore, useUserStore } from '@vben/stores';
import { formatRelativeTime, openWindow } from '@vben/utils';

import type { InboxMessageRow } from '#/api/inbox';
import {
  clearReadInboxMessages,
  deleteInboxMessage,
  markAllInboxMessagesRead,
  markInboxMessageRead,
} from '#/api/inbox';
import {
  getInboxCategoryMeta,
  INBOX_MENU_PERMISSION,
  resolveInboxCategory,
} from '#/constants/inbox-permissions';
import { $t } from '#/locales';
import { useAuthStore, useInboxStore } from '#/store';
import LoginForm from '#/views/_core/authentication/login.vue';

const inboxStore = useInboxStore();
const { recentMessages, unreadCount } = storeToRefs(inboxStore);

const router = useRouter();
const userStore = useUserStore();
const authStore = useAuthStore();
const accessStore = useAccessStore();
const { hasAccessByCodes } = useAccess();
const canUseInbox = computed(() => hasAccessByCodes([INBOX_MENU_PERMISSION]));
const { destroyWatermark, updateWatermark } = useWatermark();
const showDot = computed(() => unreadCount.value > 0);

const notifications = computed(() =>
  recentMessages.value.map(mapInboxToNotification),
);

const relativeLocale = computed(() =>
  preferences.app.locale.replace('_', '-').toLowerCase(),
);

function resolveSenderName(row: InboxMessageRow) {
  if (row.sender?.real_name || row.sender?.username) {
    return row.sender.real_name || row.sender.username;
  }
  const category = resolveInboxCategory(row.category, row.type);
  if (category === 'alert' || category === 'notice') {
    return $t('inbox.sender.system');
  }
  return undefined;
}

function mapInboxToNotification(row: InboxMessageRow): NotificationItem {
  const category = resolveInboxCategory(row.category, row.type);
  const meta = getInboxCategoryMeta(category);

  return {
    id: row.id,
    avatar: row.sender?.avatar || preferences.app.defaultAvatar,
    category,
    date: formatRelativeTime(row.created_at, relativeLocale.value),
    icon: meta.icon,
    isRead: row.is_read === 1,
    link: row.link || '/dashboard/inbox',
    message: row.content || row.title,
    priority: row.priority,
    senderName: resolveSenderName(row),
    title: row.title,
  };
}

async function loadInboxNotifications() {
  if (!accessStore.accessToken || !canUseInbox.value) {
    inboxStore.reset();
    return;
  }
  await inboxStore.refresh();
}

onMounted(() => {
  loadInboxNotifications();
});

watch(
  () => [accessStore.accessToken, canUseInbox.value] as const,
  () => {
    loadInboxNotifications();
  },
);

const menus = computed(() => [
  {
    handler: () => {
      router.push('/profile');
    },
    icon: 'lucide:user',
    text: $t('page.auth.profile'),
  },
  {
    handler: () => {
      openWindow(VBEN_DOC_URL, {
        target: '_blank',
      });
    },
    icon: BookOpenText,
    text: $t('ui.widgets.document'),
  },
  {
    handler: () => {
      openWindow(VBEN_GITHUB_URL, {
        target: '_blank',
      });
    },
    icon: SvgGithubIcon,
    text: 'GitHub',
  },
  {
    handler: () => {
      openWindow(`${VBEN_GITHUB_URL}/issues`, {
        target: '_blank',
      });
    },
    icon: CircleHelp,
    text: $t('ui.widgets.qa'),
  },
]);

const avatar = computed(() => {
  return userStore.userInfo?.avatar ?? preferences.app.defaultAvatar;
});

const userEmail = computed(() => userStore.userInfo?.desc || '');
const userTagText = computed(() => {
  const roles = userStore.userInfo?.roles ?? [];
  if (roles.includes('super_admin')) {
    return 'Super';
  }
  if (!userEmail.value) {
    return 'Incomplete';
  }
  if (roles.length > 0) {
    return 'Admin';
  }
  return undefined;
});

async function handleLogout() {
  await authStore.logout(false);
}

async function handleNoticeClear() {
  try {
    await clearReadInboxMessages();
    await loadInboxNotifications();
  } catch {
    // ignore
  }
}

async function markRead(id: number | string) {
  const item = notifications.value.find((entry) => entry.id === id);
  if (!item || item.isRead) {
    return;
  }
  try {
    await markInboxMessageRead(Number(id));
    await inboxStore.refresh();
  } catch {
    // ignore
  }
}

async function remove(id: number | string) {
  try {
    await deleteInboxMessage(Number(id));
    await inboxStore.refresh();
  } catch {
    // ignore
  }
}

async function handleMakeAll() {
  try {
    await markAllInboxMessagesRead();
    await inboxStore.refresh();
  } catch {
    // ignore
  }
}

function handleViewAllNotifications() {
  router.push('/dashboard/inbox');
}
watch(
  () => ({
    enable: preferences.app.watermark,
    content: preferences.app.watermarkContent,
  }),
  async ({ enable, content }) => {
    if (enable) {
      await updateWatermark({
        content:
          content ||
          `${userStore.userInfo?.username} - ${userStore.userInfo?.realName}`,
      });
    } else {
      destroyWatermark();
    }
  },
  {
    immediate: true,
  },
);
</script>

<template>
  <BasicLayout @clear-preferences-and-logout="handleLogout">
    <template #user-dropdown>
      <UserDropdown
        :avatar
        :menus
        :text="userStore.userInfo?.realName"
        :description="userEmail"
        :tag-text="userTagText"
        @logout="handleLogout"
      />
    </template>
    <template v-if="canUseInbox" #notification>
      <Notification
        :count="unreadCount"
        :dot="showDot"
        :notifications="notifications"
        @clear="handleNoticeClear"
        @read="(item) => item.id && markRead(item.id)"
        @remove="(item) => item.id && remove(item.id)"
        @make-all="handleMakeAll"
        @view-all="handleViewAllNotifications"
      />
    </template>
    <template #extra>
      <AuthenticationLoginExpiredModal
        v-model:open="accessStore.loginExpired"
        :avatar
      >
        <LoginForm />
      </AuthenticationLoginExpiredModal>
    </template>
    <template #lock-screen>
      <LockScreen :avatar @to-login="handleLogout" />
    </template>
  </BasicLayout>
</template>
