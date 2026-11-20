import type { UserInfo } from '@vben/types';

import { preferences } from '@vben/preferences';

import { requestClient } from '#/api/request';

interface LaravelRole {
  id: number;
  name: string;
  code: string;
}

/** Starship `/auth/me` 的 data 段 */
export interface LaravelAdminMe {
  id: number;
  username: string;
  nickname: string | null;
  real_name: string | null;
  avatar: string | null;
  email?: null | string;
  mobile?: null | string;
  roles?: LaravelRole[];
  permissions?: string[];
}

export interface UpdateProfilePayload {
  nickname?: string;
  real_name?: string;
  avatar?: string;
  email?: string;
  mobile?: string;
}

export interface UpdatePasswordPayload {
  old_password: string;
  new_password: string;
}

function mapLaravelAdminToUserInfo(admin: LaravelAdminMe): UserInfo {
  const roles =
    admin.roles
      ?.map((r) => r.code)
      .filter((c): c is string => Boolean(c)) ?? [];

  return {
    userId: String(admin.id),
    username: admin.username,
    realName: admin.nickname || admin.real_name || admin.username,
    avatar: admin.avatar || preferences.app.defaultAvatar,
    desc: admin.email || '',
    homePath: preferences.app.defaultHomePath,
    token: '',
    roles,
  };
}

/**
 * 当前登录管理员信息（Starship：`GET /admin-api/auth/me`）
 */
export async function getUserInfoApi() {
  const raw = await requestClient.get<LaravelAdminMe | null>('/auth/me');
  if (!raw?.id) {
    throw new Error('未登录或会话已失效');
  }
  return mapLaravelAdminToUserInfo(raw);
}

export async function getMyProfileApi() {
  const raw = await requestClient.get<LaravelAdminMe | null>('/auth/me');
  if (!raw?.id) {
    throw new Error('未登录或会话已失效');
  }
  return raw;
}

export async function updateMyProfileApi(payload: UpdateProfilePayload) {
  return requestClient.post<LaravelAdminMe>('/auth/profile', payload);
}

export async function updateMyPasswordApi(payload: UpdatePasswordPayload) {
  return requestClient.post<null>('/auth/password', payload);
}
