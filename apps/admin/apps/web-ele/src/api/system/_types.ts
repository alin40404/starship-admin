/** Laravel LengthAwarePaginator JSON */
export interface LaravelPage<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export function pageToItems<T>(page: LaravelPage<T>): { items: T[]; total: number } {
  return { items: page.data, total: page.total };
}
