<script lang="ts" setup>
import type { NotificationItem } from '@vben/layouts';

import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';

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
import { formatDateTime, openWindow } from '@vben/utils';

import type { InboxMessageRow } from '#/api/inbox';
import {
  deleteInboxMessage,
  fetchInboxRecentMessages,
  markAllInboxMessagesRead,
  markInboxMessageRead,
} from '#/api/inbox';
import { INBOX_MENU_PERMISSION } from '#/constants/inbox-permissions';
import { $t } from '#/locales';
import { useAuthStore } from '#/store';
import LoginForm from '#/views/_core/authentication/login.vue';

const notifications = ref<NotificationItem[]>([]);

const router = useRouter();
const userStore = useUserStore();
const authStore = useAuthStore();
const accessStore = useAccessStore();
const { hasAccessByCodes } = useAccess();
const canUseInbox = computed(() => hasAccessByCodes([INBOX_MENU_PERMISSION]));
const { destroyWatermark, updateWatermark } = useWatermark();
const showDot = computed(() =>
  notifications.value.some((item) => !item.isRead),
);

function mapInboxToNotification(row: InboxMessageRow): NotificationItem {
  return {
    id: row.id,
    avatar: userStore.userInfo?.avatar || preferences.app.defaultAvatar,
    date: formatDateTime(row.created_at) ?? '',
    isRead: row.is_read === 1,
    message: row.content || row.title,
    title: row.title,
    link: row.link || '/dashboard/inbox',
  };
}

async function loadInboxNotifications() {
  if (!accessStore.accessToken || !canUseInbox.value) {
    notifications.value = [];
    return;
  }
  try {
    const rows = await fetchInboxRecentMessages(10);
    notifications.value = rows.map(mapInboxToNotification);
  } catch {
    notifications.value = [];
  }
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

function handleNoticeClear() {
  notifications.value = [];
}

async function markRead(id: number | string) {
  const item = notifications.value.find((entry) => entry.id === id);
  if (!item || item.isRead) {
    return;
  }
  try {
    await markInboxMessageRead(Number(id));
    item.isRead = true;
  } catch {
    // ignore
  }
}

async function remove(id: number | string) {
  try {
    await deleteInboxMessage(Number(id));
    notifications.value = notifications.value.filter((item) => item.id !== id);
  } catch {
    // ignore
  }
}

async function handleMakeAll() {
  try {
    await markAllInboxMessagesRead();
    notifications.value.forEach((item) => {
      item.isRead = true;
    });
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
