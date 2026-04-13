import type { VxeTableGridOptions } from '@vben/plugins/vxe-table';

import type { ComponentPropsMap, ComponentType } from './component';

import type { Recordable } from '@vben/types';

import { h } from 'vue';

import {
  setupVbenVxeTable,
  useVbenVxeGrid as useGrid,
} from '@vben/plugins/vxe-table';

import { get } from '@vben/utils';

import { ElButton, ElImage, ElTag } from 'element-plus';

import { useVbenForm } from './form';

setupVbenVxeTable({
  configVxeTable: (vxeUI) => {
    vxeUI.renderer.forEach((_item, key) => {
      if (key.startsWith('Cell')) {
        vxeUI.renderer.delete(key);
      }
    });

    vxeUI.setConfig({
      grid: {
        align: 'center',
        border: false,
        columnConfig: {
          resizable: true,
        },
        minHeight: 180,
        formConfig: {
          // 全局禁用vxe-table的表单配置，使用formOptions
          enabled: false,
        },
        proxyConfig: {
          autoLoad: true,
          response: {
            result: 'items',
            total: 'total',
            list: 'items',
          },
          showActiveMsg: true,
          showResponseMsg: false,
        },
        round: true,
        showOverflow: true,
        size: 'small',
      } as VxeTableGridOptions,
    });

    // 表格配置项可以用 cellRender: { name: 'CellImage' },
    vxeUI.renderer.add('CellImage', {
      renderTableDefault(renderOpts, params) {
        const { props } = renderOpts;
        const { column, row } = params;
        const src = row[column.field];
        return h(ElImage, { src, previewSrcList: [src], ...props });
      },
    });

    // 表格配置项可以用 cellRender: { name: 'CellLink' },
    vxeUI.renderer.add('CellLink', {
      renderTableDefault(renderOpts) {
        const { props } = renderOpts;
        return h(
          ElButton,
          { size: 'small', link: true },
          { default: () => props?.text },
        );
      },
    });

    vxeUI.renderer.add('CellTag', {
      renderTableDefault({ options, props }, { column, row }) {
        const value = get(row, column.field);
        const tagOptions = options ?? [
          { type: 'success', label: '启用', value: 1 },
          { type: 'info', label: '禁用', value: 0 },
        ];
        const tagItem = tagOptions.find(
          (item: Recordable<any>) => item.value === value,
        );
        return h(
          ElTag,
          {
            size: 'small',
            type: (tagItem?.type as 'success' | 'info' | 'warning' | 'danger') ?? 'info',
            ...props,
          },
          { default: () => tagItem?.label ?? String(value ?? '') },
        );
      },
    });

    vxeUI.renderer.add('CellOperation', {
      renderTableDefault({ attrs, options, props }, { column, row }) {
        const defaultProps = { size: 'small', link: true, ...props };
        let justify = 'flex-end';
        switch (column.align) {
          case 'center': {
            justify = 'center';
            break;
          }
          case 'left': {
            justify = 'flex-start';
            break;
          }
          default: {
            justify = 'flex-end';
          }
        }
        const presets: Recordable<Recordable<any>> = {
          delete: { code: 'delete', text: '删除', type: 'danger' },
          edit: { code: 'edit', text: '编辑', type: 'primary' },
        };
        const operations: Array<Recordable<any>> = (options || ['edit', 'delete'])
          .map((opt: string | Recordable<any>) => {
            if (typeof opt === 'string') {
              return presets[opt]
                ? { ...presets[opt], ...defaultProps }
                : { code: opt, text: opt, type: 'primary', ...defaultProps };
            }
            return { ...defaultProps, ...presets[opt.code], ...opt };
          })
          .filter((opt: Recordable<any>) => opt.show !== false);

        return h(
          'div',
          {
            class: 'flex flex-wrap items-center gap-1',
            style: { justifyContent: justify },
          },
          operations.map((opt) =>
            h(
              ElButton,
              {
                ...opt,
                onClick: () =>
                  attrs?.onClick?.({
                    code: opt.code,
                    row,
                  }),
              },
              { default: () => opt.text },
            ),
          ),
        );
      },
    });

    // 这里可以自行扩展 vxe-table 的全局配置，比如自定义格式化
    // vxeUI.formats.add
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
