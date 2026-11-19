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
