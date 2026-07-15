import { requestClient } from '#/api/request';

export interface ThemeRow {
  name: string;
  slug: string;
  title: string;
  type: string;
  version: string;
  description: string | null;
  author: string | null;
  directory: string;
  status: number;
  status_label: string;
  enabled: boolean;
  installed: boolean;
  is_active: boolean;
  deletable?: boolean;
  layout: string | null;
  cover: string | null;
  cover_url: string | null;
  installed_at: string | null;
  enabled_at: string | null;
}

export interface ThemeDetail extends ThemeRow {
  manifest: Record<string, unknown>;
  manifest_path: string;
  entry: Record<string, unknown>;
}

export interface ThemeDiscoverResult {
  discovered: number;
  created: number;
  updated: number;
}

export function fetchThemes() {
  return requestClient.get<ThemeRow[]>('/dev/themes');
}

export function discoverThemes() {
  return requestClient.post<ThemeDiscoverResult>('/dev/themes/discover');
}

export function fetchTheme(slug: string) {
  return requestClient.get<ThemeDetail>(`/dev/themes/${slug}`);
}

export function fetchThemeCoverBlob(slug: string) {
  return requestClient.get<Blob>(`/dev/themes/${slug}/cover`, {
    responseType: 'blob',
    responseReturn: 'body',
  } as Record<string, unknown>);
}

export function installTheme(slug: string) {
  return requestClient.post<ThemeRow>(`/dev/themes/${slug}/install`);
}

export function enableTheme(slug: string) {
  return requestClient.post<ThemeRow>(`/dev/themes/${slug}/enable`);
}

export function disableTheme(slug: string) {
  return requestClient.post<ThemeRow>(`/dev/themes/${slug}/disable`);
}

export function uninstallTheme(slug: string) {
  return requestClient.post<ThemeRow>(`/dev/themes/${slug}/uninstall`);
}

export function deleteTheme(slug: string) {
  return requestClient.post(`/dev/themes/${slug}/delete`);
}

export function switchTheme(slug: string, type?: string) {
  return requestClient.post<ThemeRow>(
    `/dev/themes/${slug}/switch`,
    type ? { type } : {},
  );
}
