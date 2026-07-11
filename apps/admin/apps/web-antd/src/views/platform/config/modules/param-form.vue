<script lang="ts" setup>
import type { ConfigRow } from '#/api/system/config';
import type { VbenFormSchema } from '#/adapter/form';

import { computed, ref } from 'vue';

import { useVbenModal } from '@vben/common-ui';
import { $t } from '@vben/locales';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { batchUpsertConfigs } from '#/api/system/config';

const PARAM_GROUP = 'param';

const emits = defineEmits<{ success: [] }>();

const editingKey = ref<string | null>(null);

function getParamFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'key',
      label: $t('system.platform.config.param.keyLabel'),
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'name',
      label: $t('system.platform.config.param.nameLabel'),
      rules: 'required',
    },
    {
      component: 'Select',
      componentProps: {
        class: 'w-full',
        options: [
          {
            label: $t('system.platform.config.paramType.string'),
            value: 'string',
          },
          {
            label: $t('system.platform.config.paramType.number'),
            value: 'number',
          },
          {
            label: $t('system.platform.config.paramType.bool'),
            value: 'bool',
          },
          {
            label: $t('system.platform.config.paramType.json'),
            value: 'json',
          },
        ],
      },
      defaultValue: 'string',
      fieldName: 'type',
      label: $t('system.platform.config.param.valueType'),
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'value',
      label: $t('system.platform.config.param.value'),
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 0 },
      defaultValue: 0,
      fieldName: 'sort',
      label: $t('system.platform.config.param.sort'),
    },
    {
      component: 'Textarea',
      componentProps: { class: 'w-full', rows: 2 },
      fieldName: 'remark',
      formItemClass: 'col-span-full w-full items-start',
      label: $t('system.platform.config.param.remark'),
    },
  ];
}

function buildValueFieldSchema(type: string): VbenFormSchema {
  if (type === 'json') {
    return {
      component: 'Textarea',
      componentProps: {
        class: 'w-full',
        placeholder: $t('system.platform.config.param.jsonPlaceholder'),
        rows: 4,
      },
      fieldName: 'value',
      formItemClass: 'col-span-full w-full items-start',
      label: $t('system.platform.config.param.value'),
    };
  }

  const placeholderMap: Record<string, string> = {
    bool: $t('system.platform.config.param.boolPlaceholder'),
    number: $t('system.platform.config.param.numberPlaceholder'),
    string: $t('system.platform.config.param.stringPlaceholder'),
  };

  return {
    component: 'Input',
    componentProps: {
      class: 'w-full',
      placeholder:
        placeholderMap[type] ?? $t('system.platform.config.param.stringPlaceholder'),
    },
    fieldName: 'value',
    label: $t('system.platform.config.param.value'),
  };
}

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: getParamFormSchema(),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
  handleValuesChange(values, fieldsChanged) {
    if (fieldsChanged.includes('type')) {
      syncValueFieldByType(String(values.type ?? 'string'));
    }
  },
});

function syncValueFieldByType(type: string) {
  const schema = [...(formApi.getState()?.schema ?? getParamFormSchema())];
  const index = schema.findIndex((item) => item.fieldName === 'value');
  if (index < 0) {
    return;
  }
  schema[index] = buildValueFieldSchema(type);
  formApi.setState({ schema });
}

const modalTitle = computed(() =>
  editingKey.value
    ? $t('system.platform.config.param.editParam')
    : $t('system.platform.config.param.createParam'),
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
      message.success($t('crud.saved'));
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
      syncValueFieldByType(row.type ?? 'string');
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
      syncValueFieldByType('string');
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
  <Modal :title="modalTitle" class="w-[560px]">
    <Form />
  </Modal>
</template>
