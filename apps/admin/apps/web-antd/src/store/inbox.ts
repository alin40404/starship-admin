import type { InboxCategoryStats, InboxMessageRow } from '#/api/inbox';

import { ref } from 'vue';

import { defineStore } from 'pinia';

import {
  fetchInboxCategoryStats,
  fetchInboxMessagePage,
  fetchInboxUnreadCount,
} from '#/api/inbox';

const NOTIFICATION_PAGE_SIZE = 10;

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
  const loadingMore = ref(false);
  const hasMore = ref(true);
  const page = ref(1);

  function reset() {
    unreadCount.value = 0;
    categoryStats.value = createEmptyCategoryStats();
    recentMessages.value = [];
    hasMore.value = true;
    page.value = 1;
  }

  async function refresh() {
    loading.value = true;
    try {
      const [countResult, stats, pageResult] = await Promise.all([
        fetchInboxUnreadCount(),
        fetchInboxCategoryStats(),
        fetchInboxMessagePage({ page: 1, per_page: NOTIFICATION_PAGE_SIZE }),
      ]);
      unreadCount.value = countResult.count;
      categoryStats.value = stats;
      recentMessages.value = pageResult.data;
      hasMore.value = pageResult.current_page < pageResult.last_page;
      page.value = 1;
    } catch {
      reset();
    } finally {
      loading.value = false;
    }
  }

  async function loadMore() {
    if (loadingMore.value || !hasMore.value) {
      return;
    }

    loadingMore.value = true;
    try {
      const nextPage = page.value + 1;
      const pageResult = await fetchInboxMessagePage({
        page: nextPage,
        per_page: NOTIFICATION_PAGE_SIZE,
      });
      recentMessages.value = [...recentMessages.value, ...pageResult.data];
      hasMore.value = pageResult.current_page < pageResult.last_page;
      page.value = nextPage;
    } catch {
      // ignore
    } finally {
      loadingMore.value = false;
    }
  }

  return {
    categoryStats,
    hasMore,
    loading,
    loadingMore,
    loadMore,
    recentMessages,
    refresh,
    reset,
    unreadCount,
  };
});
