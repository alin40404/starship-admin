import type { AttachmentRow } from '#/api/system/attachment';
import { isImageAttachment } from '#/views/resource/attachment/data';

import type { AttachmentAcceptMode } from './types';

import { resolveUploadAcceptAttr } from '#/utils/upload-policy';

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

/** 根据 URL 后缀判断是否为图片资源 */
export function isImageUrl(url: string): boolean {
  const trimmed = url?.trim();
  if (!trimmed) {
    return false;
  }
  try {
    const pathname = new URL(trimmed, 'http://localhost').pathname;
    const ext = pathname.split('.').pop()?.toLowerCase();
    return ext ? IMAGE_EXTENSIONS.has(ext) : false;
  } catch {
    const ext = trimmed.split('?')[0]?.split('.').pop()?.toLowerCase();
    return ext ? IMAGE_EXTENSIONS.has(ext) : false;
  }
}

export function resolveUploadAccept(accept: AttachmentAcceptMode = 'all') {
  return resolveUploadAcceptAttr(accept);
}

export function resolveMimeFilter(accept: AttachmentAcceptMode = 'all') {
  return accept === 'image' ? 'image/' : undefined;
}

export function isAttachmentAcceptable(
  row: AttachmentRow,
  accept: AttachmentAcceptMode = 'all',
) {
  if (accept === 'image') {
    return isImageAttachment(row);
  }
  return true;
}

export function normalizeFieldValue(
  value: string | string[] | undefined | null,
  multiple: boolean,
): string | string[] {
  if (multiple) {
    if (Array.isArray(value)) {
      return value.filter(Boolean);
    }
    if (typeof value === 'string' && value.trim()) {
      return value
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean);
    }
    return [];
  }
  return typeof value === 'string' ? value : '';
}

export function valueToInputText(
  value: string | string[] | undefined | null,
  multiple: boolean,
) {
  const normalized = normalizeFieldValue(value, multiple);
  return Array.isArray(normalized) ? normalized.join(', ') : normalized;
}

export function parseInputText(text: string, multiple: boolean): string | string[] {
  if (multiple) {
    return text
      .split(',')
      .map((item) => item.trim())
      .filter(Boolean);
  }
  return text.trim();
}

export interface AttachmentFileMeta {
  name: string;
  size?: number;
}

/** 从 URL 路径提取文件名 */
export function extractFileNameFromUrl(url: string): string {
  const trimmed = url?.trim();
  if (!trimmed) {
    return 'file';
  }
  try {
    const pathname = new URL(trimmed, 'http://localhost').pathname;
    const segment = pathname.split('/').pop() ?? 'file';
    return decodeURIComponent(segment.split('?')[0] ?? segment) || 'file';
  } catch {
    const segment = trimmed.split('/').pop() ?? trimmed;
    return decodeURIComponent(segment.split('?')[0] ?? segment) || 'file';
  }
}

/** 格式化文件大小（与 wkcrm fileSize 一致） */
export function formatFileSize(value?: null | number | string): string {
  if (typeof value === 'string' && value) {
    return value;
  }
  if (value == null || value === '' || Number(value) === 0) {
    return '0 Bytes';
  }
  const unitArr = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
  const srcSize = Number(value);
  const index = Math.min(
    Math.floor(Math.log(srcSize) / Math.log(1024)),
    unitArr.length - 1,
  );
  const size = (srcSize / 1024 ** index).toFixed(2);
  return `${size}${unitArr[index]}`;
}

export function truncateFileName(name: string, maxLength = 24): string {
  if (name.length <= maxLength) {
    return name;
  }
  return `${name.slice(0, maxLength)}...`;
}

/** 列表展示：文件名(大小) */
export function formatAttachmentDisplayName(
  name: string,
  size?: number,
  maxLength = 24,
): string {
  const displayName = truncateFileName(name, maxLength);
  if (size == null || size === 0) {
    return displayName;
  }
  return `${displayName}(${formatFileSize(size)})`;
}
