<script lang="ts" setup>
import type { VbenFormSchema } from '#/adapter/form';
import type { AdminRow, AdminSavePayload } from '#/api/system/admin';
import type { DeptNode } from '#/api/system/department';
import type { PostRow } from '#/api/system/post';
import type { RoleRow } from '#/api/system/role';

import { computed, nextTick, reactive, ref, watch } from 'vue';

import { useVbenDrawer } from '@vben/common-ui';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { createAdmin, updateAdmin } from '#/api/system/admin';
import { uploadAttachment } from '#/api/system/attachment';
import { fetchDepartmentTree } from '#/api/system/department';
import { fetchPostPage } from '#/api/system/post';
import { fetchRolePage } from '#/api/system/role';

import { buildAdminFormSchema, mapDeptForTreeSelect } from '../data';

const emits = defineEmits<{ success: [] }>();

const editingId = ref<number | null>(null);
const editingIsSuperAdmin = ref(false);
const metaReady = ref(false);

const deptTree = ref<DeptNode[]>([]);
const roleOptions = ref<RoleRow[]>([]);
const postOptions = ref<PostRow[]>([]);

function normalizeAvatarToFileList(avatar: string | null | undefined) {
  if (!avatar) {
    return [];
  }
  return [
    {
      name: 'avatar',
      status: 'done',
      uid: 'avatar-existing',
      url: avatar,
    },
  ];
}

function onAvatarChange(_event: any) {}

async function onAvatarUpload(options: any) {
  try {
    const row = await uploadAttachment(options?.file);
    options?.onSuccess?.(row);
  } catch (error) {
    options?.onError?.(error);
    throw error;
  }
}

/** useVbenForm 的 schema 必须是数组；传入 computed(Ref) 会在 FormApi 里变成非数组，导致 handleValueFormat 报错 */
const [Form, formApi] = useVbenForm(
  reactive({
    /** 与 playground / vxe 搜索表单一致：选择框占满表单项宽度 */
    commonConfig: {
      componentProps: {
        class: 'w-full',
      },
    },
    schema: [] as VbenFormSchema[],
    showDefaultActions: false,
    wrapperClass: 'grid-cols-1',
  }) as any,
);

watch(
  [deptTree, roleOptions, postOptions, editingId, editingIsSuperAdmin],
  () => {
    formApi.setState({
      schema: buildAdminFormSchema({
        deptTreeData: mapDeptForTreeSelect(deptTree.value),
        isEdit: editingId.value !== null,
        lockRoles: editingIsSuperAdmin.value,
        postOptions: postOptions.value.map((p) => ({
          label: p.name,
          value: p.id,
        })),
        onAvatarChange,
        onAvatarUpload,
        roleOptions: roleOptions.value.map((r) => ({
          label: r.name,
          value: r.id,
        })),
      }),
    });
  },
  { deep: true, immediate: true },
);

const drawerTitle = computed(() =>
  editingId.value ? '编辑管理员' : '新增管理员',
);

const [Drawer, drawerApi] = useVbenDrawer({
  async onConfirm() {
    const { valid } = await formApi.validate();
    if (!valid) {
      return;
    }
    const values = (await formApi.getValues()) as AdminSavePayload;
    if (!editingId.value && !String(values.password ?? '').trim()) {
      message.warning('请填写密码');
      return;
    }
    drawerApi.lock();
    try {
      const payload: AdminSavePayload = { ...values };
      const avatarFiles = Array.isArray((values as any).avatar)
        ? ((values as any).avatar as any[])
        : [];
      const avatarFile = avatarFiles[0];
      payload.avatar =
        avatarFile?.response?.file_url ?? avatarFile?.url ?? undefined;
      if (avatarFiles.length === 0) {
        payload.avatar = '';
      }

      if (editingId.value && !payload.password) {
        delete payload.password;
      }
      if (payload.avatar === undefined) {
        delete payload.avatar;
      }
      if (editingIsSuperAdmin.value && editingId.value) {
        const p = payload as unknown as Record<string, unknown>;
        delete p.role_ids;
        delete p.status;
      }
      if (!editingId.value) {
        await createAdmin(payload);
        message.success('创建成功');
      } else {
        await updateAdmin(editingId.value, payload);
        message.success('已保存');
      }
      drawerApi.close();
      emits('success');
    } finally {
      drawerApi.unlock();
    }
  },

  async onOpenChange(isOpen: boolean) {
    if (!isOpen) {
      return;
    }
    await ensureMetaLoaded();
    const data = drawerApi.getData<AdminRow | Record<string, never>>();
    formApi.resetForm();

    const isEdit =
      data &&
      typeof (data as AdminRow).id === 'number' &&
      (data as AdminRow).id > 0;

    if (isEdit) {
      const row = data as AdminRow;
      editingIsSuperAdmin.value = row.is_super === 1;
      editingId.value = row.id;
    } else {
      editingIsSuperAdmin.value = false;
      editingId.value = null;
    }

    await nextTick();

    if (isEdit) {
      const row = data as AdminRow;
      formApi.setValues({
        avatar: normalizeAvatarToFileList(row.avatar),
        dept_id: row.dept_id ?? undefined,
        email: row.email ?? '',
        mobile: row.mobile ?? '',
        nickname: row.nickname ?? '',
        password: '',
        post_ids: row.posts?.map((p) => p.id) ?? [],
        real_name: row.real_name ?? '',
        remark: row.remark ?? '',
        role_ids: row.roles?.map((r) => r.id) ?? [],
        status: row.status,
        username: row.username,
      });
    } else {
      formApi.setValues({
        avatar: [],
        nickname: '',
        password: '',
        post_ids: [],
        real_name: '',
        remark: '',
        role_ids: [],
        status: 1,
        username: '',
      });
    }
  },
});

async function loadMeta() {
  const [dept, roles, posts] = await Promise.all([
    fetchDepartmentTree(),
    fetchRolePage({ page: 1, per_page: 500 }),
    fetchPostPage({ page: 1, per_page: 500 }),
  ]);
  deptTree.value = dept;
  roleOptions.value = roles.data;
  postOptions.value = posts.data;
  metaReady.value = true;
}

async function ensureMetaLoaded() {
  if (!metaReady.value) {
    await loadMeta();
  }
}
</script>

<template>
  <Drawer :title="drawerTitle" class="w-[560px]">
    <Form />
  </Drawer>
</template>
