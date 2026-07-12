<script lang="ts" setup>
import type { VbenFormSchema } from '#/adapter/form';
import type { AdminRow, AdminSavePayload } from '#/api/system/admin';
import type { DeptNode } from '#/api/system/department';
import type { PostRow } from '#/api/system/post';
import type { RoleRow } from '#/api/system/role';

import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue';

import { useAccess } from '@vben/access';
import { useVbenDrawer } from '@vben/common-ui';
import { $t } from '@vben/locales';
import { usePreferences } from '@vben/preferences';

import { message, Spin } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import { createAdmin, updateAdmin } from '#/api/system/admin';
import { uploadAttachment } from '#/api/system/attachment';
import { validateUploadFile } from '#/utils/upload-policy';
import { fetchDepartmentOptionsTree } from '#/api/system/department';
import { fetchPostPage } from '#/api/system/post';
import { fetchRolePage } from '#/api/system/role';
import { ADMIN_PERMISSIONS } from '#/constants/admin-permissions';

import { buildAdminFormSchema, mapDeptForTreeSelect } from '../data';

const emits = defineEmits<{ success: [] }>();

const { hasAccessByCodes } = useAccess();
const { isMobile } = usePreferences();

const editingId = ref<number | null>(null);
const editingIsSuperAdmin = ref(false);
const metaReady = ref(false);
const showFormContent = ref(false);
const detailLoading = ref(false);

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
  const file = options?.file as File | undefined;
  if (!file) {
    options?.onError?.(new Error('missing file'));
    return;
  }

  const validationError = validateUploadFile(file, 'image');
  if (validationError) {
    message.error(validationError);
    options?.onError?.(new Error(validationError));
    return;
  }

  try {
    const row = await uploadAttachment(file);
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
  isMobile,
  (mobile) => {
    formApi.setState({
      commonConfig: {
        componentProps: { class: 'w-full' },
        labelClass: mobile ? 'justify-start' : undefined,
      },
      layout: mobile ? 'vertical' : 'horizontal',
    });
  },
  { immediate: true },
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
  editingId.value
    ? $t('ui.actionTitle.edit', [$t('permission.admin.entity')])
    : $t('ui.actionTitle.create', [$t('permission.admin.entity')]),
);

const canWriteAdmin = computed(() => {
  if (editingId.value) {
    return hasAccessByCodes([ADMIN_PERMISSIONS.UPDATE]);
  }
  return hasAccessByCodes([ADMIN_PERMISSIONS.CREATE]);
});

function applyAdminPreview(row: AdminRow) {
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
}

function applyCreateDefaults() {
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

function resolveOpenPayload() {
  const data = drawerApi.getData<AdminRow | Record<string, never>>();
  const isEdit =
    !!data &&
    typeof (data as AdminRow).id === 'number' &&
    (data as AdminRow).id > 0;

  return {
    data: data as AdminRow,
    isEdit,
  };
}

function applyDrawerFooterState() {
  if (!canWriteAdmin.value) {
    drawerApi.setState({
      cancelText: $t('system.shared.close'),
      showConfirmButton: false,
    });
  } else {
    drawerApi.setState({
      cancelText: undefined,
      showConfirmButton: true,
    });
  }
}

async function mountDrawerForm() {
  const { data, isEdit } = resolveOpenPayload();

  formApi.resetForm();
  showFormContent.value = true;
  await nextTick();

  if (isEdit) {
    applyAdminPreview(data);
  } else {
    applyCreateDefaults();
  }

  if (!metaReady.value) {
    detailLoading.value = true;
    try {
      await loadMeta();
    } catch {
      drawerApi.close();
    } finally {
      detailLoading.value = false;
    }
  }
}

const [Drawer, drawerApi] = useVbenDrawer({
  async onConfirm() {
    if (!canWriteAdmin.value) {
      drawerApi.close();
      return;
    }
    const { valid } = await formApi.validate();
    if (!valid) {
      return;
    }
    const values = (await formApi.getValues()) as AdminSavePayload;
    if (!editingId.value && !String(values.password ?? '').trim()) {
      message.warning($t('permission.admin.passwordRequired'));
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
        message.success($t('crud.created'));
      } else {
        await updateAdmin(editingId.value, payload);
        message.success($t('crud.saved'));
      }
      drawerApi.close();
      emits('success');
    } finally {
      drawerApi.unlock();
    }
  },

  async onOpenChange(isOpen: boolean) {
    if (!isOpen) {
      showFormContent.value = false;
      detailLoading.value = false;
      editingId.value = null;
      editingIsSuperAdmin.value = false;
      drawerApi.setState({
        cancelText: undefined,
        loading: false,
        showConfirmButton: true,
      });
      return;
    }

    const { data, isEdit } = resolveOpenPayload();
    if (isEdit) {
      const row = data as AdminRow;
      editingIsSuperAdmin.value = row.is_super === 1;
      editingId.value = row.id;
    } else {
      editingIsSuperAdmin.value = false;
      editingId.value = null;
    }

    showFormContent.value = false;
    drawerApi.setState({ loading: true });
    applyDrawerFooterState();
  },

  onOpened() {
    void mountDrawerForm().finally(() => {
      drawerApi.setState({ loading: false });
    });
  },
});

async function loadMeta() {
  const [dept, roles, posts] = await Promise.all([
    fetchDepartmentOptionsTree(),
    fetchRolePage({ page: 1, per_page: 500 }),
    fetchPostPage({ page: 1, per_page: 500 }),
  ]);
  deptTree.value = dept;
  roleOptions.value = roles.data;
  postOptions.value = posts.data;
  metaReady.value = true;
}

onMounted(() => {
  void loadMeta();
});
</script>

<template>
  <Drawer :title="drawerTitle" class="w-[560px]">
    <Spin :spinning="detailLoading" wrapper-class-name="w-full">
      <Form v-if="showFormContent" />
    </Spin>
  </Drawer>
</template>
