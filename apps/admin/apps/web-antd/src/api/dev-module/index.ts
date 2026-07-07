import { requestClient } from '#/api/request';

export interface DevModuleRow {
  name: string;
  title: string;
  version: string | null;
  enabled: boolean;
  directory: string;
  providers: string[];
  seeders: string[];
  providers_count: number;
  seeders_count: number;
}

export interface DevModuleDetail extends DevModuleRow {
  manifest: Record<string, unknown>;
  manifest_path: string;
}

export function fetchDevModules() {
  return requestClient.get<DevModuleRow[]>('/dev/modules');
}

export function fetchDevModule(name: string) {
  return requestClient.get<DevModuleDetail>(`/dev/modules/${name}`);
}

export function updateDevModuleStatus(name: string, enabled: boolean) {
  return requestClient.put<DevModuleRow>(`/dev/modules/${name}/status`, { enabled });
}
