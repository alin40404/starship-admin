/**
 * 监控中心权限，与 modules/Monitor/app/Support/*Permissions.php 保持一致。
 */
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

export const MONITOR_LOGIN_STATUS_TAG_OPTIONS = [
  { color: 'error', label: '失败', value: 0 },
  { color: 'success', label: '成功', value: 1 },
] as const;

export const MONITOR_LOGIN_ACTION_TAG_OPTIONS = [
  { color: 'processing', label: '登录', value: 'login' },
  { color: 'default', label: '退出', value: 'logout' },
] as const;
