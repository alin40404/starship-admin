<script lang="ts" setup>
import type {
  CategorySavePayload,
  CategoryTreeNode,
  CategoryTypeOption,
} from '#/api/system/category';

import { computed, reactive, ref, watch } from 'vue';

import { useVbenModal } from '@vben/common-ui';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import {
  createCategory,
  fetchCategory,
  fetchCategoryTypes,
  updateCategory,
} from '#/api/system/category';

import { buildFormSchema } from '../data';

const emits = defineEmits<{ success: [] }>();

const editingId = ref<number | null>(null);
const typeOptions = ref<CategoryTypeOption[]>([]);

function refreshFormSchema() {
  formApi.setState({
    schema: buildFormSchema({
      excludeId: editingId.value ?? undefined,
      onTypeChange: () => {
        formApi.setFieldValue('parent_id', 0);
      },
      typeOptions: typeOptions.value,
    }),
  });
}

const [Form, formApi] = useVbenForm(
  reactive({
    commonConfig: {
      componentProps: { class: 'w-full' },
      labelClass: 'justify-start',
    },
    layout: 'vertical' as const,
    schema: buildFormSchema({
      onTypeChange: () => {
        formApi.setFieldValue('parent_id', 0);
      },
      typeOptions: [],
    }),
    showDefaultActions: false,
    wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
  }) as any,
);

watch([editingId, typeOptions], () => {
  refreshFormSchema();
});

const modalTitle = computed(() =>
  editingId.value ? '编辑分类' : '新增分类',
);

const [Modal, modalApi] = useVbenModal({
  fullscreenButton: false,
  async onConfirm() {
    const { valid } = await formApi.validate();
    if (!valid) return;

    const values = (await formApi.getValues()) as CategorySavePayload;
    if (!values.parent_id) values.parent_id = 0;

    modalApi.lock();
    try {
      if (!editingId.value) {
        await createCategory(values);
        message.success('创建成功');
      } else {
        await updateCategory(editingId.value, values);
        message.success('已保存');
      }
      modalApi.close();
      emits('success');
    } finally {
      modalApi.unlock();
    }
  },
  async onOpenChange(isOpen: boolean) {
    if (!isOpen) {
      editingId.value = null;
      return;
    }

    formApi.resetForm();
    if (typeOptions.value.length === 0) {
      typeOptions.value = await fetchCategoryTypes();
    }

    const data = modalApi.getData<
      (CategoryTreeNode & { parent_id?: number; type?: string }) | Record<string, never>
    >();
    const id =
      data && typeof (data as CategoryTreeNode).id === 'number'
        ? (data as CategoryTreeNode).id
        : null;
    editingId.value = id;
    refreshFormSchema();

    if (id) {
      const detail = await fetchCategory(id);
      formApi.setValues({
        description: detail.description ?? '',
        diyname: detail.diyname ?? '',
        flag: detail.flag ?? [],
        image: detail.image ?? '',
        keywords: detail.keywords ?? '',
        name: detail.name,
        nickname: detail.nickname,
        parent_id: detail.parent_id ?? 0,
        sort: detail.sort ?? 0,
        status: detail.status,
        type: detail.type,
      });
    } else {
      const preset = data as { parent_id?: number; type?: string };
      formApi.setValues({
        description: '',
        diyname: '',
        flag: [],
        image: '',
        keywords: '',
        name: '',
        nickname: '',
        parent_id: preset.parent_id ?? 0,
        sort: 0,
        status: 'normal',
        type: preset.type ?? typeOptions.value[0]?.value ?? '',
      });
    }
  },
});
</script>

<template>
  <Modal :title="modalTitle" class="w-[720px] max-w-[92vw]">
    <div class="mb-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
      栏目类型请在「字典管理」中维护 <code>category_type</code>；分类支持无限级，上级分类需与当前栏目类型一致。
    </div>
    <Form />
  </Modal>
</template>
