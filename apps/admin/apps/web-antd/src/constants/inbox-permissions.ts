export const INBOX_MENU_PERMISSION = 'dashboard.inbox';

export const INBOX_MESSAGE_TYPE_OPTIONS = [
  { color: 'processing', labelKey: 'inbox.type.system', value: 'system' },
  { color: 'warning', labelKey: 'inbox.type.todo', value: 'todo' },
  { color: 'success', labelKey: 'inbox.type.account', value: 'account' },
] as const;
