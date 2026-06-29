import { createIconifyIcon } from '@vben-core/icons';

export * from '@vben-core/icons';
export {
  fetchIconsData,
  ICONS_MAP,
  initOfflineIcons,
  listIcons,
} from '../offline';

export const MdiKeyboardEsc = createIconifyIcon('mdi:keyboard-esc');
