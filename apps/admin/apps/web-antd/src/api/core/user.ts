import type { UserInfo } from '@vben/types';

import { preferences } from '@vben/preferences';

import { requestClient } from '#/api/request';

interface LaravelRole {
  id: number;
  name: string;
  code: string;
}

/** Starship `/auth/me` 的 data 段 */
interface LaravelAdminMe {
  id: number;
  username: string;
  nickname: string | null;
  real_name: string | null;
  avatar: string | null;
  roles?: LaravelRole[];
  permissions?: string[];
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
    desc: '',
    homePath: '/dashboard/console',
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
