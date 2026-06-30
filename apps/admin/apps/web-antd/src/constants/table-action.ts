/**
 * 表格工具栏按钮、操作列 link 按钮的文字大小（px）。
 * 通过 CSS 变量覆盖，不修改 Ant Design Vue 全局 Design Token。
 */
export const TABLE_ACTION_BUTTON_FONT_SIZE = 13;

const TABLE_ACTION_BUTTON_FONT_SIZE_VAR =
  '--vben-table-action-button-font-size';

/** 将 {@link TABLE_ACTION_BUTTON_FONT_SIZE} 写入文档 CSS 变量 */
export function applyTableActionButtonFontSize(
  doc: Document = document,
): void {
  doc.documentElement.style.setProperty(
    TABLE_ACTION_BUTTON_FONT_SIZE_VAR,
    `${TABLE_ACTION_BUTTON_FONT_SIZE}px`,
  );
}
