import { requestClient } from '#/api/request';

export interface ConfigRow {
  id: number;
  group_name: string;
  name: string;
  key: string;
  value: string | null;
  type: string;
  sort: number;
  remark: string | null;
}

export interface ConfigListResult {
  list: ConfigRow[];
  grouped: Record<string, ConfigRow[]>;
}

export interface ConfigUpsertItem {
  key: string;
  value?: string | null;
  name?: string;
  group_name?: string;
  type?: string;
  sort?: number;
  remark?: string | null;
}

export function fetchConfigs(groupName?: string) {
  return requestClient.get<ConfigListResult>('/system/configs', {
    params: groupName ? { group_name: groupName } : undefined,
  });
}

export function batchUpsertConfigs(items: ConfigUpsertItem[]) {
  return requestClient.put('/system/configs', { items });
}
