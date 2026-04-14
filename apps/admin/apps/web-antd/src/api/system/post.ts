import type { LaravelPaginate } from './types';

import { requestClient } from '#/api/request';

export interface PostRow {
  id: number;
  name: string;
  code: string;
  status: number;
}

export function fetchPostPage(params: { page: number; per_page: number }) {
  return requestClient.get<LaravelPaginate<PostRow>>('/system/posts', {
    params,
  });
}
