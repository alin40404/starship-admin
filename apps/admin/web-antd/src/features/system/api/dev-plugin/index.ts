import { requestClient } from '#/api/request';

export interface PluginPageMeta {
  key: string;
  title: string;
  permission?: string;
}

export interface PluginTypeOption {
  value: string;
  label: string;
}

export interface PluginRow {
  name: string;
  slug: string;
  title: string;
  type: string | null;
  type_label: string;
  version: string;
  description: string | null;
  author: string | null;
  directory: string;
  namespace: string | null;
  status: number;
  status_label: string;
  enabled: boolean;
  installed: boolean;
  has_config: boolean;
  providers: string[];
  providers_count: number;
  pages: PluginPageMeta[];
  cover: string | null;
  cover_url: string | null;
  installed_at: string | null;
  enabled_at: string | null;
}

export interface PluginDetail extends PluginRow {
  manifest: Record<string, unknown>;
  manifest_path: string;
  config: Record<string, string | number | boolean | null>;
  config_schema: string[];
}

export interface PluginListResult {
  items: PluginRow[];
  types: PluginTypeOption[];
}

export interface PluginDiscoverResult {
  discovered: number;
  created: number;
  updated: number;
}

export function fetchPlugins() {
  return requestClient.get<PluginListResult>('/dev/plugins');
}

export function discoverPlugins() {
  return requestClient.post<PluginDiscoverResult>('/dev/plugins/discover');
}

export function fetchPlugin(slug: string) {
  return requestClient.get<PluginDetail>(`/dev/plugins/${slug}`);
}

export function fetchPluginCoverBlob(slug: string) {
  return requestClient.get<Blob>(`/dev/plugins/${slug}/cover`, {
    responseType: 'blob',
    responseReturn: 'body',
  } as Record<string, unknown>);
}

export function installPlugin(slug: string) {
  return requestClient.post<PluginRow>(`/dev/plugins/${slug}/install`);
}

export function enablePlugin(slug: string) {
  return requestClient.post<PluginRow>(`/dev/plugins/${slug}/enable`);
}

export function disablePlugin(slug: string) {
  return requestClient.post<PluginRow>(`/dev/plugins/${slug}/disable`);
}

export function uninstallPlugin(slug: string) {
  return requestClient.post<PluginRow>(`/dev/plugins/${slug}/uninstall`);
}

export function updatePluginConfig(slug: string, payload: Record<string, unknown>) {
  return requestClient.put<Record<string, string | number | boolean | null>>(
    `/dev/plugins/${slug}/config`,
    payload,
  );
}

export function testSmsAliyunPlugin(payload: { mobile: string }) {
  return requestClient.post<{ message: string }>('/plugin/sms-aliyun/test', payload);
}
