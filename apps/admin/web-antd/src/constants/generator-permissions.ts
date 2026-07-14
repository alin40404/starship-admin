/**
 * 代码生成器权限，与 modules/Generator/app/Support/GeneratorPermissions.php 保持一致。
 */
export const GENERATOR_PERMISSIONS = {
  /** 菜单页访问（与菜单 permission 一致） */
  MANAGE: 'generator.codegen',
  QUERY: 'generator.tables.query',
  IMPORT: 'generator.tables.import',
  UPDATE: 'generator.tables.update',
  DELETE: 'generator.tables.delete',
  PREVIEW: 'generator.codegen.preview',
  GENERATE: 'generator.codegen.generate',
  RECORDS_QUERY: 'generator.records.query',
} as const;

export type GeneratorPermissionCode =
  (typeof GENERATOR_PERMISSIONS)[keyof typeof GENERATOR_PERMISSIONS];
