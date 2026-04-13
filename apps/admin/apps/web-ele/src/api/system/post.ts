import type { LaravelPage } from './_types';

import { pageToItems } from './_types';

import { requestClient } from '#/api/request';

export interface PostRow {
  id: number;
  name: string;
  code: string | null;
  sort: number;
  status: number;
  remark: string | null;
}

export async function fetchPostPage(params: { page?: number; per_page?: number }) {
  const page = await requestClient.get<LaravelPage<PostRow>>('/system/posts', {
    params,
  });
  return pageToItems(page);
}
