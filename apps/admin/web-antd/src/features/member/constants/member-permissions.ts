/**
 * 会员管理按钮权限，与 modules/Member/app/Support/MemberPermissions.php 保持一致。
 */
export const MEMBER_PERMISSIONS = {
  CREATE: 'member.members.create',
  DELETE: 'member.members.delete',
  QUERY: 'member.members.query',
  STATUS: 'member.members.status',
  UPDATE: 'member.members.update',
} as const;

export type MemberPermissionCode =
  (typeof MEMBER_PERMISSIONS)[keyof typeof MEMBER_PERMISSIONS];
