import type { LaravelPaginate } from '../system/types';

import { requestClient } from '#/api/request';

export interface SchemaTableItem {
  name: string;
  comment: string | null;
}

export interface GeneratorColumnRow {
  id: number;
  generator_table_id: number;
  column_name: string;
  column_type: string;
  column_comment: string | null;
  ts_type: string;
  is_pk: boolean;
  is_list: boolean;
  is_search: boolean;
  is_form: boolean;
  form_component: string;
  dict_code: string | null;
  validation_rules: string[] | null;
  sort: number;
}

export interface GeneratorTableRow {
  id: number;
  table_name: string;
  class_name: string;
  module_slug: string;
  business_name: string;
  route_group: string;
  route_path: string;
  api_prefix: string;
  permission_prefix: string;
  menu_parent_path: string | null;
  template_type: string;
  options: Record<string, unknown> | null;
  status: 'draft' | 'generated' | string;
  generated_at: string | null;
  columns_count?: number;
  columns?: GeneratorColumnRow[];
  created_at?: string;
  updated_at?: string;
}

export interface GeneratorTablePageQuery {
  page: number;
  per_page: number;
}

export interface GeneratorImportPayload {
  table_name: string;
  module?: string;
  class?: string;
  business?: string;
  route_group?: string;
  api_prefix?: string;
  route_path?: string;
  permission_prefix?: string;
  menu_parent_path?: string;
}

export interface CodegenFileItem {
  path: string;
  content: string;
}

export interface CodegenFileAction {
  path: string;
  action?: string;
  reason?: string;
}

export interface CodegenResult {
  config: GeneratorTableRow;
  files: CodegenFileItem[];
  written: CodegenFileAction[];
  skipped: CodegenFileAction[];
  conflicts: CodegenFileAction[];
  menu?: CodegenFileAction[];
  record?: GeneratorRecordRow;
}

export interface GeneratorRecordRow {
  id: number;
  generator_table_id: number;
  files: CodegenFileAction[];
  operator_id: number | null;
  operator_name: string | null;
  source: string;
  options: Record<string, unknown> | null;
  created_at?: string;
  table_config?: Pick<GeneratorTableRow, 'id' | 'table_name' | 'business_name'>;
}

export interface GeneratePayload {
  force?: boolean;
  sync_menu?: boolean;
}

export function fetchGeneratorModules() {
  return requestClient.get<string[]>('/generator/modules');
}

export function fetchSchemaTables(keyword?: string) {
  return requestClient.get<SchemaTableItem[]>('/generator/schemas/tables', {
    params: keyword ? { keyword } : undefined,
  });
}

export function fetchGeneratorTablePage(params: GeneratorTablePageQuery) {
  return requestClient.get<LaravelPaginate<GeneratorTableRow>>('/generator/tables', {
    params,
  });
}

export function fetchGeneratorTable(id: number) {
  return requestClient.get<GeneratorTableRow>(`/generator/tables/${id}`);
}

export function importGeneratorTable(data: GeneratorImportPayload) {
  return requestClient.post<GeneratorTableRow>('/generator/tables/import', data);
}

export function deleteGeneratorTable(id: number) {
  return requestClient.delete(`/generator/tables/${id}`);
}

export function previewCodegen(id: number) {
  return requestClient.post<CodegenResult>(`/generator/tables/${id}/preview`);
}

export function generateCodegen(id: number, data: GeneratePayload = {}) {
  return requestClient.post<CodegenResult>(`/generator/tables/${id}/generate`, data);
}

export function fetchGeneratorRecords(params: GeneratorTablePageQuery) {
  return requestClient.get<LaravelPaginate<GeneratorRecordRow>>('/generator/records', {
    params,
  });
}
