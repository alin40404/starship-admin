import dayjs from 'dayjs';
import customParseFormat from 'dayjs/plugin/customParseFormat.js';
import relativeTime from 'dayjs/plugin/relativeTime';
import timezone from 'dayjs/plugin/timezone.js';
import utc from 'dayjs/plugin/utc.js';

dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.extend(customParseFormat);
dayjs.extend(relativeTime);

type FormatDate = Date | dayjs.Dayjs | number | string;

type Format =
  | 'HH'
  | 'HH:mm'
  | 'HH:mm:ss'
  | 'YYYY'
  | 'YYYY-MM'
  | 'YYYY-MM-DD'
  | 'YYYY-MM-DD HH'
  | 'YYYY-MM-DD HH:mm'
  | 'YYYY-MM-DD HH:mm:ss'
  | (string & {});

const NAIVE_DATE_TIME_RE =
  /^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}(?::\d{2}(?:\.\d{1,6})?)?)?$/;
const EXPLICIT_OFFSET_RE = /(?:[zZ]|[+-]\d{2}:?\d{2})$/;

/**
 * 解析接口时间：带 Z/偏移的按绝对时间；无时区的 `Y-m-d[ H:i:s]` 按约定视为 UTC。
 */
export function parseApiTime(time?: FormatDate): dayjs.Dayjs {
  if (time === undefined || time === null || time === '') {
    return dayjs(Number.NaN);
  }

  if (dayjs.isDayjs(time)) {
    return time;
  }

  if (typeof time === 'number' || time instanceof Date) {
    return dayjs(time);
  }

  if (typeof time === 'string') {
    const trimmed = time.trim();
    if (!trimmed) {
      return dayjs(Number.NaN);
    }

    if (trimmed.includes('T') || EXPLICIT_OFFSET_RE.test(trimmed)) {
      return dayjs(trimmed);
    }

    if (NAIVE_DATE_TIME_RE.test(trimmed)) {
      if (/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) {
        return dayjs.utc(trimmed);
      }
      return dayjs.utc(trimmed.replace(' ', 'T'));
    }

    return dayjs(trimmed);
  }

  return dayjs(time as string);
}

export function formatDate(time?: FormatDate, format: Format = 'YYYY-MM-DD') {
  try {
    const date = parseApiTime(time);
    if (!date.isValid()) {
      throw new Error('Invalid date');
    }
    return date.tz(getCurrentTimezone()).format(format);
  } catch (error) {
    console.error(`Error formatting date: ${error}`);
    return String(time ?? '');
  }
}

export function formatDateTime(time?: FormatDate) {
  return formatDate(time, 'YYYY-MM-DD HH:mm:ss');
}

/**
 * 日历日字段（date）：API 常为 `YYYY-MM-DD` 或 ISO 午夜。
 * 只取日期部分，不做展示时区换算，避免跨时区把「日」拨到前一天。
 */
export function formatDateOnly(time?: FormatDate) {
  try {
    if (typeof time === 'string') {
      const matched = time.trim().match(/^(\d{4}-\d{2}-\d{2})/);
      if (matched?.[1]) {
        return matched[1];
      }
    }
    const date = parseApiTime(time);
    if (!date.isValid()) {
      throw new Error('Invalid date');
    }
    return date.utc().format('YYYY-MM-DD');
  } catch (error) {
    console.error(`Error formatting date-only: ${error}`);
    return String(time ?? '');
  }
}

/**
 * 展示时区墙钟字符串 → UTC ISO（DatePicker 提交给后端用）。
 */
export function displayWallClockToUtcIso(
  time?: null | string,
  format: Format = 'YYYY-MM-DD HH:mm:ss',
): string {
  if (!time?.trim()) {
    return '';
  }
  const date = dayjs.tz(time.trim(), format, getCurrentTimezone());
  return date.isValid() ? date.utc().toISOString() : time;
}

export function formatRelativeTime(time?: FormatDate, locale = 'zh-cn') {
  try {
    const date = parseApiTime(time);
    if (!date.isValid()) {
      throw new Error('Invalid date');
    }
    return date.locale(locale).fromNow();
  } catch (error) {
    console.error(`Error formatting relative time: ${error}`);
    return formatDateTime(time);
  }
}

export function isDate(value: any): value is Date {
  return value instanceof Date;
}

export function isDayjsObject(value: any): value is dayjs.Dayjs {
  return dayjs.isDayjs(value);
}

/**
 * 获取当前时区
 * @returns 当前时区
 */
export const getSystemTimezone = () => {
  return dayjs.tz.guess();
};

/**
 * 自定义设置的时区
 */
let currentTimezone = getSystemTimezone();

/**
 * 设置默认时区
 * @param timezone
 */
export const setCurrentTimezone = (timezone?: string) => {
  currentTimezone = timezone || getSystemTimezone();
  dayjs.tz.setDefault(currentTimezone);
};

/**
 * 获取设置的时区
 * @returns 设置的时区
 */
export const getCurrentTimezone = () => {
  return currentTimezone;
};
