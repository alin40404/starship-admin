<script lang="ts" setup>
import type { UserGroupRow, UserGroupSavePayload } from '#/api/system/user-group';

import { computed, ref } from 'vue';

import { useVbenDrawer } from '@vben/common-ui';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import {
  createUserGroup,
  fetchUserGroup,
  syncUserGroupAdmins,
  updateUserGroup,
} from '#/api/system/user-group';

import { buildUserGroupFormSchema } from '../data';

const emits = defineEmits<{ success: [] }>();

const editingId = ref<number | null>(null);

const [Form, formApi] = useVbenForm({
  commonConfig: {
    componentProps: { class: 'w-full' },
    labelClass: 'justify-start',
  },
  layout: 'vertical',
  schema: buildUserGroupFormSchema(),
  showDefaultActions: false,
  wrapperClass: 'grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1',
});

const drawerTitle = computed(() =>
  editingId.value ? '编辑用户组' : '新增用户组',
);

function stripPayload(values: Record<string, unknown>): UserGroupSavePayload {
  const { admin_ids: _a, ...rest } = values;
  return rest as UserGroupSavePayload;
}

const [Drawer, drawerApi] = useVbenDrawer({
  async onConfirm() {
    const { valid } = await formApi.validate();
    if (!valid) return;

    const raw = (await formApi.getValues()) as Record<string, unknown>;
    const adminIds = ((raw.admin_ids as number[] | undefined) ?? []).map(Number);
    const values = stripPayload(raw);

    drawerApi.lock();
    try {
      if (!editingId.value) {
        const created = await createUserGroup(values);
        await syncUserGroupAdmins(created.id, adminIds);
        message.success('创建成功');
      } else {
        await updateUserGroup(editingId.value, values);
        await syncUserGroupAdmins(editingId.value, adminIds);
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

    const data = drawerApi.getData<UserGroupRow | Record<string, never>>();
    const id =
      data && typeof (data as UserGroupRow).id === 'number'
        ? (data as UserGroupRow).id
        : null;
    editingId.value = id;

    if (id) {
      const detail = await fetchUserGroup(id);
      formApi.setValues({
        admin_ids: detail.admin_ids ?? [],
        code: detail.code,
        name: detail.name,
        remark: detail.remark ?? '',
        sort: detail.sort ?? 0,
        status: detail.status,
      });
    } else {
      formApi.setValues({
        admin_ids: [],
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
  <Drawer :title="drawerTitle" class="max-w-[640px] w-[92vw]">
    <Form />
  </Drawer>
</template>
