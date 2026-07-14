export {
  extractFileNameFromUrl,
  isImageUrl,
  truncateFileName,
} from '#/components/attachment-field/utils';

/** 逗号分隔的多值字段拆分为 URL 列表 */
export function splitCsvValues(
  value: null | string | undefined,
): string[] {
  if (!value || typeof value !== 'string') {
    return [];
  }
  return value
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean);
}

/** 取逗号分隔字段的首个值（多图相册列表缩略图） */
export function firstCsvValue(value: null | string | undefined): string {
  return splitCsvValues(value)[0] ?? '';
}

/** 除首张外剩余数量，用于列表 +N 角标 */
export function csvExtraCount(value: null | string | undefined): number {
  const count = splitCsvValues(value).length;
  return count > 1 ? count - 1 : 0;
}

/** 富文本/HTML 去标签摘要，用于列表展示 */
export function stripHtmlSummary(
  html: null | string | undefined,
  maxLen = 48,
): string {
  if (!html || typeof html !== 'string') {
    return '';
  }
  const text = html
    .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
    .replace(/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/gi, '')
    .replace(/<[^>]+>/g, ' ')
    .replace(/&nbsp;/gi, ' ')
    .replace(/\s+/g, ' ')
    .trim();
  if (!text) {
    return '';
  }
  if (text.length <= maxLen) {
    return text;
  }
  return `${text.slice(0, maxLen)}…`;
}
