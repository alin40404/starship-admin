import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { CategoryTreeNode, CategoryTypeOption } from '#/features/system/api/category';
import {
  fetchCategoryOptionsTree,
  mapCategoryForTreeSelect,
} from '#/features/system/api/category';
import { CATEGORY_PERMISSIONS } from '#/features/system/constants/category-permissions';
import { $t } from '#/locales';

import {
  DICT_TYPE_CODE,
  mapDictItemOptions,
} from '#/features/system/views/platform/dict/i18n';

export type CategoryAccessChecker = {
  can: (code: string) => boolean;
};

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('platform.category.searchPlaceholder'),
      },
      fieldName: 'search',
      label: $t('platform.category.keyword'),
    },
  ];
}

export function buildFormSchema(params: {
  excludeId?: number;
  onImageChange?: (event: unknown) => void;
  onImageUpload?: (options: unknown) => Promise<void> | void;
  onTypeChange?: (value: string) => void;
  typeOptions: CategoryTypeOption[];
}): VbenFormSchema[] {
  const { excludeId, onTypeChange, typeOptions } = params;

  return [
    {
      component: 'Select',
      componentProps: {
        class: 'w-full',
        onChange: (value: string) => onTypeChange?.(value),
        options: mapDictItemOptions(DICT_TYPE_CODE.categoryType, typeOptions),
        placeholder: $t('platform.category.typePlaceholder'),
      },
      fieldName: 'type',
      label: $t('platform.category.columnType'),
      rules: 'required',
    },
    {
      component: 'ApiTreeSelect',
      componentProps: {
        allowClear: true,
        api: async (queryParams?: { excludeId?: number; typeKey?: string }) =>
          loadCategoryTreeForParent(
            queryParams?.typeKey || undefined,
            queryParams?.excludeId,
          ),
        class: 'w-full',
        fieldNames: { children: 'children', label: 'label', value: 'value' },
        params: {
          excludeId,
          typeKey: '',
        },
        placeholder: $t('platform.category.parentPlaceholder'),
        shouldFetch: (queryParams?: { typeKey?: string }) =>
          Boolean(queryParams?.typeKey),
        showSearch: true,
        treeDefaultExpandAll: true,
        treeIcon: false,
        treeLine: false,
      },
      dependencies: {
        componentProps(values) {
          const type = values.type as string | undefined;
          return {
            disabled: !type,
            params: {
              excludeId,
              typeKey: type ?? '',
            },
            placeholder: type
              ? $t('platform.category.parentPlaceholder')
              : $t('platform.category.selectTypeFirst'),
          };
        },
        triggerFields: ['type'],
      },
      defaultValue: 0,
      fieldName: 'parent_id',
      label: $t('platform.category.parent'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('platform.category.namePlaceholder'),
      },
      fieldName: 'name',
      label: $t('platform.category.name'),
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('platform.category.slugPlaceholder'),
      },
      fieldName: 'slug',
      label: $t('platform.category.slug'),
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'keywords',
      label: $t('platform.category.keywords'),
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 0 },
      defaultValue: 0,
      fieldName: 'sort',
      label: $t('platform.category.sort'),
    },
    {
      component: 'PictureUploadField',
      componentProps: {
        customRequest: params.onImageUpload,
        handleChange: params.onImageChange,
        maxCount: 1,
      },
      fieldName: 'image',
      formItemClass: 'picture-upload-form-item items-start',
      label: $t('platform.category.image'),
    },
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full',
        optionType: 'button',
        options: [
          { label: $t('system.shared.status.normal'), value: 'normal' },
          { label: $t('system.shared.status.hidden'), value: 'hidden' },
        ],
      },
      defaultValue: 'normal',
      fieldName: 'status',
      formItemClass: 'self-start',
      label: $t('platform.category.status'),
    },
    {
      component: 'Textarea',
      componentProps: { rows: 2 },
      fieldName: 'description',
      formItemClass: 'col-span-full items-start',
      label: $t('platform.category.description'),
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<CategoryTreeNode>,
  onStatusBeforeChange: (
    newVal: CategoryTreeNode['status'],
    row: CategoryTreeNode,
  ) => Promise<boolean>,
  access: CategoryAccessChecker,
  options: {
    showTypeColumn?: boolean;
    typeTagOptions?: Array<{ label: string; value: string }>;
  } = {},
): VxeTableGridColumns<CategoryTreeNode> {
  const { showTypeColumn = true, typeTagOptions = [] } = options;
  const canStatus = access.can(CATEGORY_PERMISSIONS.STATUS);
  const canSort = access.can(CATEGORY_PERMISSIONS.SORT);

  const columns: VxeTableGridColumns<CategoryTreeNode> = [
    {
      align: 'center',
      fixed: 'left',
      type: 'checkbox',
      width: 48,
    },
    { field: 'id', title: 'ID', width: 72 },
  ];

  if (showTypeColumn) {
    columns.push({
      align: 'center',
      cellRender: {
        name: 'CellTag',
        options: typeTagOptions,
      },
      field: 'type',
      title: $t('platform.category.type'),
      width: 100,
    });
  }

  columns.push(
    {
      align: 'left',
      field: 'name',
      minWidth: 180,
      title: $t('platform.category.name'),
      treeNode: true,
    },
    {
      align: 'left',
      field: 'slug',
      minWidth: 120,
      title: $t('platform.category.slug'),
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          getSrc: (row: CategoryTreeNode) => row.image,
        },
        name: 'CellImage',
        props: { height: 40, width: 40 },
      },
      field: 'image',
      title: $t('platform.category.image'),
      width: 80,
    },
    {
      align: 'center',
      className: canSort ? 'category-sort-editable-col' : undefined,
      editRender: canSort
        ? {
            name: 'VxeNumberInput',
            props: {
              min: 0,
              type: 'integer',
            },
            events: {
              keydown(params, event: KeyboardEvent) {
                if (event.key !== 'Enter') {
                  return;
                }
                event.preventDefault();
                (event.target as HTMLElement | null)?.blur?.();
                void params.$table.clearEdit();
              },
            },
          }
        : undefined,
      field: 'sort',
      title: $t('platform.category.sort'),
      width: 100,
    },
    {
      field: 'status',
      title: $t('platform.category.status'),
      width: 100,
      cellRender: {
        attrs: {
          async beforeChange(newVal: unknown, row: CategoryTreeNode) {
            return onStatusBeforeChange(
              newVal as CategoryTreeNode['status'],
              row,
            );
          },
          getSwitchProps: () => ({
            checkedChildren: $t('system.shared.status.normal'),
            checkedValue: 'normal',
            disabled: !canStatus,
            unCheckedChildren: $t('system.shared.status.hidden'),
            unCheckedValue: 'hidden',
          }),
        },
        name: 'CellSwitch',
      },
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: $t('platform.category.entity'),
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'append',
            show: () => access.can(CATEGORY_PERMISSIONS.APPEND),
            text: $t('system.shared.appendChild'),
            type: 'link',
          },
          {
            code: 'edit',
            show: () => access.can(CATEGORY_PERMISSIONS.UPDATE),
            text: $t('common.edit'),
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(CATEGORY_PERMISSIONS.DELETE),
            text: $t('common.delete'),
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 220,
    },
  );

  return columns;
}

export async function loadCategoryTreeForParent(
  type?: string,
  excludeId?: number,
): Promise<ReturnType<typeof mapCategoryForTreeSelect>> {
  const tree = await fetchCategoryOptionsTree(type ? { type } : undefined);

  function filter(nodes: CategoryTreeNode[]): CategoryTreeNode[] {
    return nodes
      .filter((node) => node.id !== excludeId)
      .map((node) => ({
        ...node,
        children: node.children?.length ? filter(node.children) : [],
      }));
  }

  const filtered = excludeId ? filter(tree) : tree;

  return [
    { label: $t('system.shared.none'), value: 0 },
    ...mapCategoryForTreeSelect(filtered),
  ];
}
