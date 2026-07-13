/**
 * 演示产品按钮权限，与 Modules\Generator\App\Support\ProductsPermissions 保持一致。
 */
export const GENERATOR_PRODUCTS_PERMISSIONS = {
  CREATE: 'generator.products.create',
  DELETE: 'generator.products.delete',
  QUERY: 'generator.products.query',
  UPDATE: 'generator.products.update',
  BATCH_DELETE: 'generator.products.batch_delete',

  BATCH_STATUS: 'generator.products.batch_status',

} as const;

export type ProductsPermissionCode =
  (typeof GENERATOR_PRODUCTS_PERMISSIONS)[keyof typeof GENERATOR_PRODUCTS_PERMISSIONS];
