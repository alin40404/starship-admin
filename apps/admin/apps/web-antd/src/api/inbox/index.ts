import type { LaravelPaginate } from '#/api/system/types';

import { requestClient } from '#/api/request';

export interface InboxMessageRow {
  id: number;
  admin_id: number;
  type: 'account' | 'system' | 'todo';
  title: string;
  content: string | null;
  link: string | null;
  is_read: 0 | 1;
  read_at: string | null;
  created_at: string;
}

export interface InboxMessagePageQuery {
  page: number;
  per_page?: number;
  keyword?: string;
  type?: string;
  is_read?: number;
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

export function markInboxMessageRead(id: number) {
  return requestClient.post<null>(`/inbox/messages/${id}/read`);
}

export function markAllInboxMessagesRead() {
  return requestClient.post<null>('/inbox/messages/read-all');
}

export function deleteInboxMessage(id: number) {
  return requestClient.delete(`/inbox/messages/${id}`);
}

export function batchDeleteInboxMessages(ids: number[]) {
  return requestClient.post<{ deleted: number }>('/inbox/messages/batch-destroy', {
    ids,
  });
}
