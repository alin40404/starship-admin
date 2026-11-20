import type { LaravelPaginate } from '#/features/system/api/types';

import { requestClient } from '#/api/request';

export interface MonitorLogPageQuery {
  page: number;
  per_page: number;
  keyword?: string;
  date_from?: string;
  date_to?: string;
  sort_field?: string;
  sort_order?: 'asc' | 'desc';
}

export interface OperationLogRow {
  id: number;
  admin_id: number | null;
  admin_name: string | null;
  module: string | null;
  action: string;
  title: string;
  content: string | Record<string, unknown> | null;
  method: string | null;
  url: string | null;
  status_code: number | null;
  ip: string | null;
  user_agent: string | null;
  created_at: string;
}

export interface LoginLogRow {
  id: number;
  admin_id: number | null;
  username: string;
  action: 'login' | 'logout';
  status: 0 | 1;
  message: string | null;
  ip: string | null;
  user_agent: string | null;
  created_at: string;
}

export interface ExceptionLogRow {
  id: number;
  trace_id: string;
  exception_class: string;
  message: string;
  file: string | null;
  line: number | null;
  request_method: string | null;
  request_url: string | null;
  admin_id: number | null;
  admin_name: string | null;
  ip: string | null;
  user_agent: string | null;
  created_at: string;
  log_path?: string | null;
  log_hint?: string;
}

export function fetchOperationLogPage(
  params: MonitorLogPageQuery & { module?: string; method?: string; admin_name?: string },
) {
  return requestClient.get<LaravelPaginate<OperationLogRow>>(
    '/monitor/operation-logs',
    { params },
  );
}

export function fetchOperationLog(id: number) {
  return requestClient.get<OperationLogRow>(`/monitor/operation-logs/${id}`);
}

export function deleteOperationLog(id: number) {
  return requestClient.post(`/monitor/operation-logs/${id}/delete`);
}

export function batchDeleteOperationLogs(ids: number[]) {
  return requestClient.post<{ deleted: number }>(
    '/monitor/operation-logs/batch-destroy',
    { ids },
  );
}

export function fetchLoginLogPage(
  params: MonitorLogPageQuery & {
    username?: string;
    action?: string;
    status?: number;
  },
) {
  return requestClient.get<LaravelPaginate<LoginLogRow>>('/monitor/login-logs', {
    params,
  });
}

export function fetchLoginLog(id: number) {
  return requestClient.get<LoginLogRow>(`/monitor/login-logs/${id}`);
}

export function deleteLoginLog(id: number) {
  return requestClient.post(`/monitor/login-logs/${id}/delete`);
}

export function batchDeleteLoginLogs(ids: number[]) {
  return requestClient.post<{ deleted: number }>(
    '/monitor/login-logs/batch-destroy',
    { ids },
  );
}

export function fetchExceptionLogPage(
  params: MonitorLogPageQuery & { exception_class?: string },
) {
  return requestClient.get<LaravelPaginate<ExceptionLogRow>>(
    '/monitor/exception-logs',
    { params },
  );
}

export function fetchExceptionLog(id: number) {
  return requestClient.get<ExceptionLogRow>(`/monitor/exception-logs/${id}`);
}

export function deleteExceptionLog(id: number) {
  return requestClient.post(`/monitor/exception-logs/${id}/delete`);
}

export function batchDeleteExceptionLogs(ids: number[]) {
  return requestClient.post<{ deleted: number }>(
    '/monitor/exception-logs/batch-destroy',
    { ids },
  );
}
