<script lang="ts" setup>
import type { ConfigRow } from '#/api/system/config';

import { computed, ref } from 'vue';

import { useVbenDrawer } from '@vben/common-ui';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { batchUpsertConfigs } from '#/api/system/config';

const PARAM_GROUP = 'param';

const emits = defineEmits<{ success: [] }>();

const editingKey = ref<string | null>(null);

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: [
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      dependencies: {
        componentProps(values) {
          return { disabled: !!values._isEdit };
        },
        triggerFields: ['_isEdit'],
      },
      fieldName: 'key',
      label: '参数键',
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'name',
      label: '参数名称',
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'value',
      label: '参数值',
    },
    {
      component: 'Select',
      componentProps: {
        class: 'w-full',
        options: [
          { label: '字符串', value: 'string' },
          { label: '数字', value: 'number' },
          { label: '布尔', value: 'bool' },
          { label: 'JSON', value: 'json' },
        ],
      },
      defaultValue: 'string',
      fieldName: 'type',
      label: '值类型',
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 0 },
      defaultValue: 0,
      fieldName: 'sort',
      label: '排序',
    },
    {
      component: 'Textarea',
      componentProps: { class: 'w-full', rows: 2 },
      fieldName: 'remark',
      formItemClass: 'col-span-full w-full items-start',
      label: '备注',
    },
    {
      component: 'Input',
      dependencies: { show: () => false, triggerFields: [] },
      fieldName: '_isEdit',
    },
  ],
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
});

const drawerTitle = computed(() =>
  editingKey.value ? '编辑参数' : '新增参数',
);

const [Drawer, drawerApi] = useVbenDrawer({
  async onConfirm() {
    const { valid } = await formApi.validate();
    if (!valid) return;

    const values = await formApi.getValues();
    drawerApi.lock();
    try {
      await batchUpsertConfigs([
        {
          group_name: PARAM_GROUP,
          key: values.key as string,
          name: values.name as string,
          remark: (values.remark as string) || null,
          sort: Number(values.sort ?? 0),
          type: values.type as string,
          value: (values.value as string) ?? '',
        },
      ]);
      message.success('已保存');
      drawerApi.close();
      emits('success');
    } finally {
      drawerApi.unlock();
    }
  },
  onOpenChange(isOpen: boolean) {
    if (!isOpen) return;
    formApi.resetForm();
    const data = drawerApi.getData<ConfigRow | Record<string, never>>();
    const row = data as ConfigRow;
    if (row?.key) {
      editingKey.value = row.key;
      formApi.setValues({
        _isEdit: true,
        key: row.key,
        name: row.name,
        remark: row.remark ?? '',
        sort: row.sort ?? 0,
        type: row.type ?? 'string',
        value: row.value ?? '',
      });
    } else {
      editingKey.value = null;
      formApi.setValues({
        _isEdit: false,
        key: '',
        name: '',
        remark: '',
        sort: 0,
        type: 'string',
        value: '',
      });
    }
  },
});
</script>

<template>
  <Drawer :title="drawerTitle" class="max-w-[560px] w-[92vw]">
    <Form />
  </Drawer>
</template>
