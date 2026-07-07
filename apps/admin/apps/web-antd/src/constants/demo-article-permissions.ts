/**
 * 演示文章按钮权限，与 Modules\System\App\Support\DemoArticlePermissions 保持一致。
 */
export const SYSTEM_DEMO_ARTICLES_PERMISSIONS = {
  CREATE: 'system.demo_articles.create',
  DELETE: 'system.demo_articles.delete',
  QUERY: 'system.demo_articles.query',
  UPDATE: 'system.demo_articles.update',

  BATCH_DELETE: 'system.demo_articles.batch_delete',


  BATCH_STATUS: 'system.demo_articles.batch_status',

} as const;

export type DemoArticlePermissionCode =
  (typeof SYSTEM_DEMO_ARTICLES_PERMISSIONS)[keyof typeof SYSTEM_DEMO_ARTICLES_PERMISSIONS];
