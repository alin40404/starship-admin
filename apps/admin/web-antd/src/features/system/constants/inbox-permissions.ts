export const INBOX_MENU_PERMISSION = 'dashboard.inbox';

export const INBOX_ALL_CATEGORY_KEY = '__all__';

export const INBOX_ALL_CATEGORY_OPTION = {
  icon: 'lucide:inbox',
  labelKey: 'system.shared.all',
  value: INBOX_ALL_CATEGORY_KEY,
} as const;

export const INBOX_MESSAGE_TYPE_OPTIONS = [
  { color: 'processing', labelKey: 'inbox.type.system', value: 'system' },
  { color: 'warning', labelKey: 'inbox.type.todo', value: 'todo' },
  { color: 'success', labelKey: 'inbox.type.account', value: 'account' },
] as const;

export const INBOX_CATEGORY_OPTIONS = [
  { color: 'processing', icon: 'lucide:bell', labelKey: 'inbox.category.notice', value: 'notice' },
  { color: 'warning', icon: 'lucide:clipboard-check', labelKey: 'inbox.category.approval', value: 'approval' },
  { color: 'gold', icon: 'lucide:list-todo', labelKey: 'inbox.category.task', value: 'task' },
  { color: 'success', icon: 'lucide:users', labelKey: 'inbox.category.crm', value: 'crm' },
  { color: 'error', icon: 'lucide:shield-alert', labelKey: 'inbox.category.alert', value: 'alert' },
] as const;

export const INBOX_PRIORITY_OPTIONS = [
  { color: 'default', labelKey: 'inbox.priority.low', value: 'low' },
  { color: 'cyan', labelKey: 'inbox.priority.normal', value: 'normal' },
  { color: 'orange', labelKey: 'inbox.priority.high', value: 'high' },
  { color: 'magenta', labelKey: 'inbox.priority.urgent', value: 'urgent' },
] as const;

export type InboxCategory = (typeof INBOX_CATEGORY_OPTIONS)[number]['value'];

export function getVisibleInboxCategoryOptions(
  visibleCategories?: readonly string[],
) {
  const visible =
    visibleCategories && visibleCategories.length > 0
      ? visibleCategories
      : INBOX_CATEGORY_OPTIONS.map((item) => item.value);

  return INBOX_CATEGORY_OPTIONS.filter((item) => visible.includes(item.value));
}

export function isInboxCategoryHidden(
  category: string,
  visibleCategories?: readonly string[],
) {
  if (!visibleCategories || visibleCategories.length === 0) {
    return false;
  }

  return !visibleCategories.includes(category);
}

export function normalizeInboxCategoryFilter(
  category: string,
  visibleCategories?: readonly string[],
) {
  if (
    category === INBOX_ALL_CATEGORY_KEY ||
    isInboxCategoryHidden(category, visibleCategories)
  ) {
    return INBOX_ALL_CATEGORY_KEY;
  }

  return category;
}

export function resolveInboxCategory(
  category?: string | null,
  type?: string,
): InboxCategory {
  if (category && INBOX_CATEGORY_OPTIONS.some((item) => item.value === category)) {
    return category as InboxCategory;
  }
  if (type === 'todo') {
    return 'task';
  }
  if (type === 'account') {
    return 'alert';
  }
  return 'notice';
}

export function getInboxCategoryMeta(category: InboxCategory) {
  return INBOX_CATEGORY_OPTIONS.find((item) => item.value === category)!;
}
