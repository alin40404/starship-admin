import type { AttachmentAcceptMode } from '#/components/attachment-field/types';

import { $t } from '@vben/locales';

import { getAdminRuntimeSnapshot } from '#/utils/admin-runtime-config';

export {
  ADMIN_UPLOAD_ALLOWED_EXTENSIONS_DEFAULT,
  ADMIN_UPLOAD_MAX_SIZE_MB_DEFAULT,
} from '#/utils/admin-runtime-config';

const IMAGE_EXTENSIONS = new Set([
  'bmp',
  'gif',
  'ico',
  'jpeg',
  'jpg',
  'png',
  'svg',
  'webp',
]);

export function getUploadMaxSizeMbSync(): number {
  return getAdminRuntimeSnapshot().uploadMaxSizeMb;
}

export function getUploadAllowedExtensionsSync(): string[] {
  return getAdminRuntimeSnapshot().uploadAllowedExtensions;
}

export function getFileExtension(fileName: string): string {
  return fileName.split('.').pop()?.toLowerCase().trim() ?? '';
}

export function validateUploadFile(
  file: File,
  mode: AttachmentAcceptMode = 'all',
): string | null {
  const maxMb = getUploadMaxSizeMbSync();
  const sizeMb = file.size / 1024 / 1024;
  if (sizeMb > maxMb) {
    return $t('ui.formRules.sizeLimit', [maxMb]);
  }

  const ext = getFileExtension(file.name);
  const allowed = getUploadAllowedExtensionsSync();

  if (allowed.length > 0) {
    if (!ext || !allowed.includes(ext)) {
      return $t('resource.attachment.field.fileTypeNotAllowed');
    }
  }

  if (mode === 'image' && (!ext || !IMAGE_EXTENSIONS.has(ext))) {
    return $t('resource.attachment.field.imageTypeRequired');
  }

  return null;
}

export function resolveUploadAcceptAttr(
  mode: AttachmentAcceptMode = 'all',
  explicitAccept?: string,
): string | undefined {
  if (explicitAccept) {
    return explicitAccept;
  }

  const allowed = getUploadAllowedExtensionsSync();

  if (mode === 'image') {
    const imageAllowed = allowed.filter((ext) => IMAGE_EXTENSIONS.has(ext));
    if (imageAllowed.length > 0) {
      return imageAllowed.map((ext) => `.${ext}`).join(',');
    }
    return 'image/*';
  }

  if (allowed.length > 0) {
    return allowed.map((ext) => `.${ext}`).join(',');
  }

  return undefined;
}
