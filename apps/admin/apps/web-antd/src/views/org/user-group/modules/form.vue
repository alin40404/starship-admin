<script lang="ts" setup>
import type { UserGroupRow, UserGroupSavePayload } from '#/api/system/user-group';

import { computed, ref } from 'vue';

import { useVbenDrawer, useVbenModal } from '@vben/common-ui';
import { usePreferences } from '@vben/preferences';

import { message } from 'ant-design-vue';

import { useVbenForm } from '#/adapter/form';
import {
  createUserGroup,
  fetchUserGroup,
  syncUserGroupAdmins,
  updateUserGroup,
} from '#/api/system/user-group';

import { buildUserGroupFormSchema } from '../data';
import { $t } from '#/locales';

const emits = defineEmits<{ success: [] }>();

const { isMobile } = usePreferences();

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

const formTitle = computed(() =>
  editingId.value
    ? $t('system.org.userGroup.editTitle')
    : $t('system.org.userGroup.createTitle'),
);

type PopupApi = {
  close: () => void;
  getData: <T = Record<string, never>>() => T;
  lock: () => void;
  open: () => void;
  setData: <T>(data: T) => void;
  unlock: () => void;
};

function stripPayload(values: Record<string, unknown>): UserGroupSavePayload {
  const { admin_ids: _a, ...rest } = values;
  return rest as UserGroupSavePayload;
}

async function handleConfirm(popupApi: PopupApi) {
  const { valid } = await formApi.validate();
  if (!valid) return;

  const raw = (await formApi.getValues()) as Record<string, unknown>;
  const adminIds = ((raw.admin_ids as number[] | undefined) ?? []).map(Number);
  const values = stripPayload(raw);

  popupApi.lock();
  try {
    if (!editingId.value) {
      const created = await createUserGroup(values);
      await syncUserGroupAdmins(created.id, adminIds);
      message.success($t('crud.created'));
    } else {
      await updateUserGroup(editingId.value, values);
      await syncUserGroupAdmins(editingId.value, adminIds);
      message.success($t('crud.saved'));
    }
    popupApi.close();
    emits('success');
  } finally {
    popupApi.unlock();
  }
}

async function handleOpenChange(isOpen: boolean, popupApi: PopupApi) {
  if (!isOpen) return;
  formApi.resetForm();

  const data = popupApi.getData<UserGroupRow | Record<string, never>>();
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
}

const [Modal, modalApi] = useVbenModal({
  destroyOnClose: true,
  fullscreenButton: false,
  async onConfirm() {
    await handleConfirm(modalApi as PopupApi);
  },
  async onOpenChange(isOpen: boolean) {
    await handleOpenChange(isOpen, modalApi as PopupApi);
  },
});

const [Drawer, drawerApi] = useVbenDrawer({
  destroyOnClose: true,
  async onConfirm() {
    await handleConfirm(drawerApi as PopupApi);
  },
  async onOpenChange(isOpen: boolean) {
    await handleOpenChange(isOpen, drawerApi as PopupApi);
  },
});

function getActivePopupApi() {
  return (isMobile.value ? drawerApi : modalApi) as PopupApi;
}

function open(data: UserGroupRow | Record<string, never> = {}) {
  const api = getActivePopupApi();
  api.setData(data);
  api.open();
}

defineExpose({ open });
</script>

<template>
  <Modal
    v-if="!isMobile"
    :title="formTitle"
    class="w-full max-w-full md:w-[640px] md:max-w-[640px]"
  >
    <Form />
  </Modal>
  <Drawer v-else :title="formTitle" class="w-full">
    <Form />
  </Drawer>
</template>
