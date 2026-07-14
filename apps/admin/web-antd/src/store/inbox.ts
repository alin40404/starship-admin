import type { InboxCategoryStats, InboxMessageRow } from '#/api/inbox';

import { ref } from 'vue';

import { defineStore } from 'pinia';

import {
  fetchInboxCategoryStats,
  fetchInboxMessagePage,
  fetchInboxSettings,
  fetchInboxUnreadCount,
} from '#/api/inbox';
import type { InboxCategory } from '#/constants/inbox-permissions';
import { INBOX_ALL_CATEGORY_KEY } from '#/constants/inbox-permissions';
import { normalizeInboxCategoryFilter } from '#/constants/inbox-permissions';

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
  const activeCategory = ref(INBOX_ALL_CATEGORY_KEY);
  const visibleCategories = ref<InboxCategory[]>([]);

  function resolveCategoryParam() {
    return activeCategory.value === INBOX_ALL_CATEGORY_KEY
      ? undefined
      : activeCategory.value;
  }

  function reset() {
    unreadCount.value = 0;
    categoryStats.value = createEmptyCategoryStats();
    recentMessages.value = [];
    loading.value = false;
    loadingMore.value = false;
    hasMore.value = true;
    page.value = 1;
    activeCategory.value = INBOX_ALL_CATEGORY_KEY;
    visibleCategories.value = [];
  }

  function $reset() {
    reset();
  }

  async function refresh() {
    loading.value = true;
    try {
      const [countResult, stats, settings, pageResult] = await Promise.all([
        fetchInboxUnreadCount(),
        fetchInboxCategoryStats(),
        fetchInboxSettings(),
        fetchInboxMessagePage({
          page: 1,
          per_page: NOTIFICATION_PAGE_SIZE,
          category: resolveCategoryParam(),
        }),
      ]);
      unreadCount.value = countResult.count;
      categoryStats.value = stats;
      visibleCategories.value = settings.visible_categories;
      activeCategory.value = normalizeInboxCategoryFilter(
        activeCategory.value,
        visibleCategories.value,
      );
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
        category: resolveCategoryParam(),
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

  async function setActiveCategory(category: string) {
    const nextCategory = normalizeInboxCategoryFilter(
      category,
      visibleCategories.value,
    );
    if (activeCategory.value === nextCategory) {
      return;
    }
    activeCategory.value = nextCategory;
    await refresh();
  }

  return {
    $reset,
    activeCategory,
    categoryStats,
    hasMore,
    loading,
    loadingMore,
    loadMore,
    recentMessages,
    refresh,
    reset,
    setActiveCategory,
    unreadCount,
    visibleCategories,
  };
});
