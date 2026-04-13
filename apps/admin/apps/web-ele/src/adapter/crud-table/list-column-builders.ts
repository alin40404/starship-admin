import type { VxeTableGridColumns } from '#/adapter/vxe-table';

import type { RowLockPredicateFn, RowLockPredicateId } from './row-lock-predicates';

import { normalizeLockPredicate } from './row-lock-predicates';

type ColumnItem = NonNullable<VxeTableGridColumns>[number];

/**
 * 列表「状态」列：与管理员模块一致（文案 + 开关 + 确认后 onToggle）。
 * 对应 vxe 渲染器 `CellStatusSwitch`（见 adapter/vxe-table.ts）。
 */
export function statusSwitchListColumn<T extends object>(options: {
  field?: string;
  lockPredicate?: RowLockPredicateId | RowLockPredicateFn;
  onToggle: (row: T, nextStatus: number) => Promise<void>;
  sortable?: boolean;
  title: string;
  width?: number;
}): ColumnItem {
  const field = options.field ?? 'status';
  const lockFn = normalizeLockPredicate(
    options.lockPredicate as RowLockPredicateId | RowLockPredicateFn | undefined,
  );

  return {
    align: 'center',
    cellRender: {
      attrs: {
        lockStatusSwitch: (row: T) => lockFn(row as Record<string, unknown>),
        onToggle: options.onToggle,
      },
      name: 'CellStatusSwitch',
    },
    field,
    sortable: options.sortable ?? false,
    title: options.title,
    width: options.width ?? 148,
  };
}

/**
 * 列表「仅展示、锁定行灰色」列：如内置数据的排序号。
 * 对应 vxe 渲染器 `CellLockedField`。
 */
export function lockedFieldListColumn(options: {
  field: string;
  lockedTitle?: string;
  lockPredicate: RowLockPredicateId | RowLockPredicateFn;
  title: string;
  width?: number;
}): ColumnItem {
  const lockFn = normalizeLockPredicate(
    options.lockPredicate as RowLockPredicateId | RowLockPredicateFn,
  );

  return {
    align: 'center',
    cellRender: {
      attrs: {
        isLocked: (row: Record<string, unknown>) => lockFn(row),
        lockedTitle: options.lockedTitle ?? '不可修改',
      },
      name: 'CellLockedField',
    },
    field: options.field,
    title: options.title,
    width: options.width ?? 88,
  };
}
