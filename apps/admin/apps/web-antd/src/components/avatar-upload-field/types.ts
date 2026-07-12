import type { UploadChangeParam, UploadFile, UploadProps } from 'ant-design-vue/es/upload';

export interface AvatarUploadFieldProps {
  customRequest?: UploadProps['customRequest'];
  disabled?: boolean;
  fileList?: UploadFile[];
  handleChange?: (event: UploadChangeParam) => void;
}
