/**
 * 附件管理按钮权限，与 modules/System/app/Support/AttachmentPermissions.php 保持一致。
 */
export const ATTACHMENT_PERMISSIONS = {
  BATCH_DELETE: 'system.attachments.batch_delete',
  DELETE: 'system.attachments.delete',
  QUERY: 'system.attachments.query',
  UPLOAD: 'system.attachments.upload',
} as const;

export type AttachmentPermissionCode =
  (typeof ATTACHMENT_PERMISSIONS)[keyof typeof ATTACHMENT_PERMISSIONS];
