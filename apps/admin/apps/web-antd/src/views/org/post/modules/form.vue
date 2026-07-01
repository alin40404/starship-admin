<script lang="ts" setup>
import type { PostRow, PostSavePayload } from '#/api/system/post';

import { computed, ref } from 'vue';

import { useVbenDrawer } from '@vben/common-ui';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { createPost, updatePost } from '#/api/system/post';

import { buildPostFormSchema } from '../data';

const emits = defineEmits<{ success: [] }>();

const editingId = ref<number | null>(null);
const readonly = ref(false);

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: buildPostFormSchema(),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
});

const drawerTitle = computed(() => {
  if (readonly.value) return '查看岗位';
  return editingId.value ? '编辑岗位' : '新增岗位';
});

const [Drawer, drawerApi] = useVbenDrawer({
  async onConfirm() {
    if (readonly.value) {
      drawerApi.close();
      return;
    }
    const { valid } = await formApi.validate();
    if (!valid) return;

    const values = (await formApi.getValues()) as PostSavePayload;
    drawerApi.lock();
    try {
      if (!editingId.value) {
        await createPost(values);
        message.success('创建成功');
      } else {
        await updatePost(editingId.value, values);
        message.success('已保存');
      }
      drawerApi.close();
      emits('success');
    } finally {
      drawerApi.unlock();
    }
  },
  async onOpenChange(isOpen: boolean) {
    if (!isOpen) return;
    formApi.resetForm();
    const data = drawerApi.getData<PostRow | Record<string, never>>();
    const id = data && typeof (data as PostRow).id === 'number' ? (data as PostRow).id : null;
    editingId.value = id;
    readonly.value = false;
    formApi.setState({ schema: buildPostFormSchema(false) });

    if (id) {
      formApi.setValues({
        code: (data as PostRow).code,
        name: (data as PostRow).name,
        remark: (data as PostRow).remark ?? '',
        sort: (data as PostRow).sort ?? 0,
        status: (data as PostRow).status,
      });
    } else {
      formApi.setValues({
        code: '',
        name: '',
        remark: '',
        sort: 0,
        status: 1,
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
