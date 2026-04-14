import type { LaravelPaginate } from './types';

import { requestClient } from '#/api/request';

export interface RoleRow {
  id: number;
  name: string;
  code: string;
  status: number;
}

export function fetchRolePage(params: { page: number; per_page: number }) {
  return requestClient.get<LaravelPaginate<RoleRow>>('/system/roles', {
    params,
  });
}
