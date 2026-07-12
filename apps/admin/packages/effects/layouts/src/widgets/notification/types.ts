interface NotificationItem {
  id: number | string;
  avatar: string;
  date: string;
  isRead?: boolean;
  message: string;
  title: string;
  /**
   * 通知分类，用于差异化展示
   */
  category?: 'alert' | 'approval' | 'crm' | 'notice' | 'task';
  /**
   * 优先级
   */
  priority?: 'high' | 'low' | 'normal' | 'urgent';
  /**
   * 分类图标（Iconify 名称）
   */
  icon?: string;
  /**
   * 发起人展示名
   */
  senderName?: string;
  /**
   * 跳转链接，可以是路由路径或完整 URL
   * @example '/dashboard' 或 'https://example.com'
   */
  link?: string;
  query?: Record<string, any>;
  state?: Record<string, any>;
}

export type { NotificationItem };
