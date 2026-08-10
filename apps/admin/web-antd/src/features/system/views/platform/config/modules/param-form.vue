<script lang="ts" setup>
import type { ConfigRow } from '#/features/system/api/config';
import type { VbenFormSchema } from '#/adapter/form';

import { computed, nextTick, ref } from 'vue';

import { useVbenModal } from '@vben/common-ui';
import { $t } from '@vben/locales';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { batchUpsertConfigs } from '#/features/system/api/config';
import {
  applyAdminApiTimeout,
  clearAdminRuntimeParamsCache,
  initAdminRuntimeConfig,
} from '#/utils/admin-runtime-config';

import { formatParamJsonForEdit } from '../data';

const PARAM_GROUP = 'param';

const emits = defineEmits<{ success: [] }>();

const editingKey = ref<string | null>(null);
const hydratingForm = ref(false);
const valueFieldType = ref('string');

function getDefaultValueForType(type: string): number | string | undefined {
  switch (type) {
    case 'bool': {
      return 'false';
    }
    case 'json':
    case 'string': {
      return '';
    }
    case 'number': {
      return undefined;
    }
    default: {
      return '';
    }
  }
}

function normalizeValueForForm(
  type: string,
  value: ConfigRow['value'],
): number | string | undefined {
  const raw = String(value ?? '');

  if (type === 'number') {
    const parsed = Number(raw);

    return Number.isFinite(parsed) ? parsed : undefined;
  }

  if (type === 'bool') {
    return ['true', '1', 'yes', 'on'].includes(raw.toLowerCase())
      ? 'true'
      : 'false';
  }

  if (type === 'json') {
    return formatParamJsonForEdit(value);
  }

  return raw;
}

function serializeParamValue(type: string, value: unknown): string {
  if (type === 'number') {
    return value === null || value === undefined || value === ''
      ? ''
      : String(value);
  }

  if (type === 'bool') {
    return String(value ?? 'false');
  }

  return String(value ?? '').trim();
}

function buildValueFieldSchema(type: string): VbenFormSchema {
  if (type === 'json') {
    return {
      component: 'Textarea',
      componentProps: {
        class: 'w-full font-mono',
        placeholder: $t('platform.config.param.jsonPlaceholder'),
        rows: 8,
      },
      fieldName: 'value',
      formItemClass: 'col-span-full w-full items-start',
      label: $t('platform.config.param.value'),
    };
  }

  if (type === 'number') {
    return {
      component: 'InputNumber',
      componentProps: {
        class: 'w-full',
        placeholder: $t('platform.config.param.numberPlaceholder'),
      },
      fieldName: 'value',
      formItemClass: 'col-span-full',
      label: $t('platform.config.param.value'),
    };
  }

  if (type === 'bool') {
    return {
      component: 'Select',
      componentProps: {
        class: 'w-full',
        options: [
          {
            label: $t('platform.config.param.boolTrue'),
            value: 'true',
          },
          {
            label: $t('platform.config.param.boolFalse'),
            value: 'false',
          },
        ],
      },
      defaultValue: 'false',
      fieldName: 'value',
      formItemClass: 'col-span-full',
      label: $t('platform.config.param.value'),
    };
  }

  return {
    component: 'Input',
    componentProps: {
      class: 'w-full',
      placeholder: $t('platform.config.param.stringPlaceholder'),
    },
    fieldName: 'value',
    formItemClass: 'col-span-full',
    label: $t('platform.config.param.value'),
  };
}

function getParamFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'key',
      formItemClass: 'col-span-full',
      label: $t('platform.config.param.keyLabel'),
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'name',
      formItemClass: 'col-span-full',
      label: $t('platform.config.param.nameLabel'),
      rules: 'required',
    },
    {
      component: 'Select',
      componentProps: {
        class: 'w-full',
        options: [
          {
            label: $t('platform.config.paramType.string'),
            value: 'string',
          },
          {
            label: $t('platform.config.paramType.number'),
            value: 'number',
          },
          {
            label: $t('platform.config.paramType.bool'),
            value: 'bool',
          },
          {
            label: $t('platform.config.paramType.json'),
            value: 'json',
          },
        ],
      },
      defaultValue: 'string',
      fieldName: 'type',
      label: $t('platform.config.param.valueType'),
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 0 },
      defaultValue: 0,
      fieldName: 'sort',
      label: $t('platform.config.param.sort'),
    },
    buildValueFieldSchema('string'),
    {
      component: 'Textarea',
      componentProps: { class: 'w-full', rows: 4 },
      fieldName: 'remark',
      formItemClass: 'col-span-full w-full items-start',
      label: $t('platform.config.param.remark'),
    },
  ];
}

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: getParamFormSchema(),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-2 gap-x-4 gap-y-1',
  handleValuesChange(values, fieldsChanged) {
    if (hydratingForm.value) {
      return;
    }

    if (fieldsChanged.includes('type')) {
      const type = String(values.type ?? 'string');
      syncValueFieldByType(type);
      formApi.setFieldValue('value', getDefaultValueForType(type));
    }
  },
});

function syncValueFieldByType(type: string) {
  if (valueFieldType.value === type) {
    return;
  }

  valueFieldType.value = type;
  const schema = [...(formApi.getState()?.schema ?? getParamFormSchema())];
  const index = schema.findIndex((item) => item.fieldName === 'value');
  if (index < 0) {
    return;
  }
  schema[index] = buildValueFieldSchema(type);
  formApi.setState({ schema });
}

async function hydrateParamForm(row?: ConfigRow) {
  hydratingForm.value = true;
  valueFieldType.value = 'string';
  formApi.resetForm();

  if (row?.key) {
    const type = row.type ?? 'string';
    const normalizedValue = normalizeValueForForm(type, row.value);

    editingKey.value = row.key;
    syncKeyFieldDisabled(true);
    syncValueFieldByType(type);
    await nextTick();

    await formApi.setValues({
      key: row.key,
      name: row.name,
      remark: row.remark ?? '',
      sort: row.sort ?? 0,
      type,
      value: normalizedValue,
    });
    await nextTick();
    await formApi.setFieldValue('value', normalizedValue);
  } else {
    editingKey.value = null;
    syncKeyFieldDisabled(false);
    syncValueFieldByType('string');
    await formApi.setValues({
      key: '',
      name: '',
      remark: '',
      sort: 0,
      type: 'string',
      value: '',
    });
  }

  await nextTick();
  hydratingForm.value = false;
}

const modalTitle = computed(() =>
  editingKey.value
    ? $t('platform.config.param.editParam')
    : $t('platform.config.param.createParam'),
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
    const type = String(values.type ?? 'string');
    modalApi.lock();
    try {
      await batchUpsertConfigs([
        {
          group_name: PARAM_GROUP,
          key: values.key as string,
          name: values.name as string,
          remark: (values.remark as string) || null,
          sort: Number(values.sort ?? 0),
          type,
          value: serializeParamValue(type, values.value),
        },
      ]);
      if (String(values.key).startsWith('admin.')) {
        clearAdminRuntimeParamsCache();
        await initAdminRuntimeConfig(true);
        const { requestClient, baseRequestClient } = await import('#/api/request');
        applyAdminApiTimeout(requestClient, baseRequestClient);
      }
      message.success($t('crud.saved'));
      modalApi.close();
      emits('success');
    } finally {
      modalApi.unlock();
    }
  },
  onOpenChange(isOpen: boolean) {
    if (!isOpen) return;
    const data = modalApi.getData<ConfigRow | Record<string, never>>();
    void hydrateParamForm((data as ConfigRow)?.key ? (data as ConfigRow) : undefined);
  },
});
</script>

<template>
  <Modal :title="modalTitle" class="w-[560px]">
    <Form />
  </Modal>
</template>
