import type { LaravelPaginate } from '#/features/system/api/types';

import { requestClient } from '#/api/request';

export interface MemberRow {
  id: number;
  username: string;
  nickname: string | null;
  email: string | null;
  mobile: string | null;
  avatar: string | null;
  status: number;
  remark: string | null;
  last_login_ip?: string | null;
  last_login_at?: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface MemberPageQuery {
  page: number;
  per_page: number;
  keyword?: string;
  username?: string;
  mobile?: string;
  status?: number;
  sort_field?: string;
  sort_order?: 'asc' | 'desc';
}

export interface MemberSavePayload {
  username: string;
  password?: string;
  nickname?: string | null;
  email?: string | null;
  mobile?: string | null;
  status?: number;
  remark?: string | null;
  avatar?: string | null;
}

export function fetchMemberPage(params: MemberPageQuery) {
  return requestClient.get<LaravelPaginate<MemberRow>>('/member/members', {
    params,
  });
}

export function fetchMember(id: number) {
  return requestClient.get<MemberRow>(`/member/members/${id}`);
}

export function createMember(data: MemberSavePayload) {
  return requestClient.post<MemberRow>('/member/members', data);
}

export function updateMember(id: number, data: Partial<MemberSavePayload>) {
  return requestClient.put<MemberRow>(`/member/members/${id}`, data);
}

export function updateMemberStatus(id: number, status: number) {
  return requestClient.put<MemberRow>(`/member/members/${id}/status`, {
    status,
  });
}

export function deleteMember(id: number) {
  return requestClient.delete(`/member/members/${id}`);
}
