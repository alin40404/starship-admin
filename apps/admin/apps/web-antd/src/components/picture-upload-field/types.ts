import type { UploadChangeParam, UploadFile, UploadProps } from 'ant-design-vue/es/upload';

import type { AttachmentAcceptMode } from '#/components/attachment-field/types';

export interface PictureUploadFieldProps {
  /** 默认识别为图片；与附件选择器、上传校验一致 */
  accept?: AttachmentAcceptMode;
  customRequest?: UploadProps['customRequest'];
  disabled?: boolean;
  fileList?: UploadFile[];
  handleChange?: (event: UploadChangeParam) => void;
  /** 不传则不限制张数 */
  maxCount?: number;
}
