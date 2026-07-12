import type { InboxCategoryStats, InboxMessageRow } from '#/api/inbox';

import { ref } from 'vue';

import { defineStore } from 'pinia';

import {
  fetchInboxCategoryStats,
  fetchInboxRecentMessages,
  fetchInboxUnreadCount,
} from '#/api/inbox';

function createEmptyCategoryStats(): InboxCategoryStats {
  return {
    alert: 0,
    all: 0,
    approval: 0,
    crm: 0,
    notice: 0,
    task: 0,
  };
}

export const useInboxStore = defineStore('inbox', () => {
  const unreadCount = ref(0);
  const categoryStats = ref<InboxCategoryStats>(createEmptyCategoryStats());
  const recentMessages = ref<InboxMessageRow[]>([]);
  const loading = ref(false);

  function reset() {
    unreadCount.value = 0;
    categoryStats.value = createEmptyCategoryStats();
    recentMessages.value = [];
  }

  async function refresh() {
    loading.value = true;
    try {
      const [countResult, stats, recent] = await Promise.all([
        fetchInboxUnreadCount(),
        fetchInboxCategoryStats(),
        fetchInboxRecentMessages(10),
      ]);
      unreadCount.value = countResult.count;
      categoryStats.value = stats;
      recentMessages.value = recent;
    } catch {
      reset();
    } finally {
      loading.value = false;
    }
  }

  return {
    categoryStats,
    loading,
    recentMessages,
    refresh,
    reset,
    unreadCount,
  };
});
