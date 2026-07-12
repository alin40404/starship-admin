import type { LaravelPaginate } from '#/api/system/types';

import type { InboxCategory } from '#/constants/inbox-permissions';

import { requestClient } from '#/api/request';

export interface InboxSender {
  avatar: string | null;
  id: number;
  real_name: string | null;
  username: string;
}

export interface InboxMessageRow {
  action_deadline: string | null;
  admin_id: number;
  biz_id: number | null;
  biz_type: string | null;
  category: InboxCategory;
  content: string | null;
  created_at: string;
  group_key: string | null;
  id: number;
  is_read: 0 | 1;
  link: string | null;
  payload: Record<string, unknown> | null;
  priority: 'high' | 'low' | 'normal' | 'urgent';
  read_at: string | null;
  sender?: InboxSender | null;
  sender_id: number | null;
  status: 'cancelled' | 'done' | 'expired' | 'pending' | null;
  sub_type: string | null;
  title: string;
  type: 'account' | 'system' | 'todo';
}

export interface InboxMessagePageQuery {
  category?: string;
  is_read?: number;
  keyword?: string;
  page: number;
  per_page?: number;
  type?: string;
}

export function fetchInboxMessagePage(params: InboxMessagePageQuery) {
  return requestClient.get<LaravelPaginate<InboxMessageRow>>('/inbox/messages', {
    params,
  });
}

export function fetchInboxMessage(id: number) {
  return requestClient.get<InboxMessageRow>(`/inbox/messages/${id}`);
}

export function fetchInboxRecentMessages(limit = 10) {
  return requestClient.get<InboxMessageRow[]>('/inbox/messages/recent', {
    params: { limit },
  });
}

export function fetchInboxUnreadCount() {
  return requestClient.get<{ count: number }>('/inbox/messages/unread-count');
}

export type InboxCategoryStats = Record<InboxCategory | 'all', number>;

export function fetchInboxCategoryStats() {
  return requestClient.get<InboxCategoryStats>('/inbox/messages/category-stats');
}

export interface InboxSettings {
  visible_categories: InboxCategory[];
}

export function fetchInboxSettings() {
  return requestClient.get<InboxSettings>('/inbox/messages/settings');
}

export function markInboxMessageRead(id: number) {
  return requestClient.post<null>(`/inbox/messages/${id}/read`);
}

export function markAllInboxMessagesRead() {
  return requestClient.post<null>('/inbox/messages/read-all');
}

export function clearReadInboxMessages() {
  return requestClient.post<{ deleted: number }>('/inbox/messages/clear-read');
}

export function deleteInboxMessage(id: number) {
  return requestClient.delete(`/inbox/messages/${id}`);
}

export function batchDeleteInboxMessages(ids: number[]) {
  return requestClient.post<{ deleted: number }>('/inbox/messages/batch-destroy', {
    ids,
  });
}
