<script lang="ts" setup>
import type { ConfigRow } from '#/api/system/config';
import type { VbenFormSchema } from '#/adapter/form';

import { computed, ref } from 'vue';

import { useVbenModal } from '@vben/common-ui';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { batchUpsertConfigs } from '#/api/system/config';

const PARAM_GROUP = 'param';

const emits = defineEmits<{ success: [] }>();

const editingKey = ref<string | null>(null);

const PARAM_FORM_SCHEMA: VbenFormSchema[] = [
  {
    component: 'Input',
    componentProps: { class: 'w-full' },
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
];

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: PARAM_FORM_SCHEMA,
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
});

const modalTitle = computed(() =>
  editingKey.value ? '编辑参数' : '新增参数',
);

function syncKeyFieldDisabled(isEdit: boolean) {
  formApi.updateSchema([
    {
      componentProps: { class: 'w-full', disabled: isEdit },
      fieldName: 'key',
    },
  ]);
}

const [Modal, modalApi] = useVbenModal({
  fullscreenButton: false,
  async onConfirm() {
    const { valid } = await formApi.validate();
    if (!valid) return;

    const values = await formApi.getValues();
    modalApi.lock();
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
      modalApi.close();
      emits('success');
    } finally {
      modalApi.unlock();
    }
  },
  onOpenChange(isOpen: boolean) {
    if (!isOpen) return;
    formApi.resetForm();
    const data = modalApi.getData<ConfigRow | Record<string, never>>();
    const row = data as ConfigRow;
    if (row?.key) {
      editingKey.value = row.key;
      syncKeyFieldDisabled(true);
      formApi.setValues({
        key: row.key,
        name: row.name,
        remark: row.remark ?? '',
        sort: row.sort ?? 0,
        type: row.type ?? 'string',
        value: row.value ?? '',
      });
    } else {
      editingKey.value = null;
      syncKeyFieldDisabled(false);
      formApi.setValues({
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
  <Modal :title="modalTitle" class="w-[560px] max-w-[92vw]">
    <Form />
  </Modal>
</template>
