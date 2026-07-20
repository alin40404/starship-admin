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

const VIDEO_EXTENSIONS = new Set(['avi', 'm4v', 'mkv', 'mov', 'mp4', 'webm']);

export function getUploadMaxSizeMbSync(): number {
  return getAdminRuntimeSnapshot().uploadMaxSizeMb;
}

export function getUploadAllowedExtensionsSync(): string[] {
  return getAdminRuntimeSnapshot().uploadAllowedExtensions;
}

export function getFileExtension(fileName: string): string {
  return fileName.split('.').pop()?.toLowerCase().trim() ?? '';
}

export function isImageExtension(ext: string): boolean {
  return IMAGE_EXTENSIONS.has(ext);
}

export function isVideoExtension(ext: string): boolean {
  return VIDEO_EXTENSIONS.has(ext);
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

  if (mode === 'video' && (!ext || !VIDEO_EXTENSIONS.has(ext))) {
    return '仅支持视频文件';
  }

  if (
    mode === 'media' &&
    (!ext || (!IMAGE_EXTENSIONS.has(ext) && !VIDEO_EXTENSIONS.has(ext)))
  ) {
    return '仅支持图片或视频文件';
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

  if (mode === 'video') {
    const videoAllowed = allowed.filter((ext) => VIDEO_EXTENSIONS.has(ext));
    if (videoAllowed.length > 0) {
      return videoAllowed.map((ext) => `.${ext}`).join(',');
    }
    return 'video/*';
  }

  if (mode === 'media') {
    const mediaAllowed = allowed.filter(
      (ext) => IMAGE_EXTENSIONS.has(ext) || VIDEO_EXTENSIONS.has(ext),
    );
    if (mediaAllowed.length > 0) {
      return mediaAllowed.map((ext) => `.${ext}`).join(',');
    }
    return 'image/*,video/*';
  }

  if (allowed.length > 0) {
    return allowed.map((ext) => `.${ext}`).join(',');
  }

  return undefined;
}
