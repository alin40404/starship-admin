import type { VxeTableGridOptions } from '@vben/plugins/vxe-table';
import type { Recordable } from '@vben/types';

import type { ComponentPropsMap, ComponentType } from './component';

import { h } from 'vue';

import { IconifyIcon } from '@vben/icons';
import { $t, $te } from '@vben/locales';
import {
  setupVbenVxeTable,
  useVbenVxeGrid as useGrid,
} from '@vben/plugins/vxe-table';
import { get, isFunction, isString } from '@vben/utils';

import { objectOmit } from '@vueuse/core';
import { Button, Image, Popconfirm, Switch, Tag } from 'ant-design-vue';

import {
  extractFileNameFromUrl,
  isImageUrl,
  truncateFileName,
} from '#/components/attachment-field/utils';
import { csvExtraCount, firstCsvValue } from '#/utils/list-cell';
import { getAdminPageSizeSync } from '#/utils/admin-runtime-config';

import { useVbenForm } from './form';

setupVbenVxeTable({
  configVxeTable: (vxeUI) => {
    vxeUI.setConfig({
      grid: {
        align: 'center',
        border: false,
        columnConfig: {
          resizable: true,
        },
        minHeight: 180,
        formConfig: {
          enabled: false,
        },
        proxyConfig: {
          autoLoad: true,
          response: {
            result: 'data',
            total: 'total',
            list: 'data',
          },
          showActiveMsg: true,
          showResponseMsg: false,
        },
        round: true,
        showOverflow: true,
        size: 'small',
        pagerConfig: {
          pageSize: getAdminPageSizeSync(),
        },
      } as VxeTableGridOptions,
    });

    vxeUI.renderer.forEach((_item, key) => {
      if (key.startsWith('Cell')) {
        vxeUI.renderer.delete(key);
      }
    });

    vxeUI.renderer.add('CellImage', {
      renderTableDefault(renderOpts, params) {
        const { attrs, props } = renderOpts;
        const { column, row } = params;
        const src =
          typeof attrs?.getSrc === 'function'
            ? attrs.getSrc(row, column)
            : row[column.field];
        const extraCount =
          typeof attrs?.getExtraCount === 'function'
            ? Number(attrs.getExtraCount(row, column) ?? 0)
            : 0;
        const width = props?.width ?? 40;
        const height = props?.height ?? 40;
        const fileIcon =
          typeof attrs?.getFileIcon === 'function'
            ? attrs.getFileIcon(row, column)
            : '';
        const fileIconClass =
          typeof attrs?.getFileIconClass === 'function'
            ? attrs.getFileIconClass(row, column)
            : 'text-muted-foreground';
        const imageNode = src
          ? h(Image, {
              ...props,
              height,
              preview: props?.preview,
              src,
              style: {
                maxHeight: '100%',
                maxWidth: '100%',
                objectFit: 'contain',
                ...(typeof props?.style === 'object' ? props.style : {}),
              },
              width,
            })
          : fileIcon
            ? h(IconifyIcon, {
                class: `size-5 ${fileIconClass}`,
                icon: fileIcon,
              })
            : undefined;

        if (!imageNode) {
          return h('span', { class: 'text-muted-foreground' }, '--');
        }

        return h(
          'div',
          {
            class: 'inline-flex items-center gap-1',
          },
          [
            h(
              'div',
              {
                class: 'inline-flex shrink-0 items-center justify-center',
                style: {
                  height: `${height}px`,
                  width: `${width}px`,
                },
              },
              imageNode,
            ),
            extraCount > 0
              ? h(
                  'span',
                  {
                    class: 'whitespace-nowrap text-xs',
                    style: { color: 'var(--ant-blue-6, #1677ff)' },
                  },
                  `+${extraCount}`,
                )
              : null,
          ],
        );
      },
    });

    vxeUI.renderer.add('CellIcon', {
      renderTableDefault(renderOpts, params) {
        const { attrs, props } = renderOpts;
        const { column, row } = params;
        const icon =
          typeof attrs?.getIcon === 'function'
            ? attrs.getIcon(row, column)
            : row[column.field];
        if (!icon) {
          return h('span', { class: 'text-muted-foreground' }, '--');
        }
        return h(
          'div',
          { class: 'inline-flex items-center justify-center' },
          h(IconifyIcon, {
            class: props?.class ?? 'size-5',
            icon: String(icon),
          }),
        );
      },
    });

    vxeUI.renderer.add('CellFile', {
      renderTableDefault(renderOpts, params) {
        const { attrs, props } = renderOpts;
        const { column, row } = params;
        const rawValue =
          typeof attrs?.getUrl === 'function'
            ? attrs.getUrl(row, column)
            : row[column.field];
        if (!rawValue) {
          return h('span', { class: 'text-muted-foreground' }, '--');
        }
        const rawText = String(rawValue);
        const urlText = firstCsvValue(rawText);
        if (!urlText) {
          return h('span', { class: 'text-muted-foreground' }, '--');
        }
        const extraCount =
          typeof attrs?.getExtraCount === 'function'
            ? Number(attrs.getExtraCount(row, column) ?? 0)
            : csvExtraCount(rawText);
        const fileName =
          typeof attrs?.getFileName === 'function'
            ? attrs.getFileName(row, column)
            : extractFileNameFromUrl(urlText);
        const displayName = truncateFileName(
          fileName,
          props?.maxFileNameLength ?? 20,
        );
        const width = props?.width ?? 40;
        const height = props?.height ?? 40;

        const fileNode = isImageUrl(urlText)
          ? h(
              'div',
              {
                class: 'inline-flex shrink-0 items-center justify-center',
                style: {
                  height: `${height}px`,
                  width: `${width}px`,
                },
              },
              h(Image, {
                height,
                preview: props?.preview ?? true,
                src: urlText,
                style: {
                  maxHeight: '100%',
                  maxWidth: '100%',
                  objectFit: 'contain',
                },
                width,
              }),
            )
          : h(
              'a',
              {
                class:
                  'inline-flex max-w-full items-center gap-1 text-primary hover:underline',
                href: urlText,
                rel: 'noopener noreferrer',
                target: '_blank',
                title: fileName,
                onClick: (event: MouseEvent) => event.stopPropagation(),
              },
              [
                h(IconifyIcon, {
                  class: 'size-4 shrink-0',
                  icon: props?.fileIcon ?? 'mdi:paperclip',
                }),
                h('span', { class: 'truncate' }, displayName),
              ],
            );

        if (extraCount <= 0) {
          return fileNode;
        }

        return h(
          'div',
          {
            class: 'inline-flex max-w-full items-center gap-1',
          },
          [
            fileNode,
            h(
              'span',
              {
                class: 'shrink-0 whitespace-nowrap text-xs',
                style: { color: 'var(--ant-blue-6, #1677ff)' },
              },
              `+${extraCount}`,
            ),
          ],
        );
      },
    });

    vxeUI.renderer.add('CellLink', {
      renderTableDefault(renderOpts) {
        const { props } = renderOpts;
        return h(
          Button,
          { size: 'small', type: 'link' },
          { default: () => props?.text },
        );
      },
    });

    vxeUI.renderer.add('CellActionText', {
      renderTableDefault({ attrs, props }, { column, row }) {
        const value = get(row, column.field);
        if (value === null || value === undefined || value === '') {
          return h('span', { class: 'text-muted-foreground' }, '--');
        }
        const label =
          typeof attrs?.getLabel === 'function'
            ? attrs.getLabel(row, column)
            : String(value);
        return h(
          Button,
          {
            class: 'cell-action-text h-auto max-w-full p-0 text-left whitespace-normal',
            size: 'small',
            type: 'link',
            ...props,
            onClick: () =>
              attrs?.onClick?.({
                code: attrs?.code ?? 'detail',
                row,
              }),
          },
          { default: () => label },
        );
      },
    });

    vxeUI.renderer.add('CellTag', {
      renderTableDefault({ options, props, attrs }, { column, row }) {
        const value = get(row, column.field);
        if (value === null || value === undefined || value === '') {
          return h('span', { class: 'text-muted-foreground' }, '--');
        }
        const tagOptions = options ?? [
          { color: 'success', label: $t('common.enabled'), value: 1 },
          { color: 'error', label: $t('common.disabled'), value: 0 },
        ];
        const tagItem = tagOptions.find((item) => item.value === value);
        const label =
          typeof attrs?.getLabel === 'function'
            ? attrs.getLabel(row, column)
            : (tagItem?.label ?? value);
        const color =
          typeof attrs?.getColor === 'function'
            ? attrs.getColor(row, column)
            : (tagItem?.color ?? props?.color ?? 'default');
        return h(
          Tag,
          {
            ...props,
            color,
            ...objectOmit(tagItem ?? {}, ['label', 'color']),
          },
          { default: () => label },
        );
      },
    });

    vxeUI.renderer.add('CellSwitch', {
      renderTableDefault({ attrs, props }, { column, row }) {
        const loadingKey = `__loading_${column.field}`;
        const rowSwitchProps =
          typeof attrs?.getSwitchProps === 'function'
            ? attrs.getSwitchProps(row)
            : {};
        const finallyProps = {
          checkedChildren: $t('common.enabled'),
          checkedValue: 1,
          unCheckedChildren: $t('common.disabled'),
          unCheckedValue: 0,
          ...props,
          ...rowSwitchProps,
          checked: row[column.field],
          loading: row[loadingKey] ?? false,
          'onUpdate:checked': onChange,
        };
        async function onChange(newVal: unknown) {
          row[loadingKey] = true;
          try {
            const result = await attrs?.beforeChange?.(newVal, row);
            if (result !== false) {
              row[column.field] = newVal;
            }
          } finally {
            row[loadingKey] = false;
          }
        }
        return h(Switch, finallyProps);
      },
    });

    vxeUI.renderer.add('CellOperation', {
      renderTableDefault({ attrs, options, props }, { column, row }) {
        const defaultProps = { size: 'small', type: 'link', ...props };
        let align: string;
        switch (column.align) {
          case 'center': {
            align = 'center';
            break;
          }
          case 'left': {
            align = 'start';
            break;
          }
          default: {
            align = 'end';
            break;
          }
        }
        const presets: Recordable<Recordable<any>> = {
          delete: {
            danger: true,
            text: $t('common.delete'),
          },
          edit: {
            text: $t('common.edit'),
          },
        };
        const operations: Array<Recordable<any>> = (
          options || ['edit', 'delete']
        )
          .map((opt) => {
            if (isString(opt)) {
              return presets[opt]
                ? { code: opt, ...presets[opt], ...defaultProps }
                : {
                    code: opt,
                    text: $te(`common.${opt}`) ? $t(`common.${opt}`) : opt,
                    ...defaultProps,
                  };
            } else {
              return { ...defaultProps, ...presets[opt.code], ...opt };
            }
          })
          .map((opt) => {
            const optBtn: Recordable<any> = {};
            Object.keys(opt).forEach((key) => {
              optBtn[key] = isFunction(opt[key]) ? opt[key](row) : opt[key];
            });
            return optBtn;
          })
          .filter((opt) => opt.show !== false);

        function renderBtn(opt: Recordable<any>, listen = true) {
          return h(
            Button,
            {
              ...props,
              ...opt,
              icon: undefined,
              onClick: listen
                ? () =>
                    attrs?.onClick?.({
                      code: opt.code,
                      row,
                    })
                : undefined,
            },
            {
              default: () => {
                const content: unknown[] = [];
                if (opt.icon) {
                  content.push(
                    h(IconifyIcon, { class: 'size-5', icon: opt.icon }),
                  );
                }
                content.push(opt.text);
                return content;
              },
            },
          );
        }

        function renderConfirm(opt: Recordable<any>) {
          let viewportWrapper: HTMLElement | null = null;
          return h(
            Popconfirm,
            {
              getPopupContainer(el) {
                viewportWrapper = el.closest('.vxe-table--viewport-wrapper');
                return document.body;
              },
              placement: 'topLeft',
              title: $t('ui.actionTitle.delete', [attrs?.nameTitle || '']),
              ...props,
              ...opt,
              icon: undefined,
              onOpenChange: (open: boolean) => {
                if (open) {
                  viewportWrapper?.style.setProperty('pointer-events', 'none');
                } else {
                  viewportWrapper?.style.removeProperty('pointer-events');
                }
              },
              onConfirm: () => {
                attrs?.onClick?.({
                  code: opt.code,
                  row,
                });
              },
            },
            {
              default: () => renderBtn({ ...opt }, false),
              description: () =>
                h(
                  'div',
                  { class: 'truncate' },
                  $t('ui.actionMessage.deleteConfirm', [
                    row[attrs?.nameField || 'name'],
                  ]),
                ),
            },
          );
        }

        const btns = operations.map((opt) =>
          opt.code === 'delete' ? renderConfirm(opt) : renderBtn(opt),
        );
        return h(
          'div',
          {
            class: 'flex table-operations',
            style: { justifyContent: align },
          },
          btns,
        );
      },
    });
  },
  useVbenForm,
});

export const useVbenVxeGrid = <T extends Record<string, any>>(
  ...rest: Parameters<typeof useGrid<T, ComponentType, ComponentPropsMap>>
) => useGrid<T, ComponentType, ComponentPropsMap>(...rest);

export type OnActionClickParams<T = Recordable<any>> = {
  code: string;
  row: T;
};
export type OnActionClickFn<T = Recordable<any>> = (
  params: OnActionClickParams<T>,
) => void;

export type * from '@vben/plugins/vxe-table';
