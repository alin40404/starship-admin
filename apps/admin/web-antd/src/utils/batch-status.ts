import { $t } from '#/locales';

/**
 * 选出「当前状态 ≠ 目标状态」的行，用于批量启用/禁用（或正常/隐藏）计数与提交。
 */
export function filterRowsForStatusChange<T extends { status?: unknown }>(
  rows: T[],
  targetStatus: unknown,
): T[] {
  return rows.filter((row) => {
    if (
      typeof targetStatus === 'number' ||
      typeof row.status === 'number'
    ) {
      return Number(row.status) !== Number(targetStatus);
    }
    return row.status !== targetStatus;
  });
}

/** 数量为 0 时不带括号计数，例如「启用」；>0 时为「启用（n）」 */
export function formatBatchActionLabel(
  plainKey: string,
  withCountKey: string,
  count: number,
): string {
  if (count <= 0) {
    return $t(plainKey);
  }
  return $t(withCountKey, [count]);
}
