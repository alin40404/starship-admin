import type { AttachmentRow } from '#/api/system/attachment';
import { isImageAttachment } from '#/views/resource/attachment/data';

import type { AttachmentAcceptMode } from './types';

export function resolveUploadAccept(accept: AttachmentAcceptMode = 'all') {
  return accept === 'image' ? 'image/*' : undefined;
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
