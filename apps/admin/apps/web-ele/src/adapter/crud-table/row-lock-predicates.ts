/**
 * 行级「只读 / 禁用开关」条件：供列表列配置与后续 CRUD 生成器引用。
 * 生成器可输出 predicateId 字符串，运行时映射为下列函数（勿用任意字符串 eval）。
 */
export type RowLockPredicateId =
  | 'never'
  /** 管理员：超级管理员账号 */
  | 'admin.is_super'
  /** 角色：内置超级管理员角色（种子 code） */
  | 'role.code_super_admin';

export type RowLockPredicateFn = (row: Record<string, unknown>) => boolean;

export const ROW_LOCK_PREDICATES: Record<RowLockPredicateId, RowLockPredicateFn> =
  {
    never: () => false,
    'admin.is_super': (row) => Number(row.is_super) === 1,
    'role.code_super_admin': (row) => row.code === 'super_admin',
  };

export function resolveRowLockPredicate(
  id: RowLockPredicateId,
): RowLockPredicateFn {
  return ROW_LOCK_PREDICATES[id] ?? ROW_LOCK_PREDICATES.never;
}

/** 支持生成器写字符串 id，或手写页面直接传函数 */
export function normalizeLockPredicate(
  lock:
    | RowLockPredicateId
    | RowLockPredicateFn
    | undefined,
): RowLockPredicateFn {
  if (lock === undefined) {
    return ROW_LOCK_PREDICATES.never;
  }
  if (typeof lock === 'function') {
    return lock;
  }
  return resolveRowLockPredicate(lock);
}
