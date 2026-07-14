/** 附件字段可选类型：图片 / 任意资源 / 全部 */
export type AttachmentAcceptMode = 'all' | 'image' | 'resource';

export interface AttachmentFieldProps {
  accept?: AttachmentAcceptMode;
  disabled?: boolean;
  multiple?: boolean;
  placeholder?: string;
  value?: string | string[];
}
