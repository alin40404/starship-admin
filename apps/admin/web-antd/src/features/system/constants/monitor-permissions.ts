/**
 * 监控中心权限，与 modules/System/app/Support/*LogPermissions.php 保持一致。
 */
import { $t } from '#/locales';

export const MONITOR_OPERATION_LOG_PERMISSIONS = {
  MANAGE: 'monitor.operation_logs',
  QUERY: 'monitor.operation_logs.query',
  DELETE: 'monitor.operation_logs.delete',
  BATCH_DELETE: 'monitor.operation_logs.batch_delete',
} as const;

export const MONITOR_LOGIN_LOG_PERMISSIONS = {
  MANAGE: 'monitor.login_logs',
  QUERY: 'monitor.login_logs.query',
  DELETE: 'monitor.login_logs.delete',
  BATCH_DELETE: 'monitor.login_logs.batch_delete',
} as const;

export const MONITOR_EXCEPTION_LOG_PERMISSIONS = {
  MANAGE: 'monitor.exception_logs',
  QUERY: 'monitor.exception_logs.query',
  DELETE: 'monitor.exception_logs.delete',
  BATCH_DELETE: 'monitor.exception_logs.batch_delete',
} as const;

export function getMonitorLoginStatusTagOptions() {
  return [
    { color: 'error', label: $t('monitor.shared.statusFailed'), value: 0 },
    { color: 'success', label: $t('monitor.shared.statusSuccess'), value: 1 },
  ] as const;
}

export function getMonitorLoginActionTagOptions() {
  return [
    { color: 'processing', label: $t('monitor.shared.actionLogin'), value: 'login' },
    { color: 'default', label: $t('monitor.shared.actionLogout'), value: 'logout' },
  ] as const;
}

/** 操作日志等方法列/筛选展示用（含历史数据）；新接口约定仍仅 GET/POST，见 AI 规则 */
export const MONITOR_HTTP_METHOD_TAG_OPTIONS = [
  { color: 'processing', label: 'GET', value: 'GET' },
  { color: 'success', label: 'POST', value: 'POST' },
  { color: 'processing', label: 'PUT', value: 'PUT' },
  { color: 'processing', label: 'PATCH', value: 'PATCH' },
  { color: 'error', label: 'DELETE', value: 'DELETE' },
] as const;

/** 操作日志「模块」列：已知路由前缀固定配色，其余按名称哈希取色 */
const OPERATION_LOG_MODULE_COLORS: Record<string, string> = {
  auth: 'default',
  dev: 'geekblue',
  generator: 'cyan',
  monitor: 'purple',
  system: 'blue',
};

export const MONITOR_OPERATION_LOG_MODULE_OPTIONS = Object.keys(
  OPERATION_LOG_MODULE_COLORS,
)
  .sort()
  .map((value) => ({ label: value, value }));

const OPERATION_LOG_MODULE_COLOR_PALETTE = [
  'processing',
  'success',
  'warning',
  'magenta',
  'red',
  'volcano',
  'orange',
  'gold',
  'lime',
  'green',
  'cyan',
  'blue',
  'geekblue',
  'purple',
] as const;

function hashModuleName(name: string): number {
  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    hash = (hash * 31 + name.charCodeAt(i)) >>> 0;
  }
  return hash;
}

export function getOperationLogModuleColor(
  module: string | null | undefined,
): string {
  if (!module) {
    return 'default';
  }
  const key = module.toLowerCase();
  const mapped = OPERATION_LOG_MODULE_COLORS[key];
  if (mapped) {
    return mapped;
  }
  const index = hashModuleName(key) % OPERATION_LOG_MODULE_COLOR_PALETTE.length;
  return OPERATION_LOG_MODULE_COLOR_PALETTE[index] ?? 'default';
}

export function getOperationLogStatusCodeColor(
  code: number | null | undefined,
): string {
  if (code == null) {
    return 'default';
  }
  if (code >= 200 && code < 300) {
    return 'success';
  }
  if (code >= 400 && code < 500) {
    return 'warning';
  }
  if (code >= 500) {
    return 'error';
  }
  return 'default';
}

/** 异常日志「异常类型」列：已知异常类固定配色，展示短类名 */
const EXCEPTION_TYPE_COLORS: Record<string, string> = {
  AuthenticationException: 'error',
  AuthorizationException: 'error',
  HttpResponseException: 'processing',
  MethodNotAllowedHttpException: 'orange',
  ModelNotFoundException: 'warning',
  NotFoundHttpException: 'warning',
  QueryException: 'red',
  UnauthorizedHttpException: 'error',
  ValidationException: 'gold',
};

const EXCEPTION_TYPE_COLOR_PALETTE = [
  'processing',
  'success',
  'warning',
  'magenta',
  'red',
  'volcano',
  'orange',
  'gold',
  'lime',
  'green',
  'cyan',
  'blue',
  'geekblue',
  'purple',
] as const;

function hashExceptionTypeName(name: string): number {
  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    hash = (hash * 31 + name.charCodeAt(i)) >>> 0;
  }
  return hash;
}

export function formatExceptionClassLabel(
  exceptionClass: string | null | undefined,
): string {
  if (!exceptionClass) {
    return '';
  }
  const text = String(exceptionClass);
  const parts = text.split('\\');
  return parts[parts.length - 1] || text;
}

export function getExceptionTypeColor(
  exceptionClass: string | null | undefined,
): string {
  const shortName = formatExceptionClassLabel(exceptionClass);
  if (!shortName) {
    return 'default';
  }
  const mapped = EXCEPTION_TYPE_COLORS[shortName];
  if (mapped) {
    return mapped;
  }
  const index =
    hashExceptionTypeName(shortName) % EXCEPTION_TYPE_COLOR_PALETTE.length;
  return EXCEPTION_TYPE_COLOR_PALETTE[index] ?? 'default';
}
